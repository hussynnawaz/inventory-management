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
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4M4 7l8 4M4 7v10l8 4m0-10v10"/></svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
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
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
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
