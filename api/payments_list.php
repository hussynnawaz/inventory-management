<?php
// GET payments / invoice list with filters, summary stats, and pagination.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/invoice_calculations.php';
require_login();

header('Content-Type: application/json');

$status   = strtolower(trim($_GET['status'] ?? 'all'));
$q        = trim($_GET['q'] ?? '');
$fromDate = trim($_GET['from_date'] ?? '');
$toDate   = trim($_GET['to_date'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = min(100, max(10, (int)($_GET['per_page'] ?? 25)));

$allowedStatus = ['all', 'paid', 'partial', 'unpaid'];
if ($status === 'partially_paid') {
    $status = 'partial';
}
if (!in_array($status, $allowedStatus, true)) {
    $status = 'all';
}

// Only accept real YYYY-MM-DD dates; swap if the range is reversed.
$isDate = static fn(string $d): bool => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
    && checkdate((int)substr($d, 5, 2), (int)substr($d, 8, 2), (int)substr($d, 0, 4));
if ($fromDate !== '' && !$isDate($fromDate)) $fromDate = '';
if ($toDate !== '' && !$isDate($toDate)) $toDate = '';
if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
    [$fromDate, $toDate] = [$toDate, $fromDate];
}

$where = ['1=1'];
$params = [];

if ($fromDate !== '') {
    $where[] = 'so.order_date >= ?';
    $params[] = $fromDate . ' 00:00:00';
}
if ($toDate !== '') {
    $where[] = 'so.order_date < DATE_ADD(?, INTERVAL 1 DAY)';
    $params[] = $toDate;
}
if ($q !== '') {
    // Invoice #, customer ID (code, or numeric customers.id), name, contact.
    $clauses = ['so.order_no LIKE ?', 'so.customer_code LIKE ?', 'so.customer_name LIKE ?', 'so.contact LIKE ?'];
    $like = '%' . addcslashes($q, '\\%_') . '%';
    array_push($params, $like, $like, $like, $like);
    if (ctype_digit($q)) {
        $clauses[] = 'so.customer_id = ?';
        $params[] = (int)$q;
    }
    $where[] = '(' . implode(' OR ', $clauses) . ')';
}

$whereSql = implode(' AND ', $where);

$baseFrom = "
    FROM sale_orders so
    LEFT JOIN (
        SELECT sale_order_id, COALESCE(SUM(amount), 0) AS amount_paid
        FROM customer_payments
        GROUP BY sale_order_id
    ) pay ON pay.sale_order_id = so.id
    WHERE {$whereSql}
";

// Status filter, computed in SQL from the invoice total and the SUM of its
// recorded payments (DECIMAL(12,2), so exact comparisons are safe). Mirrors
// invoice_payment_status() in includes/invoice_calculations.php:
//   paid    -> nothing outstanding (paid >= total, or a zero-total invoice)
//   partial -> 0 < paid < total
//   unpaid  -> paid = 0 and total > 0
$statusSql = '';
if ($status === 'paid') {
    $statusSql = ' AND (so.total <= 0 OR COALESCE(pay.amount_paid, 0) >= so.total)';
} elseif ($status === 'partial') {
    $statusSql = ' AND COALESCE(pay.amount_paid, 0) > 0 AND COALESCE(pay.amount_paid, 0) < so.total';
} elseif ($status === 'unpaid') {
    $statusSql = ' AND so.total > 0 AND COALESCE(pay.amount_paid, 0) <= 0';
}

// Summary cards respect the date range + search, but NOT the status tab, so
// the cards stay stable while switching between All / Paid / Partial / Unpaid.
$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS invoice_count,
        COALESCE(SUM(so.total), 0) AS total_sales,
        COALESCE(SUM(COALESCE(pay.amount_paid, 0)), 0) AS total_payments,
        COALESCE(SUM(GREATEST(so.total - COALESCE(pay.amount_paid, 0), 0)), 0) AS pending_payments
    {$baseFrom}
");
$summaryStmt->execute($params);
$summaryRow = $summaryStmt->fetch() ?: [];

$countStmt = $pdo->prepare("SELECT COUNT(*) {$baseFrom} {$statusSql}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare("
    SELECT
        so.id,
        so.order_no,
        so.order_date,
        so.customer_code,
        so.customer_name,
        so.contact,
        so.subtotal,
        so.sales_tax_amt,
        so.advanced_tax_amt,
        so.total,
        COALESCE(pay.amount_paid, 0) AS amount_paid
    {$baseFrom}
    {$statusSql}
    ORDER BY so.order_date DESC, so.id DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

$invoices = [];
foreach ($rows as $row) {
    $grandTotal = invoice_round($row['total']);
    $paid = invoice_round($row['amount_paid']);
    $remaining = invoice_amount_remaining($grandTotal, $paid);

    $invoices[] = [
        'id'               => (int)$row['id'],
        'order_no'         => $row['order_no'],
        'order_date'       => $row['order_date'],
        'customer_code'    => $row['customer_code'],
        'customer_name'    => $row['customer_name'],
        'contact'          => $row['contact'],
        'subtotal'         => invoice_round($row['subtotal']),
        'sales_tax_amt'    => invoice_round($row['sales_tax_amt']),
        'advanced_tax_amt' => invoice_round($row['advanced_tax_amt']),
        'grand_total'      => $grandTotal,
        'amount_paid'      => $paid,
        'amount_remaining' => $remaining,
        'payment_status'   => invoice_payment_status($grandTotal, $paid),
    ];
}

echo json_encode([
    'success' => true,
    'summary' => [
        'total_sales'      => invoice_round($summaryRow['total_sales'] ?? 0),
        'total_payments'   => invoice_round($summaryRow['total_payments'] ?? 0),
        'pending_payments' => invoice_round($summaryRow['pending_payments'] ?? 0),
        'total_invoices'   => (int)($summaryRow['invoice_count'] ?? 0),
    ],
    'pagination' => [
        'page'        => $page,
        'per_page'    => $perPage,
        'total_rows'  => $totalRows,
        'total_pages' => (int)ceil($totalRows / $perPage),
    ],
    'invoices' => $invoices,
]);
