<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
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

ob_start();
?>
<div class="mb-4">
    <a href="/views/sales.php" class="btn btn-sm btn-outline-secondary">
        <?= icon('arrow-left', 14) ?>
        Back to Sales
    </a>
</div>

<div class="row g-4">
    <!-- Order Info -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="fw-bold mb-1"><?= e($order['order_no']) ?></h5>
                        <small class="text-muted"><?= e($order['order_date']) ?></small>
                    </div>
                    <a href="/controllers/sale_order_pdf.php?id=<?= $order['id'] ?>" target="_blank" class="btn btn-danger btn-sm">
                        <?= icon('file-earmark-text', 14, 'me-1') ?>
                        Download PDF
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="small fw-semibold">#</th>
                                <th class="small fw-semibold">Product</th>
                                <th class="small fw-semibold">SKU</th>
                                <th class="small fw-semibold text-center">Qty</th>
                                <th class="small fw-semibold text-end">Unit Price</th>
                                <th class="small fw-semibold text-end">Tax (<?= $totalTaxPct ?>%)</th>
                                <th class="small fw-semibold text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $taxPct = (float)$order['sales_tax_pct'];
                            $advTaxPct = (float)($order['advanced_tax_pct'] ?? 0);
                            $totalTaxPct = $taxPct + $advTaxPct;
                            $i = 1;
                            foreach ($items as $it):
                                $lineTotal = (float)$it['line_total'];
                                $itemTax = round($lineTotal * $totalTaxPct / 100, 2);
                            ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td class="fw-semibold"><?= e($it['product_name']) ?></td>
                                <td class="font-monospace text-muted"><?= e($it['sku'] ?? '-') ?></td>
                                <td class="text-center"><?= $it['quantity'] ?></td>
                                <td class="text-end">Rs <?= number_format($it['price'], 2) ?></td>
                                <td class="text-end">Rs <?= number_format($itemTax, 2) ?></td>
                                <td class="text-end fw-bold">Rs <?= number_format($lineTotal, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary -->
    <div class="col-lg-4">
        <!-- Customer Card -->
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Customer</h6>
                <div class="mb-2">
                    <small class="text-muted">Code</small>
                    <div class="fw-semibold font-monospace"><?= e($order['customer_code'] ?: '-') ?></div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Name</small>
                    <div class="fw-semibold"><?= e($order['customer_name'] ?: '-') ?></div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Contact</small>
                    <div><?= e($order['contact'] ?: '-') ?></div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Destination</small>
                    <div><?= e($order['destination'] ?: '-') ?></div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Salesman</small>
                    <div><?php
                        $smName = $order['salesman'] ?? '';
                        if (!empty($order['salesman_id'])) {
                            $smStmt = $pdo->prepare('SELECT name FROM salesmen WHERE id = ?');
                            $smStmt->execute([$order['salesman_id']]);
                            $sm = $smStmt->fetchColumn();
                            if ($sm) $smName = $sm;
                        }
                        echo e($smName ?: '-');
                    ?></div>
                </div>
                <div class="mb-0">
                    <small class="text-muted">Address</small>
                    <div><?= e($order['address'] ?: '-') ?></div>
                </div>
            </div>
        </div>

        <!-- Billing Summary -->
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Billing Summary</h6>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span class="fw-semibold">Rs <?= number_format($order['subtotal'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between small mb-2">
                    <span class="text-muted">Sales Tax (<?= $order['sales_tax_pct'] ?>%)</span>
                    <span class="fw-semibold">Rs <?= number_format($order['sales_tax_amt'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between small mb-3">
                    <span class="text-muted">Advanced Tax (<?= $order['advanced_tax_pct'] ?? '0' ?>%)</span>
                    <span class="fw-semibold">Rs <?= number_format($order['advanced_tax_amt'] ?? 0, 2) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Net Total</span>
                    <span class="fs-5 fw-bold text-primary">Rs <?= number_format($order['total'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
render_page('Order Details - ' . $order['order_no'], ob_get_clean());
