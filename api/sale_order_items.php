<?php
// API endpoint to fetch line items for a sale order along with return tracking
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

header('Content-Type: application/json');

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare('
    SELECT soi.product_id, soi.product_name, soi.quantity, soi.price, p.cost_price, p.sku,
        COALESCE((
            SELECT SUM(r.quantity)
            FROM returns r
            WHERE r.sale_order_id = soi.sale_order_id AND r.product_id = soi.product_id
        ), 0) AS returned_quantity
    FROM sale_order_items soi
    LEFT JOIN products p ON p.id = soi.product_id
    WHERE soi.sale_order_id = ?
');
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

foreach ($items as &$it) {
    $it['quantity'] = (int)$it['quantity'];
    $it['returned_quantity'] = (int)$it['returned_quantity'];
    $it['returnable_quantity'] = max(0, $it['quantity'] - $it['returned_quantity']);
    $it['price'] = (float)$it['price'];
}

echo json_encode($items);
