<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/invoice_calculations.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: /views/sales.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM sale_orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) { header('Location: /views/sales.php'); exit; }

$items = $pdo->prepare('
    SELECT soi.*, p.sku
    FROM sale_order_items soi
    LEFT JOIN products p ON p.id = soi.product_id
    WHERE soi.sale_order_id = ?
');
$items->execute([$id]);
$items = $items->fetchAll();

// Get salesman name
$salesmanName = $order['salesman'] ?? '';
if (!empty($order['salesman_id'])) {
    $smStmt = $pdo->prepare('SELECT name FROM salesmen WHERE id = ?');
    $smStmt->execute([$order['salesman_id']]);
    $sm = $smStmt->fetchColumn();
    if ($sm) $salesmanName = $sm;
}

// Authoritative totals straight from the stored order row (shared with the PDF)
$t = invoice_totals_from_order($order);
$subtotal = $t['subtotal'];
$taxPct = $t['sales_tax_pct'];
$salesTaxAmt = $t['sales_tax_amt'];
$afterSalesTax = $t['after_sales_tax'];
$advTaxPct = $t['advanced_tax_pct'];
$advTaxAmt = $t['advanced_tax_amt'];
$grandTotal = $t['net_total'];
$itemTaxes = invoice_line_sales_taxes($items, $taxPct, $salesTaxAmt);
$amountPaid = invoice_order_amount_paid($pdo, (int)$order['id']);
$amountRemaining = invoice_amount_remaining($grandTotal, $amountPaid);
$paymentStatus = invoice_payment_status($grandTotal, $amountPaid);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-0">Sale Order: <?= e($order['order_no']) ?></h5>
        <small class="text-muted">Date: <?= e($order['order_date']) ?></small>
    </div>
    <div class="d-flex gap-2">
        <a href="/views/sale_order_new.php?edit=<?= $order['id'] ?>" class="btn btn-warning btn-sm fw-semibold">
            <?= icon('edit', 14) ?> Edit Order
        </a>
        <a href="/controllers/sale_order_pdf.php?id=<?= $order['id'] ?>" target="_blank" class="btn btn-danger btn-sm">
            <?= icon('file-text', 14) ?> Download PDF
        </a>
        <a href="/views/sales.php" class="btn btn-outline-secondary btn-sm">Back to Sales</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Customer Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted ps-0" style="width:120px">Customer Code:</td><td class="fw-semibold"><?= e($order['customer_code'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted ps-0">Name:</td><td class="fw-semibold"><?= e($order['customer_name'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted ps-0">Contact:</td><td><?= e($order['contact'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted ps-0">Address:</td><td><?= e($order['address'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted ps-0">Destination:</td><td><?= e($order['destination'] ?: '-') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Tax & Salesman Information</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted ps-0" style="width:120px">NTN No:</td><td class="fw-semibold"><?= e($order['ntn_no'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted ps-0">Sales Tax No:</td><td><?= e($order['sales_tax_no'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted ps-0">CNIC:</td><td><?= e($order['cnic'] ?: '-') ?></td></tr>
                    <tr><td class="text-muted ps-0">Salesman:</td><td class="fw-semibold text-primary"><?= e($salesmanName ?: '-') ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header bg-white py-3"><h6 class="fw-bold mb-0">Order Line Items</h6></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Sales Tax</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_values($items) as $idx => $it):
                    $lineTotal = round((float)$it['line_total'], 2);
                    $itemSalesTax = $itemTaxes[$idx];
                ?>
                <tr>
                    <td class="fw-semibold"><?= e($it['product_name']) ?></td>
                    <td><span class="font-monospace text-muted small"><?= e($it['sku'] ?: '-') ?></span></td>
                    <td class="text-end">Rs <?= number_format($it['price'], 2) ?></td>
                    <td class="text-center fw-semibold"><?= (int)$it['quantity'] ?></td>
                    <td class="text-end fw-bold">Rs <?= number_format($lineTotal, 2) ?></td>
                    <td class="text-end text-muted">Rs <?= number_format($itemSalesTax, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-5 offset-md-7">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-semibold">Rs <?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Sales Tax (<?= $taxPct ?>%)</span>
                    <span>Rs <?= number_format($salesTaxAmt, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Advance Tax (<?= $advTaxPct ?>%)</span>
                    <span>Rs <?= number_format($advTaxAmt, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between pt-2 border-top mb-2">
                    <span class="fs-5 fw-bold">Grand Total</span>
                    <span class="fs-5 fw-bold text-primary">Rs <?= number_format($grandTotal, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between small mb-1">
                    <span class="text-muted">Amount Paid</span>
                    <span class="fw-semibold">Rs <?= number_format($amountPaid, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">Amount Remaining</span>
                    <span class="fw-semibold">Rs <?= number_format($amountRemaining, 2) ?></span>
                </div>
                <div class="small text-muted">Payment status: <span class="fw-semibold text-capitalize"><?= e($paymentStatus) ?></span></div>
            </div>
        </div>
    </div>
</div>

<?php
render_page('View Sale Order', ob_get_clean());
