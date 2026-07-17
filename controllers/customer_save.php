<?php
// Customer create / update / delete handler. Expects JSON POST.
// Operations: action=save (insert/update by id), action=delete (by id)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

header('Content-Type: application/json');

function cust_fail(string $msg): void {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
$action = $input['action'] ?? '';

$fields = ['code','name','contact','delivery_route','salesman','ntn_no','sales_tax_no','cnic','address'];
foreach ($fields as $f) {
    $input[$f] = trim($input[$f] ?? '');
}

if ($action === 'save') {
    if ($input['code'] === '') cust_fail('Customer Code is required.');
    if ($input['name'] === '') cust_fail('Customer Name is required.');

    // Unique code check (exclude self on edit)
    $id = (int)($input['id'] ?? 0);
    $chk = $pdo->prepare('SELECT id FROM customers WHERE code = ? AND id <> ?');
    $chk->execute([$input['code'], $id]);
    if ($chk->fetch()) cust_fail('Customer Code already exists.');

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE customers SET code=?, name=?, contact=?, delivery_route=?, salesman=?, ntn_no=?, sales_tax_no=?, cnic=?, address=? WHERE id=?');
            $stmt->execute([$input['code'],$input['name'],$input['contact'],$input['delivery_route'],$input['salesman'],$input['ntn_no'],$input['sales_tax_no'],$input['cnic'],$input['address'],$id]);
            echo json_encode(['success' => true, 'message' => 'Customer updated successfully.']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO customers (code,name,contact,delivery_route,salesman,ntn_no,sales_tax_no,cnic,address) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$input['code'],$input['name'],$input['contact'],$input['delivery_route'],$input['salesman'],$input['ntn_no'],$input['sales_tax_no'],$input['cnic'],$input['address']]);
            echo json_encode(['success' => true, 'message' => 'Customer added successfully.']);
        }
    } catch (Exception $e) {
        cust_fail('Could not save customer: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) cust_fail('Invalid customer.');
    try {
        $pdo->prepare('DELETE FROM customers WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Customer deleted.']);
    } catch (Exception $e) {
        cust_fail('Could not delete customer: ' . $e->getMessage());
    }
    exit;
}

cust_fail('Unknown action.');
