<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$totalProducts = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalSales    = $pdo->query('SELECT COALESCE(SUM(total),0) FROM sales')->fetchColumn();
$totalPurch   = $pdo->query('SELECT COALESCE(SUM(total),0) FROM purchases')->fetchColumn();
$lowStock     = $pdo->query('SELECT COUNT(*) FROM products WHERE quantity <= 5')->fetchColumn();
$recentSales  = $pdo->query('SELECT * FROM sales ORDER BY created_at DESC LIMIT 5')->fetchAll();

$cards = [
    ['label' => 'Total Products', 'value' => $totalProducts, 'color' => 'bg-blue-50 text-blue-700'],
    ['label' => 'Total Sales',    'value' => 'Rs ' . number_format($totalSales, 2), 'color' => 'bg-green-50 text-green-700'],
    ['label' => 'Total Purchases', 'value' => 'Rs ' . number_format($totalPurch, 2), 'color' => 'bg-amber-50 text-amber-700'],
    ['label' => 'Low Stock Items', 'value' => $lowStock, 'color' => 'bg-red-50 text-red-700'],
];

ob_start();
?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <?php foreach ($cards as $c): ?>
        <div class="<?= $c['color'] ?> rounded-xl p-5 shadow-sm">
            <p class="text-sm font-medium opacity-80"><?= e($c['label']) ?></p>
            <p class="text-2xl font-bold mt-1"><?= e($c['value']) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="font-semibold text-slate-800">Recent Sales</h2>
    </div>
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 bg-slate-50">
            <tr>
                <th class="px-5 py-3">Invoice</th>
                <th class="px-5 py-3">Date</th>
                <th class="px-5 py-3 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($recentSales)): ?>
                <tr><td colspan="3" class="px-5 py-6 text-center text-slate-400">No sales yet.</td></tr>
            <?php else: foreach ($recentSales as $s): ?>
                <tr>
                    <td class="px-5 py-3 font-mono"><?= e($s['invoice_no']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($s['created_at']) ?></td>
                    <td class="px-5 py-3 text-right font-medium">Rs <?= number_format($s['total'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php
render_page('Dashboard', ob_get_clean());
