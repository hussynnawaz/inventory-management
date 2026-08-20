<?php
// POST handler for saving supplier.
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

$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');

if ($name === '') {
    fail('Supplier name is required.');
}

try {
    $stmt = $pdo->prepare('INSERT INTO suppliers (name, phone, email) VALUES (?, ?, ?)');
    $stmt->execute([$name, $phone, $email]);
    $id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Supplier added successfully.',
        'supplier' => [
            'id' => (int)$id,
            'name' => $name,
            'phone' => $phone,
            'email' => $email
        ]
    ]);
} catch (Exception $e) {
    fail('Could not save supplier: ' . $e->getMessage());
}
