<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_login();

use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); die('Invalid order id.'); }

$stmt = $pdo->prepare('SELECT * FROM sale_orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) { http_response_code(404); die('Order not found.'); }

$items = $pdo->prepare('
    SELECT soi.*, p.sku
    FROM sale_order_items soi
    LEFT JOIN products p ON p.id = soi.product_id
    WHERE soi.sale_order_id = ?
');
$items->execute([$id]);
$items = $items->fetchAll();

// Get salesman name from salesmen table
$salesmanName = $order['salesman'] ?? '';
if (!empty($order['salesman_id'])) {
    $smStmt = $pdo->prepare('SELECT name FROM salesmen WHERE id = ?');
    $smStmt->execute([$order['salesman_id']]);
    $sm = $smStmt->fetchColumn();
    if ($sm) $salesmanName = $sm;
}

// --- PDF Cache Setup ---
$cacheDir = __DIR__ . '/../cache/pdfs';
if (!is_dir($cacheDir)) { mkdir($cacheDir, 0755, true); }
$cacheFile = $cacheDir . '/sale_order_' . $id . '.pdf';

// Serve from cache if it exists and order hasn't been modified since generation
if (file_exists($cacheFile)) {
    $cachedAt = filemtime($cacheFile);
    $modifiedAt = strtotime($order['updated_at'] ?? $order['created_at'] ?? 'now');
    if ($cachedAt >= $modifiedAt) {
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($cacheFile));
        readfile($cacheFile);
        exit;
    }
}

/**
 * Resize high-resolution PNG images for fast embedding in Dompdf.
 */
function getOptimizedImagePath(string $path, int $targetWidth = 300): string {
    $realPath = realpath($path);
    if (!$realPath || !file_exists($realPath)) {
        return '';
    }

    $cacheImgDir = __DIR__ . '/../cache/images';
    if (!is_dir($cacheImgDir)) {
        mkdir($cacheImgDir, 0755, true);
    }

    $filename = pathinfo($realPath, PATHINFO_FILENAME);
    $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
    $cacheFile = $cacheImgDir . '/' . $filename . '_' . $targetWidth . '.' . $ext;

    if (file_exists($cacheFile) && filemtime($cacheFile) >= filemtime($realPath)) {
        return $cacheFile;
    }

    if ($ext === 'png' && function_exists('imagecreatefrompng')) {
        $info = @getimagesize($realPath);
        if ($info && $info[0] > 0) {
            $w = $info[0];
            $h = $info[1];
            if ($w <= $targetWidth) {
                return $realPath;
            }
            $targetHeight = (int)round($targetWidth * $h / $w);
            $srcImg = @imagecreatefrompng($realPath);
            if ($srcImg) {
                $dstImg = imagecreatetruecolor($targetWidth, $targetHeight);
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);
                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $targetWidth, $targetHeight, $w, $h);
                imagepng($dstImg, $cacheFile, 7);
                imagedestroy($srcImg);
                imagedestroy($dstImg);
                return $cacheFile;
            }
        }
    }

    return $realPath;
}

$logoPath = getOptimizedImagePath(__DIR__ . '/../public/assets/images/mj-logo.png', 300);
$stampPath = getOptimizedImagePath(__DIR__ . '/../public/assets/images/mj-traders-stamp.png', 200);

$taxPct = (float)$order['sales_tax_pct'];
$advTaxPct = (float)($order['advanced_tax_pct'] ?? 0);
$totalTaxPct = $taxPct + $advTaxPct;

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
    $hundreds = intdiv($n, 100);
    $rest = $n % 100;
    $word = $hundreds ? spellUnder100($hundreds) . ' Hundred' : '';
    if ($rest) $word .= ($word ? ' ' : '') . spellUnder100($rest);
    return $word;
}

function numberToWords(int $n): string {
    if ($n === 0) return 'Zero';

    $parts = [];
    $crore = intdiv($n, 10000000);
    $n %= 10000000;
    $lakh = intdiv($n, 100000);
    $n %= 100000;
    $thousand = intdiv($n, 1000);
    $n %= 1000;

    if ($crore) $parts[] = spellUnder1000($crore) . ' Crore';
    if ($lakh) $parts[] = spellUnder1000($lakh) . ' Lakh';
    if ($thousand) $parts[] = spellUnder1000($thousand) . ' Thousand';
    if ($n) $parts[] = spellUnder1000($n);

    return implode(' ', $parts);
}

function amountInWords(float $amount): string {
    $rupees = (int) floor(abs($amount));
    $paisa = (int) round((abs($amount) - $rupees) * 100);

    if ($paisa >= 100) {
        $rupees += 1;
        $paisa = 0;
    }

    $words = numberToWords($rupees) . ' Rupee' . ($rupees === 1 ? '' : 's');
    if ($paisa > 0) {
        $words .= ' and ' . numberToWords($paisa) . ' Paisa' . ($paisa === 1 ? '' : '');
    }

    return $words . ' Only';
}

function infoRow(string $label, string $value): string {
    return "
    <tr>
        <td style='padding:3px 0;width:88px;color:#000;font-size:11px;vertical-align:top;'>{$label}</td>
        <td style='padding:3px 0;color:#000;font-size:11px;font-weight:bold;vertical-align:top;'>{$value}</td>
    </tr>";
}

$rows = '';
$i = 1;
foreach ($items as $it) {
    $lineTotal = (float)$it['line_total'];
    $itemTax = round($lineTotal * $totalTaxPct / 100, 2);
    $rows .= "
    <tr>
        <td style='padding:7px 6px;border-bottom:1px solid #000;text-align:center;font-size:11px;'>{$i}</td>
        <td style='padding:7px 8px;border-bottom:1px solid #000;font-size:11px;font-weight:bold;'>".e2($it['product_name'])."</td>
        <td style='padding:7px 6px;border-bottom:1px solid #000;text-align:center;font-size:11px;'>".e2($it['sku'] ?? '-')."</td>
        <td style='padding:7px 6px;border-bottom:1px solid #000;text-align:center;font-size:11px;'>".$it['quantity']."</td>
        <td style='padding:7px 8px;border-bottom:1px solid #000;text-align:right;font-size:11px;'>Rs ".fmt($it['price'])."</td>
        <td style='padding:7px 8px;border-bottom:1px solid #000;text-align:right;font-size:11px;'>Rs ".fmt($itemTax)."</td>
        <td style='padding:7px 8px;border-bottom:1px solid #000;text-align:right;font-size:11px;font-weight:bold;'>Rs ".fmt($lineTotal)."</td>
    </tr>";
    $i++;
}

$amountWords = e2(amountInWords((float)$order['total']));

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
                    <strong style='font-size:11px;'>Terms &amp; Conditions</strong><br>
                    Goods once sold will not be taken back.<br>
                    Payment due as per agreed credit terms.<br>
                    This is a computer-generated invoice.
                </div>
            </td>
            <td style='width:45%;text-align:center;vertical-align:bottom;'>
                <img src='{$stampPath}' style='width:100px;height:auto;margin-bottom:6px;' />
                <div style='border-top:1px solid #000;width:170px;margin:0 auto;'></div>
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
    @page { margin: 24mm 26mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Helvetica, Arial, sans-serif; color: #000; font-size: 11px; line-height: 1.4; padding: 6mm 4mm; }
</style>
</head>
<body>

<!-- HEADER -->
<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:14px;'>
<tr>
    <td style='width:125px;vertical-align:top;padding-right:16px;'>
        {$logoBlock}
    </td>
    <td style='vertical-align:top;padding-right:12px;'>
        <div style='font-size:20px;font-weight:bold;color:#000;line-height:1.2;margin-bottom:4px;'>MJ Traders</div>
        <div style='font-size:11px;color:#000;'>Murree Road, Dhamtor, Abbottabad</div>
        <div style='font-size:11px;color:#000;'>Tel: 0315-4999667</div>
        <div style='font-size:10px;color:#000;margin-top:2px;'>Wholesale &amp; Distribution</div>
    </td>
    <td style='width:240px;vertical-align:top;text-align:right;'>
        <div style='font-size:14px;font-weight:bold;color:#000;margin-bottom:10px;'>SALE INVOICE</div>
        <table cellpadding='0' cellspacing='0' style='margin-left:auto;font-size:11px;'>
            <tr>
                <td style='padding:2px 10px 2px 0;text-align:right;color:#000;'>Invoice No:</td>
                <td style='padding:2px 0;font-weight:bold;color:#000;white-space:nowrap;'>".e2($order['order_no'])."</td>
            </tr>
            <tr>
                <td style='padding:2px 10px 2px 0;text-align:right;color:#000;'>Date:</td>
                <td style='padding:2px 0;color:#000;white-space:nowrap;'>".e2($order['order_date'])."</td>
            </tr>
        </table>
    </td>
</tr>
</table>

<div style='border-top:2px solid #000;margin-bottom:16px;'></div>

<!-- CUSTOMER & TAX -->
<table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:16px;'>
<tr>
    <td style='width:48%;vertical-align:top;padding-right:16px;'>
        <div style='font-size:11px;font-weight:bold;margin-bottom:8px;border-bottom:1px solid #000;padding-bottom:4px;'>Bill To</div>
        <table width='100%' cellpadding='0' cellspacing='0'>
            ".infoRow('Code', e2($order['customer_code'] ?: '-'))."
            ".infoRow('Name', e2($order['customer_name'] ?: '-'))."
            ".infoRow('Contact', e2($order['contact'] ?: '-'))."
            ".infoRow('Address', e2($order['address'] ?: '-'))."
        </table>
    </td>
    <td style='width:4%;'></td>
    <td style='width:48%;vertical-align:top;'>
        <div style='font-size:11px;font-weight:bold;margin-bottom:8px;border-bottom:1px solid #000;padding-bottom:4px;'>Tax Information</div>
        <table width='100%' cellpadding='0' cellspacing='0'>
            ".infoRow('NTN No', e2($order['ntn_no'] ?: '-'))."
            ".infoRow('Sales Tax No', e2($order['sales_tax_no'] ?: '-'))."
            ".infoRow('CNIC', e2($order['cnic'] ?: '-'))."
            ".infoRow('Salesman', e2($salesmanName ?: '-'))."
        </table>
    </td>
</tr>
</table>

<!-- ITEMS -->
<table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #000;margin-bottom:14px;'>
    <thead>
        <tr>
            <th style='padding:8px 6px;border-bottom:1px solid #000;text-align:center;font-size:10px;font-weight:bold;width:28px;'>#</th>
            <th style='padding:8px;text-align:left;font-size:10px;font-weight:bold;border-bottom:1px solid #000;'>Item Description</th>
            <th style='padding:8px 6px;border-bottom:1px solid #000;text-align:center;font-size:10px;font-weight:bold;width:72px;'>SKU</th>
            <th style='padding:8px 6px;border-bottom:1px solid #000;text-align:center;font-size:10px;font-weight:bold;width:36px;'>Qty</th>
            <th style='padding:8px;border-bottom:1px solid #000;text-align:right;font-size:10px;font-weight:bold;width:78px;'>Unit Price</th>
            <th style='padding:8px;border-bottom:1px solid #000;text-align:right;font-size:10px;font-weight:bold;width:72px;'>Tax ({$totalTaxPct}%)</th>
            <th style='padding:8px;border-bottom:1px solid #000;text-align:right;font-size:10px;font-weight:bold;width:82px;'>Amount</th>
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
                <td style='padding:8px 12px;font-size:10px;font-weight:bold;border-bottom:1px solid #000;'>Amount in Words</td>
            </tr>
            <tr>
                <td style='padding:10px 12px;font-size:11px;line-height:1.5;'>{$amountWords}</td>
            </tr>
        </table>
    </td>
    <td style='width:48%;vertical-align:top;'>
        <table width='100%' cellpadding='0' cellspacing='0' style='border:1px solid #000;'>
            <tr>
                <td style='padding:8px 12px;font-size:11px;border-bottom:1px solid #000;'>Subtotal</td>
                <td style='padding:8px 12px;text-align:right;font-size:11px;border-bottom:1px solid #000;width:100px;'>Rs ".fmt($order['subtotal'])."</td>
            </tr>
            <tr>
                <td style='padding:8px 12px;font-size:11px;border-bottom:1px solid #000;'>Sales Tax ({$taxPct}%)</td>
                <td style='padding:8px 12px;text-align:right;font-size:11px;border-bottom:1px solid #000;'>Rs ".fmt($order['sales_tax_amt'])."</td>
            </tr>
            <tr>
                <td style='padding:8px 12px;font-size:11px;border-bottom:1px solid #000;'>Advanced Tax ({$advTaxPct}%)</td>
                <td style='padding:8px 12px;text-align:right;font-size:11px;border-bottom:1px solid #000;'>Rs ".fmt($order['advanced_tax_amt'] ?? 0)."</td>
            </tr>
            <tr>
                <td style='padding:10px 12px;font-size:12px;font-weight:bold;border-top:2px solid #000;'>Net Total</td>
                <td style='padding:10px 12px;text-align:right;font-size:12px;font-weight:bold;border-top:2px solid #000;'>Rs ".fmt($order['total'])."</td>
            </tr>
        </table>
    </td>
</tr>
</table>

{$stampBlock}

<!-- FOOTER -->
<div style='margin-top:28px;border-top:1px solid #000;padding-top:10px;text-align:center;'>
    <div style='font-size:11px;font-weight:bold;margin-bottom:3px;'>Thank you for your business</div>
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
file_put_contents($cacheFile, $pdfContent);

if (ob_get_length()) ob_clean();
header('Content-Type: application/pdf');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;


