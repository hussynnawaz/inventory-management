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
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/><path d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 1 1.062.202c.331.143.69.272 1.054.368.364.096.745.17 1.136.217.404.046.826.065 1.264.065h.02c.439 0 .862-.02 1.277-.065a8 8 0 0 0 1.147-.218c.37-.097.731-.226 1.062-.368.331-.143.594-.273.794-.472a.8.8 0 0 1 .438.42c.156.307.11.675-.08 1.02l-.068.114c-.28.493-.772.908-1.347 1.182a6.8 6.8 0 0 1-1.563.405c-.358.06-.727.085-1.102.085h-.018c-.387 0-.76-.026-1.122-.085a7 7 0 0 1-1.58-.42c-.58-.28-1.07-.695-1.352-1.19l-.072-.116a.8.8 0 0 1 .08-1.02z"/></svg>
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
                                <th class="small fw-semibold text-end">Tax</th>
                                <th class="small fw-semibold text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $taxPct = (float)$order['sales_tax_pct'];
                            $i = 1;
                            foreach ($items as $it):
                                $lineTotal = (float)$it['line_total'];
                                $itemTax = round($lineTotal * $taxPct / 100, 2);
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
                    <small class="text-muted">Route</small>
                    <div><?= e($order['delivery_route'] ?: '-') ?></div>
                </div>
                <div class="mb-2">
                    <small class="text-muted">Salesman</small>
                    <div><?= e($order['salesman'] ?: '-') ?></div>
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
                    <span class="text-muted">GST (<?= $order['gst_pct'] ?>%)</span>
                    <span class="fw-semibold">Rs <?= number_format($order['gst_amt'], 2) ?></span>
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
