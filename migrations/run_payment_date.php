<?php
// One-time migration: customer_payments.payment_date
//
// Adds a real Payment Date (the date the customer actually paid) that is
// completely separate from the invoice/sale date.
//
//   * Existing rows are NOT touched or deleted: payment_date is added as
//     NULLABLE and backfilled from DATE(created_at), i.e. the day the payment
//     was entered. That is the only sensible default for legacy rows.
//   * created_at keeps its original meaning ("row was inserted"), so audit
//     trails stay intact.
require_once __DIR__ . '/../includes/db.php';

$cols = $pdo->query("SHOW COLUMNS FROM customer_payments LIKE 'payment_date'")->fetch();
if (!$cols) {
    $pdo->exec("ALTER TABLE customer_payments ADD COLUMN payment_date DATE NULL DEFAULT NULL AFTER payment_method");
    echo "OK: added customer_payments.payment_date\n";
} else {
    echo "SKIP: customer_payments.payment_date already exists\n";
}

// Index for date-range filtering on payment history (best effort).
$idx = $pdo->query("SHOW INDEX FROM customer_payments WHERE Key_name = 'idx_cust_payments_payment_date'")->fetch();
if (!$idx) {
    try {
        $pdo->exec("ALTER TABLE customer_payments ADD INDEX idx_cust_payments_payment_date (payment_date)");
        echo "OK: added idx_cust_payments_payment_date\n";
    } catch (PDOException $e) {
        echo "WARN: could not add index: " . $e->getMessage() . "\n";
    }
} else {
    echo "SKIP: idx_cust_payments_payment_date already exists\n";
}

// Backfill legacy payments: they were all entered on created_at's day.
$backfilled = $pdo->exec("UPDATE customer_payments SET payment_date = DATE(created_at) WHERE payment_date IS NULL");
echo "OK: backfilled {$backfilled} legacy payment(s) from created_at\n";

$remaining = (int)$pdo->query('SELECT COUNT(*) FROM customer_payments WHERE payment_date IS NULL')->fetchColumn();
echo $remaining === 0
    ? "Migration complete. All payments have a payment date.\n"
    : "Migration complete. {$remaining} payment(s) still NULL.\n";