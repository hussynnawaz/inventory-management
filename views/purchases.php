<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$purchases = $pdo->query('
    SELECT p.*, s.name AS supplier
    FROM purchases p
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    ORDER BY p.created_at DESC
')->fetchAll();

ob_start();
?>
<div class="flex justify-between items-center mb-4">
    <p class="text-sm text-slate-500">Track purchases from suppliers.</p>
    <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">New Purchase</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 bg-slate-50">
            <tr>
                <th class="px-5 py-3">Reference</th>
                <th class="px-5 py-3">Supplier</th>
                <th class="px-5 py-3">Date</th>
                <th class="px-5 py-3 text-right">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($purchases)): ?>
                <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">No purchases recorded yet.</td></tr>
            <?php else: foreach ($purchases as $p): ?>
                <tr>
                    <td class="px-5 py-3 font-mono"><?= e($p['reference_no']) ?></td>
                    <td class="px-5 py-3"><?= e($p['supplier'] ?? 'N/A') ?></td>
                    <td class="px-5 py-3 text-slate-500"><?= e($p['created_at']) ?></td>
                    <td class="px-5 py-3 text-right font-medium">Rs <?= number_format($p['total'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php
render_page('Purchases', ob_get_clean());
