<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

header('Content-Type: application/json');

function fail(string $msg): void {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;
$action = $input['action'] ?? '';

if ($action === 'save') {
    $id = (int)($input['id'] ?? 0);
    $salesmanId = trim($input['salesman_id'] ?? '');
    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $cnic = trim($input['cnic'] ?? '');
    $address = trim($input['address'] ?? '');

    if ($salesmanId === '') fail('Salesman ID is required.');
    if ($name === '') fail('Name is required.');

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE salesmen SET salesman_id=?, name=?, phone=?, cnic=?, address=? WHERE id=?');
            $stmt->execute([$salesmanId, $name, $phone, $cnic, $address, $id]);
            echo json_encode(['success' => true, 'message' => 'Salesman updated successfully.']);
        } else {
            $chk = $pdo->prepare('SELECT id FROM salesmen WHERE salesman_id = ?');
            $chk->execute([$salesmanId]);
            if ($chk->fetch()) fail('Salesman ID already exists.');

            $stmt = $pdo->prepare('INSERT INTO salesmen (salesman_id, name, phone, cnic, address) VALUES (?,?,?,?,?)');
            $stmt->execute([$salesmanId, $name, $phone, $cnic, $address]);
            echo json_encode(['success' => true, 'message' => 'Salesman added successfully.']);
        }
    } catch (Exception $e) {
        fail('Could not save salesman: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) fail('Invalid salesman.');
    try {
        $pdo->prepare('DELETE FROM salesmen WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Salesman deleted.']);
    } catch (Exception $e) {
        fail('Could not delete: ' . $e->getMessage());
    }
    exit;
}

fail('Unknown action.');
