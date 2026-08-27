<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$totalProducts = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalSales    = $pdo->query('SELECT COALESCE(SUM(total),0) FROM sale_orders')->fetchColumn();
$totalPurch    = $pdo->query('SELECT COALESCE(SUM(total),0) FROM purchases')->fetchColumn();
$lowStock      = $pdo->query('SELECT COUNT(*) FROM products WHERE quantity <= 5')->fetchColumn();
$recentOrders  = $pdo->query('SELECT * FROM sale_orders ORDER BY created_at DESC LIMIT 5')->fetchAll();

ob_start();
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <?= icon('boxes', 22) ?>
                </div>
                <div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= number_format($totalProducts) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;">
                    <?= icon('currency-dollar', 22) ?>
                </div>
                <div>
                    <div class="stat-label">Total Sales</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs <?= number_format($totalSales, 0) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#fef3c7;color:#92400e;">
                    <?= icon('cart', 22) ?>
                </div>
                <div>
                    <div class="stat-label">Total Purchases</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs <?= number_format($totalPurch, 0) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#fee2e2;color:#991b1b;">
                    <?= icon('exclamation-triangle', 22) ?>
                </div>
                <div>
                    <div class="stat-label">Low Stock Items</div>
                    <div class="stat-value"><?= number_format($lowStock) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sales Table -->
<div class="card card-table">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">Recent Sale Orders</h6>
        <a href="/views/sales.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Order No</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th class="text-end">Total</th>
                    <th class="text-center" style="width:80px">PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No sale orders yet.</td></tr>
                <?php else: foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><span class="font-monospace fw-semibold"><?= e($o['order_no']) ?></span></td>
                        <td><?= e($o['customer_name'] ?: 'Walk-in') ?></td>
                        <td class="text-muted"><?= e($o['order_date']) ?></td>
                        <td class="text-end fw-semibold">Rs <?= number_format($o['total'], 2) ?></td>
                        <td class="text-center">
                            <a href="/controllers/sale_order_pdf.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Download PDF">
                                <?= icon('file-earmark-text', 14) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
render_page('Dashboard', ob_get_clean());
