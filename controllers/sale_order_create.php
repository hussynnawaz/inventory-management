<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

header('Content-Type: application/json');

function fail(string $msg): void {
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

$items = $input['items'] ?? [];
if (empty($items) || !is_array($items)) {
    fail('Please add at least one sale item.');
}

$orderNo = 'SO-' . date('Ymd') . '-' . str_pad((int)$pdo->query('SELECT COUNT(*)+1 FROM sale_orders')->fetchColumn(), 4, '0', STR_PAD_LEFT);
$orderDate = date('Y-m-d H:i:s');

$customerId = null;
$customerCode = trim($input['customer_code'] ?? '');
if ($customerCode !== '') {
    $stmt = $pdo->prepare('SELECT id FROM customers WHERE code = ?');
    $stmt->execute([$customerCode]);
    $row = $stmt->fetch();
    $customerId = $row ? $row['id'] : null;
}

// Look up salesman
$salesmanId = null;
$salesmanName = '';
$smId = (int)($input['salesman_id'] ?? 0);
if ($smId > 0) {
    $stmt = $pdo->prepare('SELECT id, name FROM salesmen WHERE id = ?');
    $stmt->execute([$smId]);
    $sm = $stmt->fetch();
    if ($sm) {
        $salesmanId = $sm['id'];
        $salesmanName = $sm['name'];
    }
}

$subtotal = 0;
foreach ($items as $it) {
    $qty = (int)($it['qty'] ?? 0);
    $price = (float)($it['price'] ?? 0);
    if ($qty <= 0) fail('Item quantity must be greater than zero.');
    if ($price < 0) fail('Item price cannot be negative.');
    $pid = (int)($it['product_id'] ?? 0);
    if ($pid <= 0) fail('Invalid product ID.');
    // Check stock
    $stockStmt = $pdo->prepare('SELECT quantity FROM products WHERE id = ?');
    $stockStmt->execute([$pid]);
    $stock = (int)$stockStmt->fetchColumn();
    if ($stock < $qty) {
        $nameStmt = $pdo->prepare('SELECT name FROM products WHERE id = ?');
        $nameStmt->execute([$pid]);
        $pname = $nameStmt->fetchColumn() ?: 'Product';
        fail("Insufficient stock for {$pname}. Available: {$stock}, Requested: {$qty}.");
    }
    $subtotal += $qty * $price;
}

$salesTaxPct = (float)($input['sales_tax_pct'] ?? 0);
$salesTaxAmt = round($subtotal * $salesTaxPct / 100, 2);
$advancedTaxPct = (float)($input['advanced_tax_pct'] ?? 0);
$advancedTaxAmt = round($subtotal * $advancedTaxPct / 100, 2);
$total = round($subtotal + $salesTaxAmt + $advancedTaxAmt, 2);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO sale_orders
        (order_no, order_date, customer_id, customer_code, customer_name, contact, destination,
         salesman, salesman_id, ntn_no, sales_tax_no, cnic, address, subtotal,
         sales_tax_pct, sales_tax_amt, advanced_tax_pct, advanced_tax_amt, total, user_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $orderNo, $orderDate, $customerId, $customerCode,
        trim($input['customer_name'] ?? ''), trim($input['contact'] ?? ''),
        trim($input['destination'] ?? ''),
        $salesmanName, $salesmanId,
        trim($input['ntn_no'] ?? ''), trim($input['sales_tax_no'] ?? ''),
        trim($input['cnic'] ?? ''), trim($input['address'] ?? ''),
        $subtotal, $salesTaxPct, $salesTaxAmt, $advancedTaxPct, $advancedTaxAmt, $total, current_user()['id'],
    ]);
    $orderId = $pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO sale_order_items
        (sale_order_id, product_id, product_name, quantity, price, line_total)
        VALUES (?,?,?,?,?,?)');
    $stockStmt = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        $qty = (int)($it['qty'] ?? 0);
        $price = (float)($it['price'] ?? 0);
        $pname = $pdo->prepare('SELECT name FROM products WHERE id = ?');
        $pname->execute([$pid]);
        $name = $pname->fetchColumn() ?: '';
        $itemStmt->execute([$orderId, $pid, $name, $qty, $price, $qty * $price]);
        // Deduct stock
        $stockStmt->execute([$qty, $pid]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'order_id' => $orderId, 'order_no' => $orderNo, 'message' => 'Sale order created successfully.']);
} catch (Exception $e) {
    $pdo->rollBack();
    fail('Could not save sale order: ' . $e->getMessage());
}
