<?php
// GET ?q=search_term -> JSON list of matching customers for autocomplete.
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode([]);
    exit;
}

// Search by code, name, or contact
$stmt = $pdo->prepare('
    SELECT id, code, name AS customer_name, contact, delivery_route, salesman, ntn_no, sales_tax_no, cnic, address
    FROM customers
    WHERE code LIKE ? OR name LIKE ? OR contact LIKE ?
    ORDER BY
        CASE
            WHEN code LIKE ? THEN 1
            WHEN name LIKE ? THEN 2
            ELSE 3
        END,
        name ASC
    LIMIT 10
');
$like = "%{$q}%";
$stmt->execute([$like, $like, $like, $like, $like]);
$results = $stmt->fetchAll();

echo json_encode($results);
