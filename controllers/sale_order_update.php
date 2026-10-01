<?php
// POST handler for updating an existing Sale Order.
//
// The invoice is UPDATED IN PLACE: the same sale_orders row, the same
// order_no (invoice number) and the same customer_payments rows survive the
// edit. Nothing is deleted and re-created.
//
// Inventory is adjusted with a per-product DELTA, never as a fresh deduction:
//
//     quantity_delta = new_quantity - old_quantity
//         delta  >  0  ->  deduct only  delta units
//         delta  <  0  ->  return  abs(delta) units
//         delta  =  0  ->  inventory untouched
//
// Validation for an EXISTING sale uses the effective available stock:
//
//     available_for_edit = current_stock + original_sale_quantity
//
// so editing 40 -> 45 is allowed when current stock is 10 (50 were available
// before the original sale). Stock is then moved with guarded, atomic
// UPDATEs (quantity = quantity - ?  WHERE quantity >= ?) so two users editing
// the same product at the same time can never drive stock negative or
// silently corrupt it.
//
// Everything runs inside one transaction: if any product fails, the invoice
// and every inventory change roll back together.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_calculations.php';
require_login();

header('Content-Type: application/json');

function fail(string $msg): void {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Invalid request method.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$orderId = (int)($input['order_id'] ?? 0);
if ($orderId <= 0) {
    fail('Invalid order ID.');
}

$items = $input['items'] ?? [];
if (empty($items) || !is_array($items)) {
    fail('Please add at least one sale item.');
}

try {
    $pdo->beginTransaction();

    // 1. Lock the order row so two concurrent edits cannot interleave.
    //    SELECT ... FOR UPDATE serialises the whole read-validate-write below.
    $existingStmt = $pdo->prepare('SELECT * FROM sale_orders WHERE id = ? FOR UPDATE');
    $existingStmt->execute([$orderId]);
    $existingOrder = $existingStmt->fetch();
    if (!$existingOrder) {
        fail('Sale order not found.');
    }

    // 2. Original quantities currently on the invoice, aggregated per product.
    $oldItemsStmt = $pdo->prepare('SELECT id, product_id, product_name, quantity, price, line_total
                                   FROM sale_order_items WHERE sale_order_id = ? ORDER BY id FOR UPDATE');
    $oldItemsStmt->execute([$orderId]);
    $oldRows = $oldItemsStmt->fetchAll();

    $oldByProduct = [];   // product_id => quantity currently invoiced
    $oldItemByProduct = []; // product_id => sale_order_items row (kept identity)
    foreach ($oldRows as $row) {
        $pid = (int)$row['product_id'];
        $oldByProduct[$pid] = ($oldByProduct[$pid] ?? 0) + (int)$row['quantity'];
        $oldItemByProduct[$pid] = $row;
    }

    // 3. Merge the submitted items per product (a product may only appear as
    //    ONE invoice line, so duplicates are summed) and validate the basics.
    $newByProduct = [];
    foreach ($items as $it) {
        if (!is_array($it)) {
            fail('Invalid sale item.');
        }
        $pid   = (int)($it['product_id'] ?? 0);
        $qty   = (int)($it['qty'] ?? 0);
        $price = round((float)($it['price'] ?? 0), 2);

        if ($pid <= 0)  fail('Invalid product ID.');
        if ($qty <= 0)  fail('Item quantity must be greater than zero.');
        if ($price < 0) fail('Item price cannot be negative.');

        if (!isset($newByProduct[$pid])) {
            $newByProduct[$pid] = ['product_id' => $pid, 'qty' => 0, 'price' => $price];
        }
        $newByProduct[$pid]['qty'] += $qty;
    }

    // 4. Current stock + product names, locked for the products we touch.
    $productIds = array_values(array_unique(array_merge(array_keys($oldByProduct), array_keys($newByProduct))));
    sort($productIds); // stable lock order -> fewer deadlocks

    $stockByProduct = [];
    $nameByProduct  = [];
    if ($productIds) {
        $in = implode(',', array_fill(0, count($productIds), '?'));
        $prStmt = $pdo->prepare("SELECT id, name, quantity FROM products WHERE id IN ({$in}) FOR UPDATE");
        $prStmt->execute($productIds);
        foreach ($prStmt->fetchAll() as $pr) {
            $pid = (int)$pr['id'];
            $stockByProduct[$pid] = (int)$pr['quantity'];
            $nameByProduct[$pid]  = (string)$pr['name'];
        }
        foreach ($productIds as $pid) {
            if (!array_key_exists($pid, $stockByProduct)) {
                fail('Product #' . $pid . ' no longer exists.');
            }
        }
    }

    // 5. Per-product DELTA validation against the EFFECTIVE available stock:
    //
    //        available_for_edit = current_stock + original_sale_quantity
    //
    //    Products already on the invoice get their original quantity back as
    //    headroom; products newly added are validated against plain stock.
    $deltas = [];      // product_id => delta
    foreach ($productIds as $pid) {
        $oldQty  = (int)($oldByProduct[$pid] ?? 0);
        $newQty  = (int)($newByProduct[$pid]['qty'] ?? 0); // 0 == removed
        $stock   = (int)$stockByProduct[$pid];
        $pname   = $nameByProduct[$pid] ?: ('Product #' . $pid);

        $availableForEdit = $stock + $oldQty;
        if ($newQty > $availableForEdit) {
            fail("Insufficient stock for {$pname}. Available quantity for this product is {$availableForEdit}.");
        }

        $delta = $newQty - $oldQty; // >0 deduct, <0 return, 0 untouched
        if ($delta !== 0) {
            $deltas[$pid] = $delta;
        }
    }

    // 6. Apply the inventory deltas with guarded atomic UPDATEs.
    //    Products are processed in ascending id order for a consistent lock
    //    sequence. A deducted unit is only removed if the row still holds
    //    enough stock, so a competing sale can never push it negative.
    foreach ($productIds as $pid) {
        $delta = $deltas[$pid] ?? 0;
        if ($delta > 0) {
            $up = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?');
            $up->execute([$delta, $pid, $delta]);
            if ($up->rowCount() !== 1) {
                // Stock moved underneath us since the validation read.
                $fresh = (int)$pdo->query('SELECT quantity FROM products WHERE id = ' . $pid)->fetchColumn();
                $availableForEdit = $fresh + (int)($oldByProduct[$pid] ?? 0);
                $pname = $nameByProduct[$pid] ?: ('Product #' . $pid);
                fail("Insufficient stock for {$pname}. Available quantity for this product is {$availableForEdit}.");
            }
        } elseif ($delta < 0) {
            $up = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
            $up->execute([abs($delta), $pid]);
        }
        // delta === 0 -> inventory deliberately not modified
    }

    // 7. Line items: update kept lines in place (same row identity), insert
    //    brand-new products, delete removed ones. Totals are recalculated
    //    from the new quantities/prices.
    $subtotal   = 0.0;
    $newTotalByProduct = [];
    foreach ($newByProduct as $pid => $new) {
        $lineTotal = invoice_line_total($new['price'], $new['qty']);
        $subtotal  = invoice_round($subtotal + $lineTotal);

        if (isset($oldItemByProduct[$pid])) {
            $pdo->prepare('UPDATE sale_order_items
                           SET product_name = ?, quantity = ?, price = ?, line_total = ?
                           WHERE id = ?')
                ->execute([
                    $nameByProduct[$pid] ?: ($oldItemByProduct[$pid]['product_name'] ?? ''),
                    $new['qty'], $new['price'], $lineTotal,
                    (int)$oldItemByProduct[$pid]['id'],
                ]);
        } else {
            $pdo->prepare('INSERT INTO sale_order_items
                           (sale_order_id, product_id, product_name, quantity, price, line_total)
                           VALUES (?,?,?,?,?,?)')
                ->execute([
                    $orderId, $pid,
                    $nameByProduct[$pid] ?? '',
                    $new['qty'], $new['price'], $lineTotal,
                ]);
        }

        $newTotalByProduct[$pid] = $lineTotal;
    }

    // Removed products: their units were already returned to stock in step 6.
    foreach ($oldByProduct as $pid => $oldQty) {
        if (!isset($newByProduct[$pid])) {
            $pdo->prepare('DELETE FROM sale_order_items WHERE id = ?')
                ->execute([(int)$oldItemByProduct[$pid]['id']]);
        }
    }

    // 8. Authoritative tax recalculation (same helper the PDF/screens use).
    $totals = invoice_calculate_totals(
        $subtotal,
        (float)($input['sales_tax_pct'] ?? 0),
        (float)($input['advanced_tax_pct'] ?? 0)
    );

    // 9. Customer lookup (changing the customer is allowed, the invoice
    //    number is never regenerated).
    $customerId   = null;
    $customerCode = trim((string)($input['customer_code'] ?? ''));
    if ($customerCode !== '') {
        $stmt = $pdo->prepare('SELECT id FROM customers WHERE code = ?');
        $stmt->execute([$customerCode]);
        $row = $stmt->fetch();
        $customerId = $row ? (int)$row['id'] : null;
    }

    // 10. Salesman lookup.
    $salesmanId   = null;
    $salesmanName = '';
    $smId = (int)($input['salesman_id'] ?? 0);
    if ($smId > 0) {
        $stmt = $pdo->prepare('SELECT id, name FROM salesmen WHERE id = ?');
        $stmt->execute([$smId]);
        $sm = $stmt->fetch();
        if ($sm) {
            $salesmanId   = (int)$sm['id'];
            $salesmanName = $sm['name'];
        }
    }

    // 11. Invoice Date is editable, but only when it actually changes, so an
    //     untouched edit never disturbs the stored timestamp. It stays
    //     completely independent from any Payment Date.
    $orderDate = (string)$existingOrder['order_date'];
    $requestedDate = invoice_normalize_date($input['order_date'] ?? '');
    if ($requestedDate === null && trim((string)($input['order_date'] ?? '')) !== '') {
        fail('Invalid invoice date. Use a real calendar date.');
    }
    if ($requestedDate !== null && substr($orderDate, 0, 10) !== $requestedDate) {
        $orderDate = $requestedDate . ' 00:00:00';
    }

    // 12. Update the header IN PLACE. order_no is never written here, so the
    //     invoice number is guaranteed unchanged and no duplicate is created.
    //     updated_at only exists in newer databases.
    $updatedAtCol = $pdo->query("SHOW COLUMNS FROM sale_orders LIKE 'updated_at'")->fetch();
    $updatedAtSql = $updatedAtCol ? ', updated_at = NOW()' : '';

    $stmt = $pdo->prepare("UPDATE sale_orders SET
        order_date = ?, customer_id = ?, customer_code = ?, customer_name = ?, contact = ?,
        destination = ?, salesman = ?, salesman_id = ?,
        ntn_no = ?, sales_tax_no = ?, cnic = ?, address = ?,
        subtotal = ?, sales_tax_pct = ?, sales_tax_amt = ?,
        advanced_tax_pct = ?, advanced_tax_amt = ?,
        total = ?, user_id = ?{$updatedAtSql}
        WHERE id = ?");
    $stmt->execute([
        $orderDate, $customerId, $customerCode,
        trim((string)($input['customer_name'] ?? '')), trim((string)($input['contact'] ?? '')),
        trim((string)($input['destination'] ?? '')),
        $salesmanName, $salesmanId,
        trim((string)($input['ntn_no'] ?? '')), trim((string)($input['sales_tax_no'] ?? '')),
        trim((string)($input['cnic'] ?? '')), trim((string)($input['address'] ?? '')),
        $totals['subtotal'], $totals['sales_tax_pct'], $totals['sales_tax_amt'],
        $totals['advanced_tax_pct'], $totals['advanced_tax_amt'],
        $totals['net_total'], current_user()['id'],
        $orderId,
    ]);

    // 13. Payments are untouched: same customer_payments rows stay attached
    //     to this invoice. Only the derived paid/remaining/status are
    //     recomputed for the response.
    $amountPaid = invoice_order_amount_paid($pdo, $orderId);
    $amountRemaining = invoice_amount_remaining($totals['net_total'], $amountPaid);
    $paymentStatus = invoice_payment_status($totals['net_total'], $amountPaid);

    // 14. Invalidate any cached PDF so it re-renders from the updated data.
    $cacheFile = __DIR__ . '/../cache/pdfs/sale_order_' . $orderId . '.pdf';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }

    $pdo->commit();

    echo json_encode([
        'success'           => true,
        'order_id'          => $orderId,
        'order_no'          => $existingOrder['order_no'], // unchanged invoice number
        'order_date'        => $orderDate,
        'message'           => 'Sale order updated successfully.',
        'subtotal'          => $totals['subtotal'],
        'sales_tax_amt'     => $totals['sales_tax_amt'],
        'advanced_tax_amt'  => $totals['advanced_tax_amt'],
        'grand_total'       => $totals['net_total'],
        'amount_paid'       => $amountPaid,
        'amount_remaining'  => $amountRemaining,
        'payment_status'    => $paymentStatus,
        'inventory_changes' => array_map(
            static fn(int $pid, int $delta): array => [
                'product_id'   => $pid,
                'product_name' => $nameByProduct[$pid] ?? '',
                'old_quantity' => (int)($oldByProduct[$pid] ?? 0),
                'new_quantity' => (int)($newByProduct[$pid]['qty'] ?? 0),
                'delta'        => $delta,
                'stock_now'    => (int)($stockByProduct[$pid] ?? 0) - $delta,
            ],
            array_keys($deltas),
            $deltas
        ),
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Could not update sale order: ' . $e->getMessage()]);
    exit;
}