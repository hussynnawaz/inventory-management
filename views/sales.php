<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_login();

// Fetch all sale orders (the main sale flow uses sale_orders table)
$orders = $pdo->query('
    SELECT so.*
    FROM sale_orders so
    ORDER BY so.created_at DESC
')->fetchAll();

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted small mb-0">View all sale orders, history and generate PDF invoices.</p>
    <a href="/views/sale_order_new.php" class="btn btn-primary btn-sm">New Sale Order</a>
</div>

<div class="card card-table">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Order No</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Date</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">Tax</th>
                    <th class="text-end">Total</th>
                    <th class="text-center" style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No sale orders yet.</td></tr>
                <?php else: foreach ($orders as $o): ?>
                    <tr>
                        <td><span class="font-monospace fw-semibold"><?= e($o['order_no']) ?></span></td>
                        <td><?= e($o['customer_name'] ?: 'Walk-in') ?></td>
                        <td class="text-muted"><?= e($o['contact']) ?></td>
                        <td class="text-muted"><?= e($o['order_date']) ?></td>
                        <td class="text-end">Rs <?= number_format($o['subtotal'], 2) ?></td>
                        <td class="text-end">Rs <?= number_format(($o['sales_tax_amt'] ?? 0) + ($o['advanced_tax_amt'] ?? 0), 2) ?></td>
                        <td class="text-end fw-bold">Rs <?= number_format($o['total'], 2) ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="/controllers/sale_order_pdf.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-outline-danger" title="Download PDF">
                                    <?= icon('file-earmark-text', 14) ?>
                                </a>
                                <a href="/views/sale_order_view.php?id=<?= $o['id'] ?>" class="btn btn-outline-primary" title="View Details">
                                    <?= icon('eye', 14) ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
render_page('Sales', ob_get_clean());
