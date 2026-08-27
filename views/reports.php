<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

// 1. Overall Revenue from Sale Orders
$totalSales = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM sale_orders')->fetchColumn();

// 2. Overall Purchases (Cost / Expense)
$totalPurchases = $pdo->query('SELECT COALESCE(SUM(total), 0) FROM purchases')->fetchColumn();

// 3. Overall Returns (Refunds issued)
$totalReturns = $pdo->query('SELECT COALESCE(SUM(line_total), 0) FROM returns')->fetchColumn();

// 4. Net Revenue (Sales - Returns)
$netRevenue = $totalSales - $totalReturns;

// 5. Total Units Sold & Product-wise breakdown with cost, selling price & profit calculations
$productsReport = $pdo->query('
    SELECT 
        p.id,
        p.name,
        p.sku,
        p.cost_price,
        p.sale_price,
        p.quantity AS current_stock,
        COALESCE(SUM(soi.quantity), 0) AS total_units_sold,
        COALESCE(SUM(soi.line_total), 0) AS gross_sales_amount,
        COALESCE(SUM(soi.quantity * p.cost_price), 0) AS total_cogs,
        COALESCE(SUM(soi.quantity * (soi.price - p.cost_price)), 0) AS net_profit
    FROM products p
    LEFT JOIN sale_order_items soi ON soi.product_id = p.id
    GROUP BY p.id, p.name, p.sku, p.cost_price, p.sale_price, p.quantity
    ORDER BY total_units_sold DESC, net_profit DESC
')->fetchAll();

// Overall Net Profit calculation
$totalNetProfit = 0;
$totalUnitsSold = 0;
foreach ($productsReport as $pr) {
    $totalNetProfit += $pr['net_profit'];
    $totalUnitsSold += $pr['total_units_sold'];
}

ob_start();
?>
<!-- Key Financial Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;">
                    <?= icon('currency-dollar', 22) ?>
                </div>
                <div>
                    <div class="stat-label">Gross Sales</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs <?= number_format($totalSales, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#fee2e2;color:#991b1b;">
                    <?= icon('cart', 22) ?>
                </div>
                <div>
                    <div class="stat-label">Total Purchases</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs <?= number_format($totalPurchases, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#fef3c7;color:#92400e;">
                    <?= icon('arrow-return-left', 22) ?>
                </div>
                <div>
                    <div class="stat-label">Returns Refunded</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs <?= number_format($totalReturns, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <?= icon('clipboard', 22) ?>
                </div>
                <div>
                    <div class="stat-label">Total Net Profit</div>
                    <div class="stat-value text-primary" style="font-size:1.2rem;">Rs <?= number_format($totalNetProfit, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Product Sales & Profitability Analysis Table -->
<div class="card card-table">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div>
            <h6 class="fw-bold mb-0">Product Performance & Profitability Analysis</h6>
            <small class="text-muted">Net profit = (Selling Price - Purchase Cost) &times; Units Sold</small>
        </div>
        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2">
            Total Units Sold: <?= number_format($totalUnitsSold) ?>
        </span>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th class="text-center">Current Stock</th>
                    <th class="text-end">Purchase Cost</th>
                    <th class="text-end">Selling Price</th>
                    <th class="text-center">Units Sold</th>
                    <th class="text-end">Unit Profit Margin</th>
                    <th class="text-end">Total Gross Sales</th>
                    <th class="text-end">Total Net Profit</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productsReport)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No product sales records found.</td></tr>
                <?php else: foreach ($productsReport as $pr): 
                    $unitMargin = $pr['sale_price'] - $pr['cost_price'];
                ?>
                    <tr>
                        <td class="fw-bold text-dark"><?= e($pr['name']) ?></td>
                        <td><span class="font-monospace small text-muted"><?= e($pr['sku']) ?></span></td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark fw-semibold"><?= number_format($pr['current_stock']) ?></span>
                        </td>
                        <td class="text-end text-muted">Rs <?= number_format($pr['cost_price'], 2) ?></td>
                        <td class="text-end text-muted">Rs <?= number_format($pr['sale_price'], 2) ?></td>
                        <td class="text-center fw-bold text-primary"><?= number_format($pr['total_units_sold']) ?></td>
                        <td class="text-end fw-semibold <?= $unitMargin >= 0 ? 'text-success' : 'text-danger' ?>">
                            Rs <?= number_format($unitMargin, 2) ?>
                        </td>
                        <td class="text-end fw-semibold text-dark">Rs <?= number_format($pr['gross_sales_amount'], 2) ?></td>
                        <td class="text-end fw-bold fs-6 <?= $pr['net_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            Rs <?= number_format($pr['net_profit'], 2) ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_page('Reports', ob_get_clean());
