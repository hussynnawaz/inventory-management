<?php
// API endpoint to fetch a full sale order for editing
// GET ?id=X -> returns order header + items + customer + salesman
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_calculations.php';
require_login();

header('Content-Type: application/json');

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID.']);
    exit;
}

// Fetch order header
$stmt = $pdo->prepare('SELECT * FROM sale_orders WHERE id = ?');
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit;
}

// Fetch order items with product details
$itemStmt = $pdo->prepare('
    SELECT soi.id, soi.product_id, soi.product_name, soi.quantity, soi.price, soi.line_total,
           p.sku, p.quantity AS stock_qty, p.sale_price AS current_sale_price
    FROM sale_order_items soi
    LEFT JOIN products p ON p.id = soi.product_id
    WHERE soi.sale_order_id = ?
    ORDER BY soi.id
');
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

// For an EXISTING sale the effective available stock is:
//     available_for_edit = current_stock + original_sale_quantity
// because the units already consumed by this invoice are given back as
// headroom while it is being edited (40 -> 45 is allowed with 10 in stock).
$stockByProduct = [];
foreach ($items as &$it) {
    $pid = (int)$it['product_id'];
    $currentStock = (int)($it['stock_qty'] ?? 0);
    $it['current_stock'] = $currentStock;
    $it['available_for_edit'] = $currentStock + (int)$it['quantity'];
    $stockByProduct[$pid] = $currentStock;
}
unset($it);

// Fetch salesman details if available
$salesman = null;
if (!empty($order['salesman_id'])) {
    $smStmt = $pdo->prepare('SELECT id, salesman_id, name, phone, cnic FROM salesmen WHERE id = ?');
    $smStmt->execute([$order['salesman_id']]);
    $salesman = $smStmt->fetch();
}

// Authoritative money + payment position of the invoice as it stands.
$totals = invoice_totals_from_order($order);
$amountPaid = invoice_order_amount_paid($pdo, $orderId);

echo json_encode([
    'success'  => true,
    'order'    => $order,
    'items'    => $items,
    'salesman' => $salesman,
    'totals'   => [
        'subtotal'         => $totals['subtotal'],
        'sales_tax_pct'    => $totals['sales_tax_pct'],
        'sales_tax_amt'    => $totals['sales_tax_amt'],
        'advanced_tax_pct' => $totals['advanced_tax_pct'],
        'advanced_tax_amt' => $totals['advanced_tax_amt'],
        'grand_total'      => $totals['net_total'],
        'amount_paid'      => $amountPaid,
        'amount_remaining' => invoice_amount_remaining($totals['net_total'], $amountPaid),
        'payment_status'   => invoice_payment_status($totals['net_total'], $amountPaid),
    ],
]);