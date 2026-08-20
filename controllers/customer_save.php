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

/**
 * Generate next sequential customer code: CR-MJ-01, CR-MJ-02, ...
 */
function generate_customer_code(PDO $pdo): string {
    $stmt = $pdo->query("SELECT code FROM customers WHERE code LIKE 'CR-MJ-%' ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last) {
        // Extract the numeric part after the last dash
        preg_match('/CR-MJ-(\d+)$/', $last, $m);
        $next = $m ? (int)$m[1] + 1 : 1;
    } else {
        $next = 1;
    }
    return 'CR-MJ-' . str_pad($next, 2, '0', STR_PAD_LEFT);
}

/**
 * Get the next code that will be assigned (for display purposes).
 */
function get_next_customer_code(PDO $pdo): string {
    $stmt = $pdo->query("SELECT code FROM customers WHERE code LIKE 'CR-MJ-%' ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    if ($last) {
        preg_match('/CR-MJ-(\d+)$/', $last, $m);
        $next = $m ? (int)$m[1] + 1 : 1;
    } else {
        $next = 1;
    }
    return 'CR-MJ-' . str_pad($next, 2, '0', STR_PAD_LEFT);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
$action = $input['action'] ?? '';

// Handle get_next_code action
if ($action === 'get_next_code') {
    echo json_encode(['success' => true, 'code' => get_next_customer_code($pdo)]);
    exit;
}

$fields = ['code','name','contact','delivery_route','ntn_no','sales_tax_no','cnic','address'];
foreach ($fields as $f) {
    $input[$f] = trim($input[$f] ?? '');
}

if ($action === 'save') {
    $id = (int)($input['id'] ?? 0);

    // Auto-generate code on new customer
    if ($id === 0) {
        $input['code'] = generate_customer_code($pdo);
    }

    if ($input['name'] === '') cust_fail('Customer Name is required.');

    // Unique code check (exclude self on edit)
    $chk = $pdo->prepare('SELECT id FROM customers WHERE code = ? AND id <> ?');
    $chk->execute([$input['code'], $id]);
    if ($chk->fetch()) {
        // Code collision — regenerate
        $input['code'] = generate_customer_code($pdo);
        $chk2 = $pdo->prepare('SELECT id FROM customers WHERE code = ? AND id <> ?');
        $chk2->execute([$input['code'], $id]);
        if ($chk2->fetch()) cust_fail('Could not generate unique code. Please try again.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE customers SET code=?, name=?, contact=?, delivery_route=?, ntn_no=?, sales_tax_no=?, cnic=?, address=? WHERE id=?');
            $stmt->execute([$input['code'],$input['name'],$input['contact'],$input['delivery_route'],$input['ntn_no'],$input['sales_tax_no'],$input['cnic'],$input['address'],$id]);
            echo json_encode(['success' => true, 'message' => 'Customer updated successfully.']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO customers (code,name,contact,delivery_route,salesman,ntn_no,sales_tax_no,cnic,address) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$input['code'],$input['name'],$input['contact'],$input['delivery_route'],$input['salesman'],$input['ntn_no'],$input['sales_tax_no'],$input['cnic'],$input['address']]);
            echo json_encode(['success' => true, 'message' => 'Customer added successfully.', 'code' => $input['code']]);
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
