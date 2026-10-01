<?php
require __DIR__ . '/../includes/db.php';
echo implode(', ', $pdo->query('SHOW COLUMNS FROM sale_orders')->fetchAll(PDO::FETCH_COLUMN));
