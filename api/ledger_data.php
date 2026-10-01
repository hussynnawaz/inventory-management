<?php
// API endpoint for customer ledger data.
// GET ?customer_id=X -> returns invoices, payments, and overall balance breakdown
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_calculations.php';
require_login();

header('Content-Type: application/json');

$customerId = (int)($_GET['customer_id'] ?? 0);
if ($customerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid customer ID.']);
    exit;
}

// Fetch customer details
$custStmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$custStmt->execute([$customerId]);
$customer = $custStmt->fetch();
if (!$customer) {
    echo json_encode(['success' => false, 'message' => 'Customer not found.']);
    exit;
}

// Fetch all invoices for this customer
$invStmt = $pdo->prepare('
    SELECT so.id, so.order_no, so.order_date, so.total,
        (SELECT COALESCE(SUM(cp.amount), 0) FROM customer_payments cp WHERE cp.sale_order_id = so.id) AS paid
    FROM sale_orders so
    WHERE so.customer_id = ?
    ORDER BY so.order_date DESC, so.id DESC
');
$invStmt->execute([$customerId]);
$invoicesRaw = $invStmt->fetchAll();

$invoices = [];
$totalSales = 0;
$totalPaidOnInvoices = 0;

foreach ($invoicesRaw as $inv) {
    $paid = round((float)$inv['paid'], 2);
    $total = round((float)$inv['total'], 2);
    $outstanding = round(max(0, $total - $paid), 2);
    $totalSales += $total;
    $totalPaidOnInvoices += $paid;

    $invoices[] = [
        'id'          => (int)$inv['id'],
        'order_no'    => $inv['order_no'],
        'order_date'  => $inv['order_date'],
        'total'       => $total,
        'paid'        => $paid,
        'outstanding' => $outstanding,
    ];
}

// Fetch payment history for customer, newest Payment Date first.
// Payment Date (when the customer paid) is deliberately independent from
// created_at (when the row was typed in) and from the invoice date.
$payStmt = $pdo->prepare('
    SELECT cp.*, so.order_no
    FROM customer_payments cp
    LEFT JOIN sale_orders so ON so.id = cp.sale_order_id
    WHERE cp.customer_id = ?
    ORDER BY COALESCE(cp.payment_date, DATE(cp.created_at)) DESC, cp.id DESC
');
$payStmt->execute([$customerId]);
$payments = $payStmt->fetchAll();

// Always expose a usable payment_date, even for legacy rows written before
// the payment_date column existed.
foreach ($payments as &$pay) {
    $pay['payment_date'] = invoice_payment_date($pay);
    $pay['payment_date_display'] = invoice_fmt_date($pay['payment_date']);
}
unset($pay);

// Payments not bound to a specific invoice
$unlinkedPaid = 0;
foreach ($payments as $p) {
    if (empty($p['sale_order_id'])) {
        $unlinkedPaid += round((float)$p['amount'], 2);
    }
}

$totalSales = round($totalSales, 2);
$totalPaid = round($totalPaidOnInvoices + $unlinkedPaid, 2);
$totalOutstanding = round(max(0, $totalSales - $totalPaid), 2);

echo json_encode([
    'success'  => true,
    'customer' => $customer,
    'invoices' => $invoices,
    'payments' => $payments,
    'totals'   => [
        'invoice_count'     => count($invoices),
        'total_sales'       => $totalSales,
        'total_paid'        => $totalPaid,
        'total_outstanding' => $totalOutstanding,
    ],
]);
