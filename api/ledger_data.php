<?php
// API endpoint for ledger data.
// GET ?customer_id=X -> returns invoices with paid/outstanding + payments
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

header('Content-Type: application/json');

$customerId = (int)($_GET['customer_id'] ?? 0);
if ($customerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID.']);
    exit;
}

// Fetch all invoices for this customer
$invStmt = $pdo->prepare('
    SELECT so.id, so.order_no, so.order_date, so.total,
        (SELECT COALESCE(SUM(cp.amount), 0) FROM customer_payments cp WHERE cp.sale_order_id = so.id) AS paid
    FROM sale_orders so
    WHERE so.customer_id = ?
    ORDER BY so.order_date DESC
');
$invStmt->execute([$customerId]);
$invoicesRaw = $invStmt->fetchAll();

$invoices = [];
$totalSales = 0;
$totalPaid = 0;

foreach ($invoicesRaw as $inv) {
    $paid = (float)$inv['paid'];
    $total = (float)$inv['total'];
    $outstanding = round($total - $paid, 2);
    $totalSales += $total;
    $totalPaid += $paid;

    $invoices[] = [
        'id'          => (int)$inv['id'],
        'order_no'    => $inv['order_no'],
        'order_date'  => $inv['order_date'],
        'total'       => $total,
        'paid'        => $paid,
        'outstanding' => $outstanding,
    ];
}

// Fetch payment history
$payStmt = $pdo->prepare('
    SELECT cp.*, so.order_no
    FROM customer_payments cp
    LEFT JOIN sale_orders so ON so.id = cp.sale_order_id
    WHERE cp.customer_id = ?
    ORDER BY cp.created_at DESC
');
$payStmt->execute([$customerId]);
$payments = $payStmt->fetchAll();

// If there are payments not linked to specific invoices, include them in totals
$generalPaid = 0;
foreach ($payments as $p) {
    if (empty($p['sale_order_id'])) {
        $generalPaid += (float)$p['amount'];
    }
}

echo json_encode([
    'success'  => true,
    'invoices' => $invoices,
    'payments' => $payments,
    'totals'   => [
        'invoice_count' => count($invoices),
        'total_sales'   => round($totalSales, 2),
        'total_paid'    => round($totalPaid + $generalPaid, 2),
    ],
]);
