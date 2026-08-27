<?php
// API endpoint to fetch line items for a sale order
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
    SELECT soi.product_id, soi.product_name, soi.quantity, soi.price, p.cost_price, p.sku
    FROM sale_order_items soi
    LEFT JOIN products p ON p.id = soi.product_id
    WHERE soi.sale_order_id = ?
');
$stmt->execute([$orderId]);
echo json_encode($stmt->fetchAll());
