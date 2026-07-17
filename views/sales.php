<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$sales = $pdo->query('
    SELECT s.*, c.name AS customer
    FROM sales s
    LEFT JOIN customers c ON c.id = s.customer_id
    ORDER BY s.created_at DESC
')->fetchAll();

ob_start();
?>
<div class="flex justify-between items-center mb-4">
    <p class="text-sm text-slate-500">Manage your sales transactions.</p>
    <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">New Sale</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 bg-slate-50">
            <tr>
                <th class="px-5 py-3">Invoice</th>
                <th class="px-5 py-3">Customer</th>
                <th class="px-5 py-3">Date</th>
                <th class="px-5 py-3 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($sales)): ?>
                <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">No sales recorded yet.</td></tr>
            <?php else: foreach ($sales as $s): ?>
                <tr>
                    <td class="px-5 py-3 font-mono"><?= e($s['invoice_no']) ?></td>
                    <td class="px-5 py-3"><?= e($s['customer'] ?? 'Walk-in') ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($s['created_at']) ?></td>
                    <td class="px-5 py-3 text-right font-medium">Rs <?= number_format($s['total'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php
render_page('Sales', ob_get_clean());
