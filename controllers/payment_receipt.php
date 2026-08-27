<?php
// Payment receipt PDF generator.
// GET ?receipt_no=PAY-XXXXXXXX-XXXX or ?id=<payment_id>
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_login();

use Dompdf\Dompdf;
use Dompdf\Options;

$receiptNo = trim($_GET['receipt_no'] ?? '');
$paymentId = (int)($_GET['id'] ?? 0);

if ($receiptNo === '' && $paymentId <= 0) {
    http_response_code(400);
    die('Invalid payment reference.');
}

// Fetch payment(s) — if receipt_no given, fetch all payments with same receipt_no
if ($receiptNo !== '') {
    $stmt = $pdo->prepare('SELECT cp.*, c.name AS customer_name, c.code AS customer_code, c.contact AS customer_contact,
        c.address AS customer_address, c.ntn_no AS customer_ntn, c.sales_tax_no AS customer_tax_no,
        so.order_no, so.order_date, so.total AS order_total
        FROM customer_payments cp
        LEFT JOIN customers c ON c.id = cp.customer_id
        LEFT JOIN sale_orders so ON so.id = cp.sale_order_id
        WHERE cp.receipt_no = ?
        ORDER BY cp.id ASC');
    $stmt->execute([$receiptNo]);
} else {
    $stmt = $pdo->prepare('SELECT cp.*, c.name AS customer_name, c.code AS customer_code, c.contact AS customer_contact,
        c.address AS customer_address, c.ntn_no AS customer_ntn, c.sales_tax_no AS customer_tax_no,
        so.order_no, so.order_date, so.total AS order_total
        FROM customer_payments cp
        LEFT JOIN customers c ON c.id = cp.customer_id
        LEFT JOIN sale_orders so ON so.id = cp.sale_order_id
        WHERE cp.id = ?');
    $stmt->execute([$paymentId]);
    $receiptNo = null;
}

$payments = $stmt->fetchAll();
if (empty($payments)) {
    http_response_code(404);
    die('Payment not found.');
}

$first = $payments[0];
if (!$receiptNo) $receiptNo = $first['receipt_no'];

// Customer info
$customerName    = $first['customer_name'] ?? 'Walk-in';
$customerCode    = $first['customer_code'] ?? '';
$customerContact = $first['customer_contact'] ?? '';
$customerAddress = $first['customer_address'] ?? '';
$customerNtn     = $first['customer_ntn'] ?? '';
$customerTaxNo   = $first['customer_tax_no'] ?? '';

// Totals
$totalPaid = 0;
foreach ($payments as $p) {
    $totalPaid += (float)$p['amount'];
}
$remainingBalance = (float)$first['remaining_balance'];
$previousBalance  = (float)$first['previous_balance'] + $totalPaid - (float)$first['amount'];
// Recalculate previous from first payment's stored value
$previousBalance = (float)$first['previous_balance'];

// Payment method details
$method = $first['payment_method'];
$methodLabel = $method === 'cash' ? 'Cash' : 'Bank Transfer';
$collector = $first['collector_name'] ?? '';
$txnId     = $first['transaction_id'] ?? '';
$bankChan  = $first['bank_channel'] ?? '';

// Related invoices
$relatedOrders = [];
foreach ($payments as $p) {
    if (!empty($p['order_no'])) {
        $relatedOrders[] = $p['order_no'];
    }
}
$relatedOrders = array_unique($relatedOrders);

// Logo + Stamp
$logoPath = '';
$logoFile = __DIR__ . '/../public/assets/images/mj-logo.png';
$r = @realpath($logoFile);
if ($r && file_exists($r)) $logoPath = $r;

$stampPath = '';
$stampFile = __DIR__ . '/../public/assets/images/mj-traders-stamp.png';
$r2 = @realpath($stampFile);
if ($r2 && file_exists($r2)) $stampPath = $r2;

function e2($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function fmt($v) { return number_format((float)$v, 2); }

function spellUnder100(int $n): string {
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    if ($n < 20) return $ones[$n];
    $word = $tens[intdiv($n, 10)];
    if ($n % 10) $word .= ' ' . $ones[$n % 10];
    return $word;
}

function spellUnder1000(int $n): string {
    if ($n === 0) return '';
    $h = intdiv($n, 100); $r = $n % 100;
    $w = $h ? spellUnder100($h) . ' Hundred' : '';
    if ($r) $w .= ($w ? ' ' : '') . spellUnder100($r);
    return $w;
}

function numberToWords(int $n): string {
    if ($n === 0) return 'Zero';
    $p = [];
    $c = intdiv($n, 10000000); $n %= 10000000;
    $l = intdiv($n, 100000); $n %= 100000;
    $t = intdiv($n, 1000); $n %= 1000;
    if ($c) $p[] = spellUnder1000($c) . ' Crore';
    if ($l) $p[] = spellUnder1000($l) . ' Lakh';
    if ($t) $p[] = spellUnder1000($t) . ' Thousand';
    if ($n) $p[] = spellUnder1000($n);
    return implode(' ', $p);
}

function amountInWords(float $amount): string {
    $rupees = (int) floor(abs($amount));
    $paisa = (int) round((abs($amount) - $rupees) * 100);
    if ($paisa >= 100) { $rupees += 1; $paisa = 0; }
    $words = numberToWords($rupees) . ' Rupee' . ($rupees === 1 ? '' : 's');
    if ($paisa > 0) $words .= ' and ' . numberToWords($paisa) . ' Paisa';
    return $words . ' Only';
}

function infoRow(string $label, string $value): string {
    return "<tr>
        <td style='padding:3px 0;width:100px;color:#555;font-size:11px;vertical-align:top;'>{$label}</td>
        <td style='padding:3px 0;color:#000;font-size:11px;font-weight:bold;vertical-align:top;'>{$value}</td>
    </tr>";
}

// Build invoice rows
$invoiceRows = '';
if (!empty($relatedOrders)) {
    foreach ($relatedOrders as $on) {
        $invoiceRows .= "<tr>
            <td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;font-size:11px;'>".e2($on)."</td>
        </tr>";
    }
} else {
    $invoiceRows = "<tr><td style='padding:6px 8px;border-bottom:1px solid #e5e7eb;font-size:11px;color:#999;'>General Payment</td></tr>";
}

// Payment detail rows
$paymentDetailRows = '';
if ($method === 'cash') {
    $paymentDetailRows = "
        ".infoRow('Collector Name', e2($collector))."
        ".infoRow('Payment Mode', 'Cash')."
    ";
} else {
    $paymentDetailRows = "
        ".infoRow('Banking Channel', e2($bankChan))."
        ".infoRow('Transaction ID', e2($txnId))."
        ".infoRow('Payment Mode', 'Bank Transfer')."
    ";
}

$logoBlock = $logoPath
    ? "<img src='{$logoPath}' style='width:130px;height:auto;display:block;' />"
    : "<div style='width:130px;height:130px;border:1px solid #000;font-size:28px;font-weight:bold;text-align:center;line-height:130px;'>MJ</div>";

$stampBlock = '';
if ($stampPath) {
    $stampBlock = "
    <table width='100%' cellpadding='0' cellspacing='0' style='margin-top:30px;'>
        <tr>
            <td style='width:55%;vertical-align:bottom;padding-right:20px;'>
                <div style='font-size:10px;color:#555;line-height:1.7;'>
                    <strong style='font-size:11px;color:#333;'>Terms &amp; Conditions</strong><br>
                    This is a payment receipt for the amount received.<br>
                    For any queries, please contact our accounts department.<br>
                    This is a computer-generated document.
                </div>
            </td>
            <td style='width:45%;text-align:center;vertical-align:bottom;'>
                <img src='{$stampPath}' style='width:90px;height:auto;margin-bottom:6px;' />
                <div style='border-top:1px solid #000;width:150px;margin:0 auto;'></div>
                <div style='font-size:10px;color:#000;margin-top:5px;font-weight:bold;'>Authorized Signature</div>
            </td>
        </tr>
    </table>";
}

$html = "
<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
    @page { margin: 22mm 24mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; color: #000; font-size: 11px; line-height: 1.4; padding: 5mm 4mm; }
</style>
</head>
<body>

<!-- HEADER -->
<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:12px;'>
<tr>
    <td style='width:140px;vertical-align:top;padding-right:14px;'>
        {$logoBlock}
    </td>
    <td style='vertical-align:top;padding-right:10px;'>
        <div style='font-size:18px;font-weight:bold;color:#000;line-height:1.2;margin-bottom:3px;'>MJ Traders</div>
        <div style='font-size:10px;color:#555;'>Murree Road, Dhamtor, Abbottabad</div>
        <div style='font-size:10px;color:#555;'>Tel: 0315-4999667</div>
        <div style='font-size:9px;color:#555;margin-top:2px;'>Wholesale &amp; Distribution</div>
    </td>
    <td style='width:220px;vertical-align:top;text-align:right;'>
        <div style='font-size:14px;font-weight:bold;color:#16a34a;margin-bottom:8px;'>PAYMENT RECEIPT</div>
        <table cellpadding='0' cellspacing='0' style='margin-left:auto;font-size:10px;'>
            <tr>
                <td style='padding:2px 8px 2px 0;text-align:right;color:#555;'>Receipt No:</td>
                <td style='padding:2px 0;font-weight:bold;color:#000;white-space:nowrap;'>".e2($receiptNo)."</td>
            </tr>
            <tr>
                <td style='padding:2px 8px 2px 0;text-align:right;color:#555;'>Date:</td>
                <td style='padding:2px 0;color:#000;white-space:nowrap;'>".e2(date('d M Y, h:i A', strtotime($first['created_at'])))."</td>
            </tr>
        </table>
    </td>
</tr>
</table>

<div style='border-top:2px solid #16a34a;margin-bottom:14px;'></div>

<!-- CUSTOMER & PAYMENT INFO -->
<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:14px;'>
<tr>
    <td style='width:48%;vertical-align:top;padding-right:14px;'>
        <div style='font-size:11px;font-weight:bold;margin-bottom:6px;border-bottom:1px solid #16a34a;padding-bottom:3px;color:#16a34a;'>Customer Details</div>
        <table width='100%' cellpadding='0' cellspacing='0'>
            ".infoRow('Code', e2($customerCode ?: '-'))."
            ".infoRow('Name', e2($customerName))."
            ".infoRow('Contact', e2($customerContact ?: '-'))."
            ".infoRow('Address', e2($customerAddress ?: '-'))."
        </table>
    </td>
    <td style='width:4%;'></td>
    <td style='width:48%;vertical-align:top;'>
        <div style='font-size:11px;font-weight:bold;margin-bottom:6px;border-bottom:1px solid #16a34a;padding-bottom:3px;color:#16a34a;'>Payment Details</div>
        <table width='100%' cellpadding='0' cellspacing='0'>
            ".infoRow('Payment Method', e2($methodLabel))."
            {$paymentDetailRows}
            ".infoRow('Amount Paid', '<span style=\"color:#16a34a;font-size:13px;\">Rs '.fmt($totalPaid).'</span>')."
        </table>
    </td>
</tr>
</table>

<!-- RELATED INVOICES -->
".(!empty($relatedOrders) ? "
<div style='font-size:11px;font-weight:bold;margin-bottom:6px;color:#333;'>Related Invoice(s)</div>
<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e5e7eb;margin-bottom:14px;'>
    <thead>
        <tr>
            <th style='padding:6px 8px;border-bottom:1px solid #e5e7eb;text-align:left;font-size:10px;font-weight:bold;color:#555;background:#f9fafb;'>Invoice Number</th>
        </tr>
    </thead>
    <tbody>
        {$invoiceRows}
    </tbody>
</table>
" : "")."

<!-- BALANCE SUMMARY -->
<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:14px;'>
<tr>
    <td style='width:50%;vertical-align:top;padding-right:14px;'>
        <div style='border:1px solid #e5e7eb;padding:10px 14px;border-radius:4px;'>
            <div style='font-size:10px;color:#555;margin-bottom:4px;'>Amount in Words</div>
            <div style='font-size:11px;font-weight:bold;'>".e2(amountInWords($totalPaid))."</div>
        </div>
    </td>
    <td style='width:50%;vertical-align:top;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #e5e7eb;'>
            <tr>
                <td style='padding:6px 10px;font-size:10px;color:#555;border-bottom:1px solid #e5e7eb;'>Previous Balance</td>
                <td style='padding:6px 10px;text-align:right;font-size:11px;border-bottom:1px solid #e5e7eb;'>Rs ".fmt($previousBalance)."</td>
            </tr>
            <tr>
                <td style='padding:6px 10px;font-size:10px;color:#555;border-bottom:1px solid #e5e7eb;'>Amount Paid</td>
                <td style='padding:6px 10px;text-align:right;font-size:11px;color:#16a34a;font-weight:bold;border-bottom:1px solid #e5e7eb;'>- Rs ".fmt($totalPaid)."</td>
            </tr>
            <tr>
                <td style='padding:8px 10px;font-size:11px;font-weight:bold;'>Remaining Balance</td>
                <td style='padding:8px 10px;text-align:right;font-size:12px;font-weight:bold;color:".($remainingBalance > 0 ? '#dc2626' : '#16a34a').";'>Rs ".fmt($remainingBalance)."</td>
            </tr>
        </table>
    </td>
</tr>
</table>

{$stampBlock}

<!-- FOOTER -->
<div style='margin-top:24px;border-top:1px solid #e5e7eb;padding-top:10px;text-align:center;'>
    <div style='font-size:10px;font-weight:bold;margin-bottom:2px;'>Thank you for your payment</div>
    <div style='font-size:8px;color:#999;'>MJ Traders Inventory System &mdash; Generated ".date('d M Y, h:i A')."</div>
</div>

</body>
</html>";

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');
$options->set('chroot', [realpath(__DIR__ . '/../')]);
$options->set('isFontSubsettingEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfContent = $dompdf->output();

if (ob_get_length()) ob_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $receiptNo . '.pdf"');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
