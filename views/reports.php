<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$totalSales  = $pdo->query('SELECT COALESCE(SUM(total),0) FROM sales')->fetchColumn();
$totalPurch  = $pdo->query('SELECT COALESCE(SUM(total),0) FROM purchases')->fetchColumn();
$totalReturn = $pdo->query('SELECT COALESCE(SUM(quantity),0) FROM returns')->fetchColumn();
$profit      = $totalSales - $totalPurch;
$topProducts = $pdo->query('
    SELECT p.name, COALESCE(SUM(si.quantity),0) AS units
    FROM products p
    LEFT JOIN sale_items si ON si.product_id = p.id
    GROUP BY p.id
    ORDER BY units DESC
    LIMIT 5
')->fetchAll();

ob_start();
?>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-sm text-slate-500">Total Revenue</p>
        <p class="text-2xl font-bold text-green-700 mt-1">Rs <?= number_format($totalSales, 2) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-sm text-slate-500">Total Expenses</p>
        <p class="text-2xl font-bold text-red-700 mt-1">Rs <?= number_format($totalPurch, 2) ?></p>
    </div>
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-sm text-slate-500">Net Profit</p>
        <p class="text-2xl font-bold text-blue-700 mt-1">Rs <?= number_format($profit, 2) ?></p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="font-semibold text-slate-800">Top Selling Products</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 bg-slate-50">
            <tr>
                <th class="px-5 py-3">Product</th>
                <th class="px-5 py-3 text-right">Units Sold</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($topProducts)): ?>
                <tr><td colspan="2" class="px-5 py-6 text-center text-slate-400">No data available.</td></tr>
            <?php else: foreach ($topProducts as $tp): ?>
                <tr>
                    <td class="px-5 py-3"><?= e($tp['name']) ?></td>
                    <td class="px-5 py-3 text-right"><?= e($tp['units']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<p class="text-sm text-slate-500 mt-4">Total units returned: <span class="font-semibold"><?= number_format($totalReturn) ?></span></p>
<?php
render_page('Reports', ob_get_clean());
