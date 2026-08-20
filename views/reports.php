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
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/></svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/></svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5"/></svg>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1zm1 12h2V2h-2zm-3 0V7H7v7zm-5 0v-3H2v3z"/></svg>
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
