<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/modal.php';
require_login();

// Fetch returns list
$returns = $pdo->query('
    SELECT r.*, p.name AS product_name, p.sku, so.order_no, u.username
    FROM returns r
    LEFT JOIN products p ON p.id = r.product_id
    LEFT JOIN sale_orders so ON so.id = r.sale_order_id
    LEFT JOIN users u ON u.id = r.user_id
    ORDER BY r.created_at DESC
')->fetchAll();

// Fetch products for dropdown
$products = $pdo->query('SELECT id, name, sku, sale_price, cost_price, quantity FROM products ORDER BY name ASC')->fetchAll();

ob_start();
?>
<style>
    .cust-ac-wrap { position: relative; }
    .cust-ac-dropdown {
        position: absolute; left: 0; right: 0; top: 100%;
        background: #fff; border: 1px solid #dee2e6; border-radius: .5rem;
        margin-top: 4px; max-height: 240px; overflow-y: auto;
        box-shadow: 0 10px 30px rgba(0,0,0,.12); z-index: 50;
    }
    .cust-ac-item {
        padding: .6rem .85rem; cursor: pointer; font-size: .85rem;
        display: flex; justify-content: space-between; align-items: center;
        border-bottom: 1px solid #f1f5f9; transition: background .1s;
    }
    .cust-ac-item:last-child { border-bottom: none; }
    .cust-ac-item:hover, .cust-ac-item.active { background: #eff6ff; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="fw-bold mb-1">Returns Management</h5>
        <p class="text-muted small mb-0">Record product sales returns, issue refunds, and automatically restock returned units.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newReturnModal">
        <?= icon('arrow-return-left', 16) ?>
        New Return
    </button>
</div>

<!-- Returns History Table -->
<div class="card card-table mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0">Return History</h6>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Return No</th>
                    <th>Sale Order</th>
                    <th>Product</th>
                    <th class="text-center">Qty Restocked</th>
                    <th class="text-end">Refund Unit Price</th>
                    <th class="text-end">Total Refunded</th>
                    <th>Reason</th>
                    <th>Date</th>
                    <th class="text-center" style="width:60px">PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($returns)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">No product returns recorded yet. Click "New Return" above to add.</td></tr>
                <?php else: foreach ($returns as $r): ?>
                    <tr>
                        <td><span class="font-monospace fw-semibold text-danger"><?= e($r['return_no']) ?></span></td>
                        <td><?= $r['order_no'] ? '<span class="font-monospace fw-semibold">' . e($r['order_no']) . '</span>' : '<span class="text-muted">N/A</span>' ?></td>
                        <td>
                            <div class="fw-medium"><?= e($r['product_name'] ?? 'Unknown Product') ?></div>
                            <small class="text-muted font-monospace"><?= e($r['sku'] ?? '') ?></small>
                        </td>
                        <td class="text-center fw-bold text-success">+<?= e($r['quantity']) ?></td>
                        <td class="text-end">Rs <?= number_format($r['refund_price'], 2) ?></td>
                        <td class="text-end fw-bold text-dark">Rs <?= number_format($r['line_total'], 2) ?></td>
                        <td class="text-muted small"><?= e($r['reason'] ?: 'None specified') ?></td>
                        <td class="text-muted small"><?= e($r['created_at']) ?></td>
                        <td class="text-center">
                            <a href="/controllers/return_pdf.php?return_no=<?= urlencode($r['return_no']) ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Download PDF">
                                    <?= icon('file-earmark-text', 14) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Return Modal -->
<div class="modal fade" id="newReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h6 class="modal-title fw-bold">Record Product Return</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="returnForm">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label">Linked Sale Order (Optional)</label>
                        <div class="cust-ac-wrap">
                            <input type="text" id="soSearch" autocomplete="off" placeholder="Search sale order no or customer name..." class="form-control font-monospace">
                            <input type="hidden" id="sale_order_id">
                            <div id="soAcList" class="cust-ac-dropdown d-none"></div>
                        </div>
                        <small class="text-muted">Linking a sale order will automatically import order products.</small>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3">Items to Return & Restock</h6>

                    <!-- Search Product to add manually if needed -->
                    <div class="cust-ac-wrap mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><?= icon('search', 14) ?></span>
                            <input type="text" id="retProdSearch" autocomplete="off" placeholder="Search product to add to return list..." class="form-control">
                        </div>
                        <div id="retAcList" class="cust-ac-dropdown d-none"></div>
                    </div>

                    <!-- Table of Items to return -->
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center" style="width:100px">Return Qty</th>
                                    <th style="width:140px">Refund Price (Rs)</th>
                                    <th>Reason</th>
                                    <th class="text-end" style="width:120px">Total Refund</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="returnItemsBody">
                                <tr><td colspan="6" class="text-center text-muted py-3">No items added to return. Search product or select a Sale Order.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                        <span class="fw-bold">Total Refund Amount:</span>
                        <span id="returnTotalAmount" class="fs-5 fw-bold text-danger">Rs 0.00</span>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveReturnBtn" class="btn btn-primary fw-semibold">Process Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const PRODUCTS = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name'], 'sku' => $p['sku'], 'price' => (float)$p['sale_price']], $products), JSON_HEX_TAG | JSON_HEX_APOS) ?>;

let returnItems = [];
let returnSaleOrderNo = '';

function fmt(n) { return 'Rs ' + Number(n).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// Sale order autocomplete
const soSearch = document.getElementById('soSearch');
const soAcList = document.getElementById('soAcList');

soSearch.addEventListener('input', () => {
    const q = soSearch.value.trim();
    if (!q) { soAcList.classList.add('d-none'); return; }
    fetch('/api/sale_order_search.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            if (!data.length) { soAcList.classList.add('d-none'); return; }
            soAcList.innerHTML = data.map(o =>
                '<div class="cust-ac-item" onclick="selectSaleOrder('+o.id+', \''+esc(o.order_no)+'\')">' +
                    '<div><span class="font-monospace fw-bold text-primary">'+esc(o.order_no)+'</span> <small class="text-muted">('+esc(o.customer_name||'Walk-in')+')</small></div>' +
                    '<span class="fw-bold small">Rs '+Number(o.total).toLocaleString('en-PK')+'</span>' +
                '</div>').join('');
            soAcList.classList.remove('d-none');
        });
});

function selectSaleOrder(orderId, orderNo) {
    document.getElementById('sale_order_id').value = orderId;
    soSearch.value = orderNo;
    soAcList.classList.add('d-none');
    returnSaleOrderNo = orderNo;

    // Fetch line items for this sale order
    fetch('/api/sale_order_items.php?order_id=' + orderId)
        .then(r => r.json())
        .then(items => {
            returnItems = [];
            items.forEach(it => {
                returnItems.push({
                    product_id: it.product_id,
                    name: it.product_name,
                    sku: it.sku || '',
                    qty: 1,
                    refund_price: parseFloat(it.price) || 0,
                    reason: 'Customer return'
                });
            });
            renderReturnRows();
        });
}

// Product search autocomplete
const rSearch = document.getElementById('retProdSearch');
const rAcList = document.getElementById('retAcList');

rSearch.addEventListener('input', () => {
    const q = rSearch.value.trim().toLowerCase();
    if (!q) { rAcList.classList.add('d-none'); return; }
    const res = PRODUCTS.filter(p => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)).slice(0, 8);
    if (!res.length) { rAcList.classList.add('d-none'); return; }
    rAcList.innerHTML = res.map(p =>
        '<div class="cust-ac-item" onclick="addReturnProduct('+p.id+')">' +
            '<div><span class="fw-semibold small">'+esc(p.name)+'</span> <small class="text-muted font-monospace">('+esc(p.sku)+')</small></div>' +
            '<span class="text-primary fw-bold small">Sale Price: Rs '+p.price.toLocaleString('en-PK')+'</span>' +
        '</div>').join('');
    rAcList.classList.remove('d-none');
});

function addReturnProduct(id) {
    const p = PRODUCTS.find(x => x.id === id);
    if (!p) return;
    returnItems.push({ product_id: p.id, name: p.name, sku: p.sku, qty: 1, refund_price: p.price, reason: '' });
    rSearch.value = '';
    rAcList.classList.add('d-none');
    renderReturnRows();
}

function renderReturnRows() {
    const body = document.getElementById('returnItemsBody');
    body.innerHTML = '';
    if (!returnItems.length) {
        body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No items added to return. Search product or select a Sale Order.</td></tr>';
        document.getElementById('returnTotalAmount').textContent = 'Rs 0.00';
        return;
    }
    let grandTotal = 0;
    returnItems.forEach((it, idx) => {
        const lineTotal = it.qty * it.refund_price;
        grandTotal += lineTotal;
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><div class="fw-semibold small">'+esc(it.name)+'</div><small class="text-muted font-monospace">'+esc(it.sku || '')+'</small></td>' +
            '<td><input type="number" min="1" value="'+it.qty+'" onchange="updateReturnItem('+idx+', \'qty\', this.value)" class="form-control form-control-sm text-center"></td>' +
            '<td><input type="number" min="0" step="0.01" value="'+it.refund_price+'" onchange="updateReturnItem('+idx+', \'refund_price\', this.value)" class="form-control form-control-sm fw-semibold"></td>' +
            '<td><input type="text" value="'+esc(it.reason)+'" onchange="updateReturnItem('+idx+', \'reason\', this.value)" placeholder="Defective, wrong item..." class="form-control form-control-sm"></td>' +
            '<td class="text-end fw-bold text-danger">'+fmt(lineTotal)+'</td>' +
            '<td class="text-end"><button type="button" onclick="removeReturnItem('+idx+')" class="btn btn-sm btn-outline-danger">&#10005;</button></td>';
        body.appendChild(tr);
    });
    document.getElementById('returnTotalAmount').textContent = fmt(grandTotal);
}

function updateReturnItem(idx, field, val) {
    if (field === 'qty') returnItems[idx].qty = Math.max(1, parseInt(val) || 1);
    if (field === 'refund_price') returnItems[idx].refund_price = Math.max(0, parseFloat(val) || 0);
    if (field === 'reason') returnItems[idx].reason = val;
    renderReturnRows();
}

function removeReturnItem(idx) {
    returnItems.splice(idx, 1);
    renderReturnRows();
}

document.getElementById('returnForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!returnItems.length) {
        showModal('Validation Error', 'Please add at least one item to return.', 'error');
        return;
    }

    const btn = document.getElementById('saveReturnBtn');
    btn.disabled = true;
    btn.textContent = 'Processing...';

    const payload = {
        sale_order_id: document.getElementById('sale_order_id').value,
        items: returnItems
    };

    fetch('/controllers/return_create.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showModal('Success', d.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showModal('Error', d.message, 'error');
            btn.disabled = false;
            btn.textContent = 'Process Return';
        }
    })
    .catch(() => {
        showModal('Error', 'Error processing return.', 'error');
        btn.disabled = false;
        btn.textContent = 'Process Return';
    });
});
</script>
<?php
render_page('Returns', ob_get_clean(), modal_markup_html());
