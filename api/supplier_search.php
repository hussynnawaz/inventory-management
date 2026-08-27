<?php
// GET ?q=search_term -> JSON list of matching suppliers for autocomplete.
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');

if ($q === '') {
    $stmt = $pdo->query('SELECT id, code, name, company_name, contact, phone, email FROM suppliers ORDER BY name ASC LIMIT 10');
    echo json_encode($stmt->fetchAll());
    exit;
}

$stmt = $pdo->prepare('
    SELECT id, code, name, company_name, contact, phone, email
    FROM suppliers
    WHERE name LIKE ? OR code LIKE ? OR company_name LIKE ? OR contact LIKE ? OR phone LIKE ?
    ORDER BY name ASC
    LIMIT 10
');
$like = "%{$q}%";
$stmt->execute([$like, $like, $like, $like, $like]);
echo json_encode($stmt->fetchAll());