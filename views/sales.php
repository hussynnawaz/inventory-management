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
                        <td class="text-end">Rs <?= number_format($o['sales_tax_amt'] + $o['gst_amt'], 2) ?></td>
                        <td class="text-end fw-bold">Rs <?= number_format($o['total'], 2) ?></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="/controllers/sale_order_pdf.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-outline-danger" title="Download PDF">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2M9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/><path d="M4.603 14.087a.8.8 0 0 1-.438-.42c-.195-.388-.13-.776.08-1.102.198-.307.526-.568.897-.787a7.7 7.7 0 0 1 1.482-.645 20 20 0 0 1 1.062.202c.331.143.69.272 1.054.368.364.096.745.17 1.136.217.404.046.826.065 1.264.065h.02c.439 0 .862-.02 1.277-.065a8 8 0 0 0 1.147-.218c.37-.097.731-.226 1.062-.368.331-.143.594-.273.794-.472a.8.8 0 0 1 .438.42c.156.307.11.675-.08 1.02l-.068.114c-.28.493-.772.908-1.347 1.182a6.8 6.8 0 0 1-1.563.405c-.358.06-.727.085-1.102.085h-.018c-.387 0-.76-.026-1.122-.085a7 7 0 0 1-1.58-.42c-.58-.28-1.07-.695-1.352-1.19l-.072-.116a.8.8 0 0 1 .08-1.02z"/></svg>
                                </a>
                                <a href="/views/sale_order_view.php?id=<?= $o['id'] ?>" class="btn btn-outline-primary" title="View Details">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7"/></svg>
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
