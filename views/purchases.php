<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/modal.php';
require_login();

// Fetch purchases listing
$purchases = $pdo->query('
    SELECT p.*, s.name AS supplier, u.username
    FROM purchases p
    LEFT JOIN suppliers s ON s.id = p.supplier_id
    LEFT JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC
')->fetchAll();

// Fetch products for dropdown
$products = $pdo->query('SELECT id, name, sku, cost_price, sale_price, quantity FROM products ORDER BY name ASC')->fetchAll();

// Fetch suppliers for dropdown
$suppliers = $pdo->query('SELECT id, name, phone FROM suppliers ORDER BY name ASC')->fetchAll();

$now = date('Y-m-d H:i:s');
$previewRef = 'PO-' . date('Ymd') . '-' . str_pad((int)$pdo->query('SELECT COUNT(*)+1 FROM purchases')->fetchColumn(), 4, '0', STR_PAD_LEFT);

ob_start();
?>
<style>
    .cust-ac-wrap { position: relative; }
    .cust-ac-dropdown {
        position: absolute; left: 0; right: 0; top: 100%;
        background: #fff; border: 1px solid #dee2e6; border-radius: .5rem;
        margin-top: 4px; max-height: 260px; overflow-y: auto;
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
        <h5 class="fw-bold mb-1">Purchases Management</h5>
        <p class="text-muted small mb-0">Record inventory purchases from suppliers and update cost prices & stock automatically.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newPurchaseModal">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/></svg>
        New Purchase
    </button>
</div>

<!-- Purchases History Table -->
<div class="card card-table mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="fw-bold mb-0">Purchase History</h6>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Supplier</th>
                    <th>User</th>
                    <th>Date</th>
                    <th class="text-end">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($purchases)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No purchases recorded yet. Click "New Purchase" above to add.</td></tr>
                <?php else: foreach ($purchases as $p): ?>
                    <tr>
                        <td><span class="font-monospace fw-semibold text-primary"><?= e($p['reference_no']) ?></span></td>
                        <td class="fw-medium"><?= e($p['supplier'] ?? 'Walk-in Supplier') ?></td>
                        <td class="text-muted small"><?= e($p['username'] ?? 'Admin') ?></td>
                        <td class="text-muted"><?= e($p['created_at']) ?></td>
                        <td class="text-end fw-bold text-dark">Rs <?= number_format($p['total'], 2) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Purchase Modal -->
<div class="modal fade" id="newPurchaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <h6 class="modal-title fw-bold">Record New Purchase</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="purchaseForm">
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Reference No</label>
                            <input type="text" id="purch_ref" class="form-control font-monospace" value="<?= e($previewRef) ?>" placeholder="PO-XXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <div class="input-group">
                                <select id="supplier_id" class="form-select">
                                    <option value="">-- Select Supplier --</option>
                                    <?php foreach ($suppliers as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['phone'] ?: 'No Phone') ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-secondary" onclick="quickAddSupplier()" title="Add New Supplier">+</button>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3">Purchase Line Items</h6>
                    
                    <!-- Search Product to add -->
                    <div class="cust-ac-wrap mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg></span>
                            <input type="text" id="purchProdSearch" autocomplete="off" placeholder="Search product by name or SKU to add..." class="form-control">
                        </div>
                        <div id="purchAcList" class="cust-ac-dropdown d-none"></div>
                    </div>

                    <!-- Items Table -->
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0" id="purchItemsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center" style="width:120px">Qty</th>
                                    <th style="width:160px">Cost Price (Rs)</th>
                                    <th class="text-end" style="width:140px">Line Total</th>
                                    <th style="width:50px"></th>
                                </tr>
                            </thead>
                            <tbody id="purchItemsBody">
                                <tr id="purchEmptyRow"><td colspan="5" class="text-center text-muted py-3">No products added yet. Search above to add items.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                        <span class="fw-bold">Total Purchase Amount:</span>
                        <span id="purchTotalAmount" class="fs-5 fw-bold text-primary">Rs 0.00</span>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="savePurchBtn" class="btn btn-primary fw-semibold">Save Purchase</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const PRODUCTS = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name'], 'sku' => $p['sku'], 'cost' => (float)$p['cost_price'], 'qty' => (int)$p['quantity']], $products), JSON_HEX_TAG | JSON_HEX_APOS) ?>;

let purchItems = [];

function fmt(n) { return 'Rs ' + Number(n).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// Autocomplete product search
const pSearch = document.getElementById('purchProdSearch');
const pAcList = document.getElementById('purchAcList');

pSearch.addEventListener('input', () => {
    const q = pSearch.value.trim().toLowerCase();
    if (!q) { pAcList.classList.add('d-none'); return; }
    const res = PRODUCTS.filter(p => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)).slice(0, 8);
    if (!res.length) { pAcList.classList.add('d-none'); return; }
    pAcList.innerHTML = res.map(p =>
        '<div class="cust-ac-item" onclick="addPurchProduct('+p.id+')">' +
            '<div><span class="fw-semibold small">'+esc(p.name)+'</span> <small class="text-muted font-monospace">('+esc(p.sku)+')</small></div>' +
            '<div><span class="badge bg-light text-secondary me-2">Curr Stock: '+p.qty+'</span><span class="text-primary fw-bold small">Cost: Rs '+p.cost.toLocaleString('en-PK')+'</span></div>' +
        '</div>').join('');
    pAcList.classList.remove('d-none');
});

function addPurchProduct(id) {
    const p = PRODUCTS.find(x => x.id === id);
    if (!p) return;
    if (purchItems.some(it => it.id === p.id)) {
        alert(p.name + ' is already added.');
    } else {
        purchItems.push({ id: p.id, name: p.name, sku: p.sku, qty: 1, cost: p.cost });
    }
    pSearch.value = '';
    pAcList.classList.add('d-none');
    renderPurchRows();
}

function renderPurchRows() {
    const body = document.getElementById('purchItemsBody');
    body.innerHTML = '';
    if (!purchItems.length) {
        body.innerHTML = '<tr id="purchEmptyRow"><td colspan="5" class="text-center text-muted py-3">No products added yet. Search above to add items.</td></tr>';
        document.getElementById('purchTotalAmount').textContent = 'Rs 0.00';
        return;
    }
    let grandTotal = 0;
    purchItems.forEach((it, idx) => {
        const lineTotal = it.qty * it.cost;
        grandTotal += lineTotal;
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><div class="fw-semibold small">'+esc(it.name)+'</div><small class="text-muted font-monospace">'+esc(it.sku)+'</small></td>' +
            '<td><input type="number" min="1" value="'+it.qty+'" onchange="updatePurchItem('+idx+', \'qty\', this.value)" class="form-control form-control-sm text-center"></td>' +
            '<td><input type="number" min="0" step="0.01" value="'+it.cost+'" onchange="updatePurchItem('+idx+', \'cost\', this.value)" class="form-control form-control-sm fw-semibold"></td>' +
            '<td class="text-end fw-bold">'+fmt(lineTotal)+'</td>' +
            '<td class="text-end"><button type="button" onclick="removePurchItem('+idx+')" class="btn btn-sm btn-outline-danger">&#10005;</button></td>';
        body.appendChild(tr);
    });
    document.getElementById('purchTotalAmount').textContent = fmt(grandTotal);
}

function updatePurchItem(idx, field, val) {
    const v = parseFloat(val) || 0;
    if (field === 'qty') purchItems[idx].qty = Math.max(1, Math.floor(v));
    if (field === 'cost') purchItems[idx].cost = Math.max(0, v);
    renderPurchRows();
}

function removePurchItem(idx) {
    purchItems.splice(idx, 1);
    renderPurchRows();
}

function quickAddSupplier() {
    const sName = prompt('Enter new supplier name:');
    if (sName && sName.trim()) {
        fetch('/controllers/supplier_save.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ name: sName.trim() })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const sel = document.getElementById('supplier_id');
                const opt = document.createElement('option');
                opt.value = d.supplier.id;
                opt.textContent = d.supplier.name;
                opt.selected = true;
                sel.appendChild(opt);
            } else {
                alert(d.message);
            }
        });
    }
}

document.getElementById('purchaseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!purchItems.length) {
        alert('Please add at least one product.');
        return;
    }

    const btn = document.getElementById('savePurchBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    const payload = {
        reference_no: document.getElementById('purch_ref').value,
        supplier_id: document.getElementById('supplier_id').value,
        items: purchItems.map(it => ({ product_id: it.id, qty: it.qty, cost: it.cost }))
    };

    fetch('/controllers/purchase_create.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert(d.message);
            window.location.reload();
        } else {
            alert(d.message);
            btn.disabled = false;
            btn.textContent = 'Save Purchase';
        }
    })
    .catch(() => {
        alert('Error saving purchase.');
        btn.disabled = false;
        btn.textContent = 'Save Purchase';
    });
});
</script>
<?php
render_page('Purchases', ob_get_clean(), modal_markup_html());
