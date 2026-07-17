<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

$products = $pdo->query('SELECT * FROM products ORDER BY name ASC')->fetchAll();

ob_start();
?>
<div class="flex justify-between items-center mb-4">
    <p class="text-sm text-slate-500">Manage products and stock levels.</p>
    <a href="#" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Add Product</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 bg-slate-50">
            <tr>
                <th class="px-5 py-3">SKU</th>
                <th class="px-5 py-3">Name</th>
                <th class="px-5 py-3">Category</th>
                <th class="px-5 py-3 text-right">Cost</th>
                <th class="px-5 py-3 text-right">Price</th>
                <th class="px-5 py-3 text-right">Qty</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($products)): ?>
                <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">No products yet.</td></tr>
            <?php else: foreach ($products as $p): ?>
                <tr>
                    <td class="px-5 py-3 font-mono text-slate-500"><?= e($p['sku']) ?></td>
                    <td class="px-5 py-3 font-medium"><?= e($p['name']) ?></td>
                    <td class="px-5 py-3"><?= e($p['category']) ?></td>
                    <td class="px-5 py-3 text-right">Rs <?= number_format($p['cost_price'], 2) ?></td>
                    <td class="px-5 py-3 text-right">Rs <?= number_format($p['sale_price'], 2) ?></td>
                    <td class="px-5 py-3 text-right">
                        <span class="<?= $p['quantity'] <= 5 ? 'text-red-600 font-semibold' : 'text-slate-700' ?>">
                            <?= e($p['quantity']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php
render_page('Inventory', ob_get_clean());
