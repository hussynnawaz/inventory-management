<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_login();

use Dompdf\Dompdf;
use Dompdf\Options;

// Accept return_no param — ONE PDF for all items in the same return
$retNo = trim($_GET['return_no'] ?? '');
if ($retNo === '') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); die('Invalid return.'); }
    $lookup = $pdo->prepare('SELECT return_no FROM returns WHERE id = ?');
    $lookup->execute([$id]);
    $retNo = $lookup->fetchColumn();
    if (!$retNo) { http_response_code(404); die('Return not found.'); }
    header('Location: /controllers/return_pdf.php?return_no=' . urlencode($retNo));
    exit;
}

$allItems = $pdo->prepare('
    SELECT r.*, p.name AS product_name, p.sku
    FROM returns r
    LEFT JOIN products p ON p.id = r.product_id
    WHERE r.return_no = ?
    ORDER BY r.id ASC
');
$allItems->execute([$retNo]);
$allItems = $allItems->fetchAll();

if (empty($allItems)) { http_response_code(404); die('Return not found.'); }

$first = $allItems[0];

$orderInfo = null;
if (!empty($first['sale_order_id'])) {
    $oStmt = $pdo->prepare('SELECT order_no, customer_name, contact, address FROM sale_orders WHERE id = ?');
    $oStmt->execute([(int)$first['sale_order_id']]);
    $orderInfo = $oStmt->fetch();
}

// Logo + Stamp
$logoPath = '';
$logoFile = __DIR__ . '/../public/assets/images/mj-logo.png';
$r = realpath($logoFile);
if ($r && file_exists($r)) $logoPath = $r;

$stampPath = '';
$stampFile = __DIR__ . '/../public/assets/images/mj-traders-stamp.png';
$r2 = realpath($stampFile);
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
        <td style='padding:3px 0;width:88px;color:#000;font-size:11px;vertical-align:top;'>{$label}</td>
        <td style='padding:3px 0;color:#000;font-size:11px;font-weight:bold;vertical-align:top;'>{$value}</td>
    </tr>";
}

// Build rows
$totalRefund = 0;
$rows = '';
$i = 1;
foreach ($allItems as $it) {
    $lineTotal = (float)$it['line_total'];
    $totalRefund += $lineTotal;
    $rows .= "
    <tr>
        <td style='padding:7px 6px;border-bottom:1px solid #000;text-align:center;font-size:11px;'>{$i}</td>
        <td style='padding:7px 8px;border-bottom:1px solid #000;font-size:11px;font-weight:bold;'>".e2($it['product_name'] ?: 'Unknown')."</td>
        <td style='padding:7px 6px;border-bottom:1px solid #000;text-align:center;font-size:11px;'>".e2($it['sku'] ?? '-')."</td>
        <td style='padding:7px 6px;border-bottom:1px solid #000;text-align:center;font-size:11px;'>".$it['quantity']."</td>
        <td style='padding:7px 8px;border-bottom:1px solid #000;text-align:right;font-size:11px;'>Rs ".fmt($it['refund_price'])."</td>
        <td style='padding:7px 8px;border-bottom:1px solid #000;text-align:right;font-size:11px;font-weight:bold;'>Rs ".fmt($lineTotal)."</td>
        <td style='padding:7px 8px;border-bottom:1px solid #000;font-size:10px;'>".e2($it['reason'] ?: '-')."</td>
    </tr>";
    $i++;
}

$amountWords = e2(amountInWords($totalRefund));

$logoBlock = $logoPath
    ? "<img src='{$logoPath}' style='width:150px;height:auto;display:block;' />"
    : "<div style='width:150px;height:150px;border:1px solid #000;font-size:32px;font-weight:bold;text-align:center;line-height:150px;'>MJ</div>";

$stampBlock = '';
if ($stampPath) {
    $stampBlock = "
    <table width='100%' cellpadding='0' cellspacing='0' style='margin-top:30px;'>
        <tr>
            <td style='width:55%;vertical-align:bottom;padding-right:20px;'>
                <div style='font-size:10px;color:#000;line-height:1.7;'>
                    <strong style='font-size:11px;color:#c00;'>Terms &amp; Conditions</strong><br>
                    Goods returned must be in original condition.<br>
                    Refund processed as per company policy.<br>
                    This is a computer-generated return voucher.
                </div>
            </td>
            <td style='width:45%;text-align:center;vertical-align:bottom;'>
                <img src='{$stampPath}' style='width:100px;height:auto;margin-bottom:6px;' />
                <div style='border-top:1px solid #c00;width:170px;margin:0 auto;'></div>
                <div style='font-size:10px;color:#c00;margin-top:5px;font-weight:bold;'>Authorized Signature</div>
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
    @page { margin: 24mm 26mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; color: #000; font-size: 11px; line-height: 1.4; padding: 6mm 4mm; }
</style>
</head>
<body>

<!-- HEADER -->
<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:14px;'>
<tr>
    <td style='width:150px;vertical-align:top;padding-right:16px;'>
        {$logoBlock}
    </td>
    <td style='vertical-align:top;padding-right:12px;'>
        <div style='font-size:20px;font-weight:bold;color:#000;line-height:1.2;margin-bottom:4px;'>MJ Traders</div>
        <div style='font-size:11px;color:#000;'>Murree Road, Dhamtor, Abbottabad</div>
        <div style='font-size:11px;color:#000;'>Tel: 0315-4999667</div>
        <div style='font-size:10px;color:#000;margin-top:2px;'>Wholesale &amp; Distribution</div>
    </td>
    <td style='width:240px;vertical-align:top;text-align:right;'>
        <div style='font-size:14px;font-weight:bold;color:#c00;margin-bottom:10px;'>RETURN VOUCHER</div>
        <table cellpadding='0' cellspacing='0' style='margin-left:auto;font-size:11px;'>
            <tr>
                <td style='padding:2px 10px 2px 0;text-align:right;color:#c00;'>Return No:</td>
                <td style='padding:2px 0;font-weight:bold;color:#c00;white-space:nowrap;'>".e2($retNo)."</td>
            </tr>
            <tr>
                <td style='padding:2px 10px 2px 0;text-align:right;color:#000;'>Date:</td>
                <td style='padding:2px 0;color:#000;white-space:nowrap;'>".e2($first['created_at'])."</td>
            </tr>
            ".($orderInfo ? "<tr>
                <td style='padding:2px 10px 2px 0;text-align:right;color:#000;'>Sale Order:</td>
                <td style='padding:2px 0;font-weight:bold;color:#000;white-space:nowrap;'>".e2($orderInfo['order_no'])."</td>
            </tr>" : "")."
        </table>
    </td>
</tr>
</table>

<div style='border-top:2px solid #c00;margin-bottom:16px;'></div>

<!-- CUSTOMER & DETAILS -->
<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:16px;'>
<tr>
    <td style='width:48%;vertical-align:top;padding-right:16px;'>
        <div style='font-size:11px;font-weight:bold;margin-bottom:8px;border-bottom:1px solid #c00;padding-bottom:4px;color:#c00;'>Bill To</div>
        <table width='100%' cellpadding='0' cellspacing='0'>
            ".infoRow('Name', e2($orderInfo['customer_name'] ?? 'Walk-in'))."
            ".infoRow('Contact', e2($orderInfo['contact'] ?? '-'))."
            ".infoRow('Address', e2($orderInfo['address'] ?? '-'))."
        </table>
    </td>
    <td style='width:4%;'></td>
    <td style='width:48%;vertical-align:top;'>
        <div style='font-size:11px;font-weight:bold;margin-bottom:8px;border-bottom:1px solid #c00;padding-bottom:4px;color:#c00;'>Return Summary</div>
        <table width='100%' cellpadding='0' cellspacing='0'>
            ".infoRow('Total Items', (string)count($allItems))."
            ".infoRow('Total Refund', '<span style=\"color:#c00;\">Rs '.fmt($totalRefund).'</span>')."
        </table>
    </td>
</tr>
</table>

<!-- ITEMS -->
<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #000;margin-bottom:14px;'>
    <thead>
        <tr>
            <th style='padding:8px 6px;border-bottom:1px solid #c00;text-align:center;font-size:10px;font-weight:bold;width:28px;color:#c00;'>#</th>
            <th style='padding:8px;text-align:left;font-size:10px;font-weight:bold;border-bottom:1px solid #c00;color:#c00;'>Item Description</th>
            <th style='padding:8px 6px;border-bottom:1px solid #c00;text-align:center;font-size:10px;font-weight:bold;width:72px;color:#c00;'>SKU</th>
            <th style='padding:8px 6px;border-bottom:1px solid #c00;text-align:center;font-size:10px;font-weight:bold;width:36px;color:#c00;'>Qty</th>
            <th style='padding:8px;border-bottom:1px solid #c00;text-align:right;font-size:10px;font-weight:bold;width:78px;color:#c00;'>Refund Price</th>
            <th style='padding:8px;border-bottom:1px solid #c00;text-align:right;font-size:10px;font-weight:bold;width:82px;color:#c00;'>Amount</th>
            <th style='padding:8px;text-align:left;font-size:10px;font-weight:bold;border-bottom:1px solid #c00;width:110px;color:#c00;'>Reason</th>
        </tr>
    </thead>
    <tbody>
        {$rows}
    </tbody>
</table>

<!-- TOTALS -->
<table width='100%' cellpadding='0' cellspacing='0'>
<tr>
    <td style='width:52%;vertical-align:top;padding-right:20px;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #000;'>
            <tr>
                <td style='padding:8px 12px;font-size:10px;font-weight:bold;border-bottom:1px solid #c00;color:#c00;'>Amount in Words</td>
            </tr>
            <tr>
                <td style='padding:10px 12px;font-size:11px;line-height:1.5;'>{$amountWords}</td>
            </tr>
        </table>
    </td>
    <td style='width:48%;vertical-align:top;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #000;'>
            <tr>
                <td style='padding:10px 12px;font-size:12px;font-weight:bold;border-top:2px solid #c00;color:#c00;'>Total Refund</td>
                <td style='padding:10px 12px;text-align:right;font-size:12px;font-weight:bold;border-top:2px solid #c00;color:#c00;'>Rs ".fmt($totalRefund)."</td>
            </tr>
        </table>
    </td>
</tr>
</table>

{$stampBlock}

<!-- FOOTER -->
<div style='margin-top:28px;border-top:1px solid #c00;padding-top:10px;text-align:center;'>
    <div style='font-size:11px;font-weight:bold;margin-bottom:3px;color:#c00;'>Thank you for your business</div>
    <div style='font-size:9px;'>MJ Traders Inventory System &mdash; Generated ".date('d M Y, h:i A')."</div>
</div>

</body>
</html>";

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Helvetica');
$options->set('chroot', [realpath(__DIR__ . '/../')]);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdfContent = $dompdf->output();

if (ob_get_length()) ob_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $retNo . '.pdf"');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
