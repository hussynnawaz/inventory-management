<?php
// POST handler for recording customer payments.
// Input JSON: customer_id, sale_order_id (optional), amount, payment_method,
//   collector_name (cash), transaction_id + bank_channel (bank)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

header('Content-Type: application/json');

function pay_fail(string $msg): void {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    pay_fail('Invalid request method.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$customerId   = (int)($input['customer_id'] ?? 0);
$saleOrderId  = !empty($input['sale_order_id']) ? (int)$input['sale_order_id'] : null;
$amount       = round((float)($input['amount'] ?? 0), 2);
$method       = strtolower(trim($input['payment_method'] ?? 'cash'));
$collector    = trim($input['collector_name'] ?? '');
$txnId        = trim($input['transaction_id'] ?? '');
$bankChannel  = trim($input['bank_channel'] ?? '');
$notes        = trim($input['notes'] ?? '');

if ($customerId <= 0) pay_fail('Invalid customer.');
if ($amount <= 0) pay_fail('Payment amount must be greater than zero.');
if (!in_array($method, ['cash', 'bank'], true)) pay_fail('Invalid payment method.');
if ($method === 'cash' && $collector === '') pay_fail('Collector name is required for cash payments.');
if ($method === 'bank' && ($txnId === '' || $bankChannel === '')) pay_fail('Transaction ID and banking channel are required for bank payments.');

try {
    $pdo->beginTransaction();

    // Generate receipt number: PAY-YYYYMMDD-XXXX
    $receiptBase = 'PAY-' . date('Ymd') . '-';
    $count = (int)$pdo->query('SELECT COUNT(*) FROM customer_payments WHERE receipt_no LIKE "' . $receiptBase . '%"')->fetchColumn();
    $receiptNo = $receiptBase . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    // Ensure unique receipt_no
    $chk = $pdo->prepare('SELECT id FROM customer_payments WHERE receipt_no = ?');
    $chk->execute([$receiptNo]);
    if ($chk->fetch()) {
        $count++;
        $receiptNo = $receiptBase . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Get current balance for the specific invoice or customer total
    $previousBalance = 0;
    if ($saleOrderId) {
        // Calculate outstanding for this specific invoice
        $stmt = $pdo->prepare('SELECT total FROM sale_orders WHERE id = ?');
        $stmt->execute([$saleOrderId]);
        $orderTotal = (float)$stmt->fetchColumn();

        $paidStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE sale_order_id = ?');
        $paidStmt->execute([$saleOrderId]);
        $alreadyPaid = (float)$paidStmt->fetchColumn();

        $previousBalance = $orderTotal - $alreadyPaid;
    } else {
        // Calculate total outstanding for customer across all invoices
        $totalStmt = $pdo->prepare('SELECT COALESCE(SUM(total), 0) FROM sale_orders WHERE customer_id = ?');
        $totalStmt->execute([$customerId]);
        $totalOrders = (float)$totalStmt->fetchColumn();

        $totalPaidStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM customer_payments WHERE customer_id = ?');
        $totalPaidStmt->execute([$customerId]);
        $totalPaid = (float)$totalPaidStmt->fetchColumn();

        $previousBalance = $totalOrders - $totalPaid;
    }

    if ($amount > round($previousBalance + 0.01, 2)) {
        $pdo->rollBack();
        pay_fail('Payment amount (Rs ' . number_format($amount, 2) . ') exceeds outstanding balance (Rs ' . number_format($previousBalance, 2) . ').');
    }

    $remainingBalance = round($previousBalance - $amount, 2);
    if ($remainingBalance < 0) $remainingBalance = 0;

    // Insert payment
    $stmt = $pdo->prepare('INSERT INTO customer_payments
        (receipt_no, customer_id, sale_order_id, payment_method, amount, previous_balance, remaining_balance,
         collector_name, transaction_id, bank_channel, notes, user_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $receiptNo, $customerId, $saleOrderId, $method, $amount,
        $previousBalance, $remainingBalance,
        $method === 'cash' ? $collector : null,
        $method === 'bank' ? $txnId : null,
        $method === 'bank' ? $bankChannel : null,
        $notes ?: null,
        current_user()['id']
    ]);

    $paymentId = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success'          => true,
        'message'          => 'Payment recorded successfully.',
        'payment_id'       => $paymentId,
        'receipt_no'       => $receiptNo,
        'amount'           => $amount,
        'previous_balance' => $previousBalance,
        'remaining_balance'=> $remainingBalance,
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    pay_fail('Could not save payment: ' . $e->getMessage());
}
