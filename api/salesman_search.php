<?php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') { echo json_encode([]); exit; }

$like = "%{$q}%";
$stmt = $pdo->prepare('
    SELECT id, salesman_id, name, phone, cnic, address
    FROM salesmen
    WHERE salesman_id LIKE ? OR name LIKE ? OR phone LIKE ?
    ORDER BY
        CASE
            WHEN salesman_id LIKE ? THEN 1
            WHEN name LIKE ? THEN 2
            ELSE 3
        END,
        name ASC
    LIMIT 10
');
$stmt->execute([$like, $like, $like, $like, $like]);
echo json_encode($stmt->fetchAll());
