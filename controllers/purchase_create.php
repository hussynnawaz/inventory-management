<?php
// POST handler for creating a purchase.
// Input JSON: supplier_id (optional), supplier_name (if quick add), reference_no, items: [{ product_id, qty, cost }]
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
    fail('Please add at least one item to purchase.');
}

$supplierId = !empty($input['supplier_id']) ? (int)$input['supplier_id'] : null;

// Auto-create supplier if name given but no ID
if (!$supplierId && !empty($input['supplier_name'])) {
    $sName = trim($input['supplier_name']);
    $stmtS = $pdo->prepare('SELECT id FROM suppliers WHERE name = ?');
    $stmtS->execute([$sName]);
    $existingS = $stmtS->fetchColumn();
    if ($existingS) {
        $supplierId = (int)$existingS;
    } else {
        $insS = $pdo->prepare('INSERT INTO suppliers (name) VALUES (?)');
        $insS->execute([$sName]);
        $supplierId = (int)$pdo->lastInsertId();
    }
}

// Generate reference number if not provided
$refNo = trim($input['reference_no'] ?? '');
if ($refNo === '') {
    $refNo = 'PO-' . date('Ymd') . '-' . str_pad((int)$pdo->query('SELECT COUNT(*)+1 FROM purchases')->fetchColumn(), 4, '0', STR_PAD_LEFT);
}

$total = 0;
foreach ($items as $it) {
    $qty = (int)($it['qty'] ?? 0);
    $cost = (float)($it['cost'] ?? 0);
    if ($qty <= 0) fail('Purchase quantity must be greater than 0.');
    if ($cost < 0) fail('Cost price cannot be negative.');
    $total += $qty * $cost;
}

try {
    $pdo->beginTransaction();

    // 1. Insert into purchases table
    $stmt = $pdo->prepare('INSERT INTO purchases (reference_no, supplier_id, user_id, total) VALUES (?, ?, ?, ?)');
    $stmt->execute([$refNo, $supplierId, current_user()['id'], $total]);
    $purchaseId = $pdo->lastInsertId();

    // 2. Insert items, update product stock and cost price
    $itemStmt = $pdo->prepare('INSERT INTO purchase_items (purchase_id, product_id, quantity, cost, line_total) VALUES (?, ?, ?, ?, ?)');
    $updateProdStmt = $pdo->prepare('UPDATE products SET quantity = quantity + ?, cost_price = ? WHERE id = ?');

    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        $qty = (int)($it['qty'] ?? 0);
        $cost = (float)($it['cost'] ?? 0);
        $lineTotal = $qty * $cost;

        $itemStmt->execute([$purchaseId, $pid, $qty, $cost, $lineTotal]);
        $updateProdStmt->execute([$qty, $cost, $pid]);
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'purchase_id' => $purchaseId,
        'reference_no' => $refNo,
        'message' => 'Purchase recorded and inventory updated successfully.'
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    fail('Could not save purchase: ' . $e->getMessage());
}
