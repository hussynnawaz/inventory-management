<?php
// GET ?code=C-AB-1234 -> JSON customer details for auto-fetch on sale order form.
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$code = trim($_GET['code'] ?? '');

if ($code === '') {
    echo json_encode(['found' => false]);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM customers WHERE code = ?');
$stmt->execute([$code]);
$customer = $stmt->fetch();

if (!$customer) {
    echo json_encode(['found' => false]);
    exit;
}

echo json_encode([
    'found' => true,
    'customer' => [
        'code'           => $customer['code'],
        'customer_name'  => $customer['name'],
        'contact'        => $customer['contact'],
        'destination'    => $customer['destination'],
        'ntn_no'         => $customer['ntn_no'],
        'sales_tax_no'   => $customer['sales_tax_no'],
        'cnic'           => $customer['cnic'],
        'address'        => $customer['address'],
    ],
]);
