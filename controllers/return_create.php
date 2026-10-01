<?php
// POST handler for creating product return.
// Input JSON: sale_order_id (required/optional), items: [{ product_id, qty, refund_price, reason }]
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
    fail('Please add at least one item to return.');
}

$saleOrderId = !empty($input['sale_order_id']) ? (int)$input['sale_order_id'] : null;

try {
    $pdo->beginTransaction();

    $returnNoBase = 'RET-' . date('Ymd') . '-';
    $count = (int)$pdo->query('SELECT COUNT(*) FROM returns')->fetchColumn();

    $itemStmt = $pdo->prepare('INSERT INTO returns (return_no, sale_order_id, product_id, quantity, refund_price, line_total, reason, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $updateProdStmt = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');

    $savedReturns = [];

    foreach ($items as $it) {
        $pid = (int)($it['product_id'] ?? 0);
        $qty = (int)($it['qty'] ?? 0);
        $refundPrice = round((float)($it['refund_price'] ?? 0), 2);
        $reason = trim($it['reason'] ?? ($input['default_reason'] ?? ''));

        if ($pid <= 0) fail('Invalid product selected.');
        if ($qty <= 0) fail('Return quantity must be greater than 0.');
        if ($refundPrice < 0) fail('Refund price cannot be negative.');

        // If linked to a sale order, check returnable limit
        if ($saleOrderId) {
            $soiStmt = $pdo->prepare('SELECT quantity FROM sale_order_items WHERE sale_order_id = ? AND product_id = ?');
            $soiStmt->execute([$saleOrderId, $pid]);
            $soldQty = (int)$soiStmt->fetchColumn();
            if ($soldQty <= 0) {
                fail('Product is not part of this sale order.');
            }

            $retStmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM returns WHERE sale_order_id = ? AND product_id = ?');
            $retStmt->execute([$saleOrderId, $pid]);
            $prevRetQty = (int)$retStmt->fetchColumn();

            $maxReturnable = max(0, $soldQty - $prevRetQty);
            if ($qty > $maxReturnable) {
                $pNameStmt = $pdo->prepare('SELECT name FROM products WHERE id = ?');
                $pNameStmt->execute([$pid]);
                $pName = $pNameStmt->fetchColumn() ?: 'Product';
                fail("Cannot return {$qty} units of {$pName}. Maximum returnable quantity is {$maxReturnable}.");
            }
        }

        $lineTotal = round($qty * $refundPrice, 2);
        $count++;
        $retNo = $returnNoBase . str_pad($count, 4, '0', STR_PAD_LEFT);

        $itemStmt->execute([$retNo, $saleOrderId, $pid, $qty, $refundPrice, $lineTotal, $reason, current_user()['id']]);
        $updateProdStmt->execute([$qty, $pid]);

        $savedReturns[] = ['return_no' => $retNo, 'id' => (int)$pdo->lastInsertId()];
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Return recorded and stock restocked successfully.',
        'returns' => $savedReturns
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    fail('Could not process return: ' . $e->getMessage());
}
