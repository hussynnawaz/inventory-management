<?php
// Migration script for purchases and returns tables and columns
require_once __DIR__ . '/../includes/db.php';

echo "Running migrations for purchases and returns...\n";

// 1. Create suppliers table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS suppliers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    phone      VARCHAR(30)  DEFAULT '',
    email      VARCHAR(120) DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "OK: suppliers table check done.\n";

// 2. Create purchases table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(50) NOT NULL UNIQUE,
    supplier_id  INT NULL,
    user_id      INT NOT NULL,
    total        DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "OK: purchases table check done.\n";

// 3. Create purchase_items table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS purchase_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    purchase_id  INT NOT NULL,
    product_id   INT NOT NULL,
    quantity     INT NOT NULL,
    cost         DECIMAL(12,2) NOT NULL,
    line_total   DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "OK: purchase_items table check done.\n";

// Add line_total column to purchase_items if missing
$piCols = $pdo->query("SHOW COLUMNS FROM purchase_items")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('line_total', $piCols, true)) {
    $pdo->exec("ALTER TABLE purchase_items ADD COLUMN line_total DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER cost");
    echo "OK: added line_total column to purchase_items.\n";
}

// 4. Create returns table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS returns (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    return_no    VARCHAR(50) NOT NULL UNIQUE,
    sale_order_id INT NULL,
    product_id   INT NOT NULL,
    quantity     INT NOT NULL,
    refund_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    line_total   DECIMAL(12,2) NOT NULL DEFAULT 0,
    reason       VARCHAR(255) DEFAULT '',
    user_id      INT NOT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "OK: returns table check done.\n";

// Add sale_order_id, refund_price, line_total to returns if missing
$rCols = $pdo->query("SHOW COLUMNS FROM returns")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('sale_order_id', $rCols, true)) {
    $pdo->exec("ALTER TABLE returns ADD COLUMN sale_order_id INT NULL AFTER return_no");
    echo "OK: added sale_order_id column to returns.\n";
}
if (!in_array('refund_price', $rCols, true)) {
    $pdo->exec("ALTER TABLE returns ADD COLUMN refund_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER quantity");
    echo "OK: added refund_price column to returns.\n";
}
if (!in_array('line_total', $rCols, true)) {
    $pdo->exec("ALTER TABLE returns ADD COLUMN line_total DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER refund_price");
    echo "OK: added line_total column to returns.\n";
}

echo "All migrations completed successfully!\n";
