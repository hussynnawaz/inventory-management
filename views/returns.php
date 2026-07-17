<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$returns = $pdo->query('
    SELECT r.*, p.name AS product
    FROM returns r
    LEFT JOIN products p ON p.id = r.product_id
    ORDER BY r.created_at DESC
')->fetchAll();

ob_start();
?>
<div class="flex justify-between items-center mb-4">
    <p class="text-sm text-slate-500">Record and review product returns.</p>
    <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">New Return</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 bg-slate-50">
            <tr>
                <th class="px-5 py-3">Return No</th>
                <th class="px-5 py-3">Product</th>
                <th class="px-5 py-3 text-center">Qty</th>
                <th class="px-5 py-3">Reason</th>
                <th class="px-5 py-3">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($returns)): ?>
                <tr><td colspan="5" class="px-5 py-6 text-center text-slate-400">No returns recorded yet.</td></tr>
            <?php else: foreach ($returns as $r): ?>
                <tr>
                    <td class="px-5 py-3 font-mono"><?= e($r['return_no']) ?></td>
                    <td class="px-5 py-3"><?= e($r['product'] ?? 'N/A') ?></td>
                    <td class="px-5 py-3 text-center"><?= e($r['quantity']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($r['reason']) ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($r['created_at']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php
render_page('Returns', ob_get_clean());
