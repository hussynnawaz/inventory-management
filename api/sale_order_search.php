<?php
// GET ?q=search_term -> JSON list of matching sale orders for return processing.
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare('
    SELECT id, order_no, customer_name, total, order_date
    FROM sale_orders
    WHERE order_no LIKE ? OR customer_name LIKE ?
    ORDER BY created_at DESC
    LIMIT 10
');
$like = "%{$q}%";
$stmt->execute([$like, $like]);
echo json_encode($stmt->fetchAll());
