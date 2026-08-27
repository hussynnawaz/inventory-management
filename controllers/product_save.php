<?php
// Product CRUD + inventory update handler. Expects JSON POST.
// Operations: action=save (insert/update), action=update_stock (adjust qty), action=delete
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

header('Content-Type: application/json');

function fail(string $msg): void {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

/**
 * Generate SKU from product name: MJ-{INITIALS}-{NUMBER}
 * "Nestle Mineral Water" → "MJ-NMW-01"
 */
function generate_sku(PDO $pdo, string $name): string {
    $stopWords = ['a','an','the','and','or','of','for','in','on','at','to','by','with','from'];
    $words = preg_split('/[\s\-_]+/', trim($name));
    $initials = '';
    foreach ($words as $w) {
        $w = strtolower($w);
        if (in_array($w, $stopWords, true) || $w === '') continue;
        $initials .= strtoupper($w[0]);
        if (strlen($initials) >= 3) break;
    }
    if (strlen($initials) < 1) $initials = 'PRD';
    $prefix = 'MJ-' . $initials . '-';
    $stmt = $pdo->prepare("SELECT sku FROM products WHERE sku LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetchColumn();
    if ($last && preg_match('/-(\d+)$/', $last, $m)) {
        $next = (int)$m[1] + 1;
    } else {
        $next = 1;
    }
    return $prefix . str_pad($next, 2, '0', STR_PAD_LEFT);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
$action = $input['action'] ?? '';

// ── Save (create or update product) ──
if ($action === 'save') {
    $id        = (int)($input['id'] ?? 0);
    $name      = trim($input['name'] ?? '');
    $sku       = trim($input['sku'] ?? '');
    $category  = trim($input['category'] ?? '');
    $desc      = trim($input['description'] ?? '');
    $costPrice = (float)($input['cost_price'] ?? 0);
    $salePrice = (float)($input['sale_price'] ?? 0);
    $quantity  = (int)($input['quantity'] ?? 0);

    if ($name === '') fail('Product Name is required.');
    if ($costPrice < 0) fail('Purchase Price cannot be negative.');
    if ($salePrice < 0) fail('Selling Price cannot be negative.');
    if ($quantity < 0)  fail('Initial Stock cannot be negative.');

    // Auto-generate SKU on new product, or on edit if SKU was cleared
    if ($id === 0 || $sku === '') {
        $sku = generate_sku($pdo, $name);
    }

    // Unique SKU check (regenerate if collision on new product)
    $chk = $pdo->prepare('SELECT id FROM products WHERE sku = ? AND id <> ?');
    $chk->execute([$sku, $id]);
    if ($chk->fetch()) {
        $sku = generate_sku($pdo, $name);
        $chk2 = $pdo->prepare('SELECT id FROM products WHERE sku = ? AND id <> ?');
        $chk2->execute([$sku, $id]);
        if ($chk2->fetch()) fail('Could not generate unique SKU. Please try again.');
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE products SET name=?, sku=?, category=?, description=?, cost_price=?, sale_price=?, quantity=? WHERE id=?');
            $stmt->execute([$name, $sku, $category, $desc, $costPrice, $salePrice, $quantity, $id]);
            echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO products (name, sku, category, description, cost_price, sale_price, quantity) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$name, $sku, $category, $desc, $costPrice, $salePrice, $quantity]);
            echo json_encode(['success' => true, 'message' => 'Product added successfully.', 'product_id' => (int)$pdo->lastInsertId()]);
        }
    } catch (Exception $e) {
        fail('Could not save product: ' . $e->getMessage());
    }
    exit;
}

// ── Update Stock (adjust quantity) ──
if ($action === 'update_stock') {
    $id      = (int)($input['id'] ?? 0);
    $mode    = $input['mode'] ?? 'set'; // 'set', 'add', 'subtract'
    $qty     = (int)($input['quantity'] ?? 0);

    if ($id <= 0)  fail('Invalid product.');
    if ($qty < 0)  fail('Quantity cannot be negative.');

    // Fetch current quantity
    $row = $pdo->prepare('SELECT quantity FROM products WHERE id = ?');
    $row->execute([$id]);
    $product = $row->fetch();
    if (!$product) fail('Product not found.');

    $current = (int)$product['quantity'];
    switch ($mode) {
        case 'add':     $newQty = $current + $qty; break;
        case 'subtract': $newQty = max(0, $current - $qty); break;
        default:        $newQty = $qty; break;
    }

    try {
        $stmt = $pdo->prepare('UPDATE products SET quantity = ? WHERE id = ?');
        $stmt->execute([$newQty, $id]);
        echo json_encode(['success' => true, 'message' => "Stock updated. New quantity: $newQty", 'new_quantity' => $newQty]);
    } catch (Exception $e) {
        fail('Could not update stock: ' . $e->getMessage());
    }
    exit;
}

// ── Delete ──
if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) fail('Invalid product.');

    // Check if product has sale items
    $chk = $pdo->prepare('SELECT COUNT(*) FROM sale_items WHERE product_id = ?');
    $chk->execute([$id]);
    if ((int)$chk->fetchColumn() > 0) {
        fail('Cannot delete product — it has associated sales records.');
    }

    $chk2 = $pdo->prepare('SELECT COUNT(*) FROM sale_order_items WHERE product_id = ?');
    $chk2->execute([$id]);
    if ((int)$chk2->fetchColumn() > 0) {
        fail('Cannot delete product — it has associated sale order records.');
    }

    try {
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Product deleted.']);
    } catch (Exception $e) {
        fail('Could not delete product: ' . $e->getMessage());
    }
    exit;
}

fail('Unknown action.');
