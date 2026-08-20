<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/modal.php';
require_login();

$products = $pdo->query('SELECT id, name, sale_price, quantity FROM products ORDER BY name ASC')->fetchAll();
$now = date('Y-m-d H:i:s');
$previewNo = 'SO-' . date('Ymd') . '-' . str_pad((int)$pdo->query('SELECT COUNT(*)+1 FROM sale_orders')->fetchColumn(), 4, '0', STR_PAD_LEFT);

ob_start();
?>
<style>
    .field-locked { background:#f8fafc !important; color:#64748b !important; cursor:not-allowed; }
    .receipt-dashed { border-top: 2px dashed #dee2e6; }
    .qty-btn { width: 1.75rem; height: 1.75rem; display: flex; align-items: center; justify-content: center; border-radius: .375rem; border: 1px solid #dee2e6; background: #fff; cursor: pointer; transition: all 0.15s; font-weight: bold; }
    .qty-btn:hover { background: #e9ecef; border-color: #adb5bd; }
    .table-row-enter { animation: fadeSlideIn 0.3s ease-out; }
    @keyframes fadeSlideIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }

    /* Customer autocomplete */
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
    .cust-ac-item.active { outline: 2px solid #3b82f6; outline-offset: -2px; }
    .cust-ac-empty { padding: 1rem; text-align: center; color: #94a3b8; font-size: .85rem; }
</style>

<div class="mb-3">
    <!-- Info Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/><path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z"/></svg>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">New Sale Order</h6>
                        <small class="text-muted">Order: <span class="font-monospace fw-semibold"><?= e($previewNo) ?></span> &middot; <?= e($now) ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="saleForm">
        <div class="row g-4">
            <!-- Left Side (Col 8) -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h6 class="fw-bold mb-0">Sale Line Items</h6>
                                <small class="text-muted">Search products to add items.</small>
                            </div>
                            <span id="itemsCounter" class="badge bg-primary bg-opacity-10 text-primary">0 Items</span>
                        </div>

                        <!-- Product Search -->
                        <div class="cust-ac-wrap mb-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg></span>
                                <input type="text" id="productSearch" autocomplete="off" placeholder="Type product name to search..." class="form-control">
                            </div>
                            <div id="acList" class="cust-ac-dropdown d-none"></div>
                        </div>

                        <!-- Products Table -->
                        <div id="tableContainer" class="table-responsive d-none">
                            <table class="table table-sm align-middle mb-0" id="itemsTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="small fw-semibold">Product Name</th>
                                        <th class="small fw-semibold text-center" style="width:140px">Quantity</th>
                                        <th class="small fw-semibold" style="width:160px">Unit Price</th>
                                        <th class="small fw-semibold text-end" style="width:140px">Line Total</th>
                                        <th style="width:50px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="text-center py-5 border border-2 border-dashed rounded-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="text-muted mb-2" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v2A1.5 1.5 0 0 1 14.5 7H9v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7H1.5A1.5 1.5 0 0 1 0 5.5z"/></svg>
                            <h6 class="text-muted small mb-1">No items added</h6>
                            <small class="text-muted">Search and select products above.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side (Col 4) -->
            <div class="col-lg-4">
                <!-- Customer Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold mb-0">Customer</h6>
                            <span id="fetchBadge" class="badge bg-success d-none">SELECTED</span>
                        </div>

                        <!-- Customer Search -->
                        <div class="mb-3 cust-ac-wrap">
                            <label class="form-label small fw-medium">Search Customer</label>
                            <div class="position-relative">
                                <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg>
                                </span>
                                <input type="text" id="customer_code" autocomplete="off" placeholder="Type code, name or contact..." class="form-control ps-9" style="font-size:.85rem">
                            </div>
                            <div id="custAcList" class="cust-ac-dropdown d-none"></div>
                        </div>

                        <!-- Customer Summary Card (hidden until selected) -->
                        <div id="customerSummaryCard" class="d-none mb-3 p-2 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 d-flex align-items-center gap-3">
                            <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center fw-bold small" style="width:36px;height:36px;flex-shrink:0" id="customerAvatar">C</div>
                            <div class="overflow-hidden">
                                <div class="fw-bold small text-truncate" id="summary_name">Customer</div>
                                <small class="text-muted text-truncate d-block" id="summary_contact">Contact No</small>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <ul class="nav nav-tabs nav-tabs-sm mb-3">
                            <li class="nav-item">
                                <button type="button" onclick="showCustTab('primary')" id="tabBtn_primary" class="nav-link active small fw-medium">Primary</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" onclick="showCustTab('business')" id="tabBtn_business" class="nav-link small fw-medium">Tax / CNIC</button>
                            </li>
                            <li class="nav-item">
                                <button type="button" onclick="showCustTab('logistics')" id="tabBtn_logistics" class="nav-link small fw-medium">Route</button>
                            </li>
                        </ul>

                        <!-- Tab Panels -->
                        <div id="customerFields">
                            <div id="tab_primary">
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-medium">Customer Name</label>
                                    <input type="text" name="customer_name" id="customer_name" readonly class="form-control field-locked" placeholder="--">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-medium">Contact Number</label>
                                    <input type="text" name="contact" id="contact" readonly class="form-control field-locked" placeholder="--">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small text-muted fw-medium">Billing Address</label>
                                    <input type="text" name="address" id="address" readonly class="form-control field-locked" placeholder="--">
                                </div>
                            </div>
                            <div id="tab_business" class="d-none">
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-medium">CNIC</label>
                                    <input type="text" name="cnic" id="cnic" readonly class="form-control field-locked" placeholder="--">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-medium">NTN No</label>
                                    <input type="text" name="ntn_no" id="ntn_no" readonly class="form-control field-locked" placeholder="--">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small text-muted fw-medium">Sales Tax No</label>
                                    <input type="text" name="sales_tax_no" id="sales_tax_no" readonly class="form-control field-locked" placeholder="--">
                                </div>
                            </div>
                            <div id="tab_logistics" class="d-none">
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-medium">Delivery Route</label>
                                    <input type="text" name="delivery_route" id="delivery_route" readonly class="form-control field-locked" placeholder="--">
                                </div>
                                <div class="mb-0">
                                    <label class="form-label small text-muted fw-medium">Sales Man</label>
                                    <input type="text" name="salesman" id="salesman" readonly class="form-control field-locked" placeholder="--">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Receipt Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary"></div>
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Billing Summary</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label small fw-medium">Sales Tax %</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" min="0" step="0.01" id="sales_tax_pct" value="0" class="form-control fw-semibold">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-medium">GST %</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" min="0" step="0.01" id="gst_pct" value="0" class="form-control fw-semibold">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="bg-light rounded-3 p-3 mb-4">
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span id="subtotal" class="fw-semibold">Rs 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between small mb-2">
                                <span class="text-muted">Sales Tax</span>
                                <span id="stRow" class="fw-semibold">Rs 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between small mb-3">
                                <span class="text-muted">GST</span>
                                <span id="gstRow" class="fw-semibold">Rs 0.00</span>
                            </div>
                            <div class="receipt-dashed pt-3 d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Total</span>
                                <span id="netTotal" class="fs-5 fw-bold text-primary">Rs 0.00</span>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" id="submitBtn" class="btn btn-primary fw-semibold py-2">
                                Save Order
                            </button>
                            <a href="/views/sales.php" class="btn btn-light fw-semibold">Cancel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
const PRODUCTS = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name'], 'price' => (float)$p['sale_price'], 'qty' => (int)$p['quantity']], $products), JSON_HEX_TAG | JSON_HEX_APOS) ?>;

function fmt(n) { return 'Rs ' + Number(n).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

let items = [];
let selectedCustomer = null;

// ── Calc ──
function recalc() {
    let subtotal = 0;
    items.forEach(it => { subtotal += it.qty * it.price; });
    const stPct = parseFloat(document.getElementById('sales_tax_pct').value) || 0;
    const gstPct = parseFloat(document.getElementById('gst_pct').value) || 0;
    const stAmt = subtotal * stPct / 100;
    const gstAmt = subtotal * gstPct / 100;
    const net = subtotal + stAmt + gstAmt;
    document.getElementById('subtotal').textContent = fmt(subtotal);
    document.getElementById('stRow').textContent = fmt(stAmt);
    document.getElementById('gstRow').textContent = fmt(gstAmt);
    document.getElementById('netTotal').textContent = fmt(net);
    document.getElementById('itemsCounter').textContent = items.length + (items.length === 1 ? ' Item' : ' Items');
    renderRows();
}

function renderRows() {
    const tc = document.getElementById('tableContainer');
    const es = document.getElementById('emptyState');
    const body = document.getElementById('itemsBody');
    body.innerHTML = '';
    if (!items.length) { tc.classList.add('d-none'); es.classList.remove('d-none'); return; }
    tc.classList.remove('d-none'); es.classList.add('d-none');
    items.forEach((it, idx) => {
        const tr = document.createElement('tr');
        tr.className = 'table-row-enter';
        tr.innerHTML =
            '<td><div class="fw-semibold small">' + esc(it.name) + '</div><small class="text-muted font-monospace">#' + String(it.id).padStart(4,'0') + '</small></td>' +
            '<td class="text-center"><div class="d-inline-flex align-items-center gap-1">' +
                '<button type="button" class="qty-btn btn-qty-dec" data-i="'+idx+'">&#8722;</button>' +
                '<input type="number" min="1" value="'+it.qty+'" data-i="'+idx+'" class="qty-input form-control form-control-sm text-center" style="width:60px">' +
                '<button type="button" class="qty-btn btn-qty-inc" data-i="'+idx+'">+</button>' +
            '</div></td>' +
            '<td><div class="input-group input-group-sm"><span class="input-group-text bg-white" style="font-size:.75rem">Rs</span>' +
                '<input type="number" min="0" step="0.01" value="'+it.price+'" data-i="'+idx+'" class="price-input form-control fw-semibold"></div></td>' +
            '<td class="text-end fw-bold">'+fmt(it.qty*it.price)+'</td>' +
            '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-rm="'+idx+'" title="Remove">&#10005;</button></td>';
        body.appendChild(tr);
    });
    body.querySelectorAll('.btn-qty-dec').forEach(b=>b.addEventListener('click',e=>{const i=parseInt(e.currentTarget.dataset.i);if(items[i].qty>1){items[i].qty--;recalc();}}));
    body.querySelectorAll('.btn-qty-inc').forEach(b=>b.addEventListener('click',e=>{items[parseInt(e.currentTarget.dataset.i)].qty++;recalc();}));
    body.querySelectorAll('.qty-input').forEach(el=>el.addEventListener('input',e=>{items[e.target.dataset.i].qty=parseFloat(e.target.value)||0;recalc();}));
    body.querySelectorAll('.price-input').forEach(el=>el.addEventListener('input',e=>{items[e.target.dataset.i].price=parseFloat(e.target.value)||0;recalc();}));
    body.querySelectorAll('[data-rm]').forEach(el=>el.addEventListener('click',e=>{items.splice(parseInt(e.currentTarget.dataset.rm),1);recalc();}));
}

// ── Product autocomplete ──
const pSearch = document.getElementById('productSearch');
const pAcList = document.getElementById('acList');
let pAcIdx = -1, pAcRes = [];

pSearch.addEventListener('input', () => {
    const q = pSearch.value.trim().toLowerCase();
    if (!q) { pAcList.classList.add('d-none'); return; }
    pAcRes = PRODUCTS.filter(p => p.name.toLowerCase().includes(q)).slice(0, 8);
    if (!pAcRes.length) { pAcList.classList.add('d-none'); return; }
    pAcList.innerHTML = pAcRes.map((p, i) =>
        '<div class="cust-ac-item" data-i="'+i+'">' +
            '<div class="d-flex align-items-center gap-2"><span class="fw-semibold small">'+esc(p.name)+'</span><small class="text-muted font-monospace">#'+String(p.id).padStart(4,'0')+'</small></div>' +
            '<div class="d-flex gap-2 align-items-center"><span class="badge bg-light text-secondary" style="font-size:.7rem">Stock: '+p.qty+'</span><span class="text-primary fw-bold small">Rs '+p.price.toLocaleString('en-PK')+'</span></div>' +
        '</div>').join('');
    pAcList.classList.remove('d-none');
    pAcIdx = -1;
    pAcList.querySelectorAll('.cust-ac-item').forEach(el => el.addEventListener('click', () => pickProduct(parseInt(el.dataset.i))));
});

pSearch.addEventListener('keydown', e => {
    if (pAcList.classList.contains('d-none')) return;
    const els = pAcList.querySelectorAll('.cust-ac-item');
    if (e.key === 'ArrowDown') { e.preventDefault(); pAcIdx = Math.min(pAcIdx+1, els.length-1); highlightItem(els, pAcIdx); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); pAcIdx = Math.max(pAcIdx-1, 0); highlightItem(els, pAcIdx); }
    else if (e.key === 'Enter' && pAcIdx >= 0) { e.preventDefault(); pickProduct(pAcIdx); }
    else if (e.key === 'Escape') { pAcList.classList.add('d-none'); }
});

function highlightItem(els, idx) { els.forEach((el,i) => el.classList.toggle('active', i === idx)); }

function pickProduct(i) {
    const p = pAcRes[i];
    if (!p) return;
    if (items.some(it => it.id === p.id)) { showModal('Notice', p.name + ' is already in the order.', 'error'); }
    else items.push({ id: p.id, name: p.name, qty: 1, price: p.price });
    pSearch.value = '';
    pAcList.classList.add('d-none');
    recalc();
}

// ── Customer autocomplete ──
const cSearch = document.getElementById('customer_code');
const cAcList = document.getElementById('custAcList');
let cAcIdx = -1, cAcRes = [], cTimer = null;

cSearch.addEventListener('input', function() {
    clearTimeout(cTimer);
    const q = this.value.trim();
    if (q.length < 1) { cAcList.classList.add('d-none'); return; }
    cTimer = setTimeout(() => {
        fetch('/api/customer_search.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(res => {
                cAcRes = res;
                if (!res.length) {
                    cAcList.innerHTML = '<div class="cust-ac-empty">No customers found. <a href="/views/customers.php" target="_blank">Add one?</a></div>';
                    cAcList.classList.remove('d-none');
                    return;
                }
                cAcList.innerHTML = res.map((c, i) =>
                    '<div class="cust-ac-item" data-i="'+i+'">' +
                        '<div class="d-flex align-items-center gap-2">' +
                            '<span class="font-monospace fw-bold text-primary" style="font-size:.8rem">'+esc(c.code)+'</span>' +
                            '<span class="fw-medium">'+esc(c.customer_name)+'</span>' +
                        '</div>' +
                        '<small class="text-muted">'+esc(c.contact||'')+'</small>' +
                    '</div>').join('');
                cAcList.classList.remove('d-none');
                cAcIdx = -1;
                cAcList.querySelectorAll('.cust-ac-item').forEach(el => {
                    el.addEventListener('mousedown', e => { e.preventDefault(); pickCustomer(parseInt(el.dataset.i)); });
                    el.addEventListener('mouseenter', function() {
                        cAcIdx = parseInt(this.dataset.i);
                        cAcList.querySelectorAll('.cust-ac-item').forEach(x => x.classList.remove('active'));
                        this.classList.add('active');
                    });
                });
            });
    }, 200);
});

cSearch.addEventListener('keydown', function(e) {
    if (cAcList.classList.contains('d-none')) return;
    const els = cAcList.querySelectorAll('.cust-ac-item');
    if (!els.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); cAcIdx = Math.min(cAcIdx + 1, els.length - 1); cHighlight(els); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); cAcIdx = Math.max(cAcIdx - 1, 0); cHighlight(els); }
    else if (e.key === 'Enter') { e.preventDefault(); if (cAcIdx >= 0) pickCustomer(cAcIdx); else if (cAcRes.length) pickCustomer(0); }
    else if (e.key === 'Escape') { cAcList.classList.add('d-none'); }
});

cSearch.addEventListener('blur', () => { setTimeout(() => cAcList.classList.add('d-none'), 150); });

function cHighlight(els) {
    els.forEach((el, i) => el.classList.toggle('active', i === cAcIdx));
    if (els[cAcIdx]) els[cAcIdx].scrollIntoView({ block: 'nearest' });
}

function pickCustomer(i) {
    const c = cAcRes[i];
    if (!c) return;
    selectedCustomer = c;
    cSearch.value = c.code;
    cAcList.classList.add('d-none');

    const fields = ['customer_name','contact','delivery_route','salesman','ntn_no','sales_tax_no','cnic','address'];
    fields.forEach(f => document.getElementById(f).value = c[f] || '');
    document.getElementById('fetchBadge').classList.remove('d-none');

    const nm = c.customer_name || 'Customer';
    document.getElementById('summary_name').textContent = nm;
    document.getElementById('summary_contact').textContent = c.contact || 'No Contact';
    document.getElementById('customerAvatar').textContent = nm.charAt(0).toUpperCase();
    document.getElementById('customerSummaryCard').classList.remove('d-none');

    fields.forEach(f => {
        const el = document.getElementById(f);
        el.readOnly = true;
        el.classList.add('field-locked');
    });
}

// ── Tabs ──
function showCustTab(tabName) {
    ['primary','business','logistics'].forEach(t => {
        document.getElementById('tab_'+t).classList.toggle('d-none', t !== tabName);
        document.getElementById('tabBtn_'+t).classList.toggle('active', t === tabName);
    });
}

document.getElementById('sales_tax_pct').addEventListener('input', recalc);
document.getElementById('gst_pct').addEventListener('input', recalc);

// ── Submit ──
document.getElementById('saleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!selectedCustomer) { showModal('Error', 'Please select a customer first.', 'error'); cSearch.focus(); return; }
    if (!items.length) { showModal('Error', 'Please add at least one product.', 'error'); return; }

    const btn = document.getElementById('submitBtn');
    btn.disabled = true; btn.textContent = 'Saving...';

    const f = e.target;
    const data = {
        customer_code: selectedCustomer.code,
        customer_name: f.customer_name.value, contact: f.contact.value,
        delivery_route: f.delivery_route.value, salesman: f.salesman.value,
        ntn_no: f.ntn_no.value, sales_tax_no: f.sales_tax_no.value,
        cnic: f.cnic.value, address: f.address.value,
        sales_tax_pct: f.sales_tax_pct.value, gst_pct: f.gst_pct.value,
        items: items.map(it => ({ product_id: it.id, qty: it.qty, price: it.price })),
    };

    fetch('/controllers/sale_order_create.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showModal('Success', d.message + '\nOrder: ' + d.order_no, 'success');
            setTimeout(() => { window.location.href = '/views/sales.php'; }, 1200);
        } else {
            showModal('Error', d.message, 'error');
            btn.disabled = false; btn.textContent = 'Save Order';
        }
    })
    .catch(() => { showModal('Error', 'Submission failed.', 'error'); btn.disabled = false; btn.textContent = 'Save Order'; });
});

recalc();
</script>
<?php
$content = ob_get_clean();
render_page('New Sale Order', $content, modal_markup_html());
