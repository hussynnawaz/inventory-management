<?php
// Supplier create / update / delete handler. Expects JSON POST.
// Operations: action=save (insert/update by id), action=delete (by id), action=get_next_code
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

header('Content-Type: application/json');

function sup_fail(string $msg): void {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

/**
 * Generate next sequential supplier code: SP-MJ-01, SP-MJ-02, ...
 */
function generate_supplier_code(PDO $pdo): string {
    $stmt = $pdo->query("SELECT code FROM suppliers WHERE code LIKE 'SP-MJ-%' ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last) {
        preg_match('/SP-MJ-(\d+)$/', $last, $m);
        $next = $m ? (int)$m[1] + 1 : 1;
    } else {
        $next = 1;
    }
    return 'SP-MJ-' . str_pad($next, 2, '0', STR_PAD_LEFT);
}

function get_next_supplier_code(PDO $pdo): string {
    $stmt = $pdo->query("SELECT code FROM suppliers WHERE code LIKE 'SP-MJ-%' ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last) {
        preg_match('/SP-MJ-(\d+)$/', $last, $m);
        $next = $m ? (int)$m[1] + 1 : 1;
    } else {
        $next = 1;
    }
    return 'SP-MJ-' . str_pad($next, 2, '0', STR_PAD_LEFT);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
$action = $input['action'] ?? '';

// Handle get_next_code action
if ($action === 'get_next_code') {
    echo json_encode(['success' => true, 'code' => get_next_supplier_code($pdo)]);
    exit;
}

$fields = ['code','name','company_name','contact','phone','email','address','ntn','stn'];
foreach ($fields as $f) {
    $input[$f] = trim($input[$f] ?? '');
}

if ($action === 'save') {
    $id = (int)($input['id'] ?? 0);

    // Auto-generate code on new supplier
    if ($id === 0) {
        $input['code'] = generate_supplier_code($pdo);
    }

    if ($input['name'] === '') sup_fail('Supplier Name is required.');

    // Unique code check (exclude self on edit)
    $chk = $pdo->prepare('SELECT id FROM suppliers WHERE code = ? AND id <> ?');
    $chk->execute([$input['code'], $id]);
    if ($chk->fetch()) {
        $input['code'] = generate_supplier_code($pdo);
        $chk2 = $pdo->prepare('SELECT id FROM suppliers WHERE code = ? AND id <> ?');
        $chk2->execute([$input['code'], $id]);
        if ($chk2->fetch()) sup_fail('Could not generate unique code. Please try again.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE suppliers SET code=?, name=?, company_name=?, contact=?, phone=?, email=?, address=?, ntn=?, stn=? WHERE id=?');
            $stmt->execute([$input['code'],$input['name'],$input['company_name'],$input['contact'],$input['phone'],$input['email'],$input['address'],$input['ntn'],$input['stn'],$id]);
            echo json_encode(['success' => true, 'message' => 'Supplier updated successfully.']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO suppliers (code,name,company_name,contact,phone,email,address,ntn,stn) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$input['code'],$input['name'],$input['company_name'],$input['contact'],$input['phone'],$input['email'],$input['address'],$input['ntn'],$input['stn']]);
            echo json_encode(['success' => true, 'message' => 'Supplier added successfully.', 'supplier' => ['id' => (int)$pdo->lastInsertId(), 'code' => $input['code'], 'name' => $input['name']]]);
        }
    } catch (Exception $e) {
        sup_fail('Could not save supplier: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) sup_fail('Invalid supplier.');
    try {
        $pdo->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Supplier deleted.']);
    } catch (Exception $e) {
        sup_fail('Could not delete supplier: ' . $e->getMessage());
    }
    exit;
}

sup_fail('Unknown action.');