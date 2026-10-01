<?php
// One-time migration: salesmen table + sale_orders salesman columns
require_once __DIR__ . '/../includes/db.php';

$steps = [
    "CREATE TABLE IF NOT EXISTS salesmen (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        code       VARCHAR(30)  NOT NULL UNIQUE,
        name       VARCHAR(120) NOT NULL,
        phone      VARCHAR(30)  DEFAULT '',
        cnic       VARCHAR(20)  DEFAULT '',
        email      VARCHAR(120) DEFAULT '',
        address    TEXT,
        notes      VARCHAR(255) DEFAULT '',
        created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

$alterCols = [
    'salesman_id'    => "ALTER TABLE sale_orders ADD COLUMN salesman_id INT NULL AFTER delivery_route",
    'salesman_code'  => "ALTER TABLE sale_orders ADD COLUMN salesman_code VARCHAR(30) DEFAULT '' AFTER salesman_id",
    'salesman_phone' => "ALTER TABLE sale_orders ADD COLUMN salesman_phone VARCHAR(30) DEFAULT '' AFTER salesman",
    'salesman_cnic'  => "ALTER TABLE sale_orders ADD COLUMN salesman_cnic VARCHAR(20) DEFAULT '' AFTER salesman_phone",
];

foreach ($steps as $sql) {
    $pdo->exec($sql);
    echo "OK: salesmen table\n";
}

$existing = $pdo->query("SHOW COLUMNS FROM sale_orders")->fetchAll(PDO::FETCH_COLUMN);
foreach ($alterCols as $col => $sql) {
    if (in_array($col, $existing, true)) {
        echo "SKIP: {$col} already exists\n";
        continue;
    }
    $pdo->exec($sql);
    echo "OK: added {$col}\n";
}

echo "Migration complete.\n";
