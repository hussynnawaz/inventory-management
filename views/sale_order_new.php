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
    .field-locked { background:#f1f5f9; color:#64748b; }
    .ac-wrap { position:relative; }
    .ac-list { position:absolute; z-index:30; left:0; right:0; background:#fff; border:1px solid #e2e8f0; border-radius:.5rem; margin-top:2px; max-height:200px; overflow:auto; box-shadow:0 10px 25px rgba(0,0,0,.1); }
    .ac-item { padding:.5rem .75rem; cursor:pointer; font-size:.875rem; }
    .ac-item:hover, .ac-item.active { background:#eff6ff; }
</style>

<div class="max-w-4xl mx-auto space-y-6">
    <form id="saleForm" class="space-y-6">
        <!-- Header -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date &amp; Time</label>
                    <input type="text" value="<?= e($now) ?>" readonly class="w-full rounded-lg border border-slate-200 field-locked px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Order No</label>
                    <input type="text" value="<?= e($previewNo) ?>" readonly class="w-full rounded-lg border border-slate-200 field-locked px-3 py-2 font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Customer Code</label>
                    <input type="text" id="customer_code" autocomplete="off" placeholder="Type code e.g. CUST001"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Customer details (auto-fetched + locked) -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-slate-800">Customer Details</h2>
                <span id="fetchBadge" class="hidden text-xs px-2 py-1 rounded-full bg-green-50 text-green-700">Auto-filled</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="customerFields">
                <?php
                $custFields = [
                    'customer_name' => 'Customer Name',
                    'contact' => 'Contact Number',
                    'delivery_route' => 'Delivery Route',
                    'salesman' => 'Sales Man',
                    'ntn_no' => 'NTN No',
                    'sales_tax_no' => 'Sales Tax No',
                    'cnic' => 'CNIC',
                    'address' => 'Address',
                ];
                foreach ($custFields as $k => $label): ?>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1"><?= e($label) ?></label>
                    <input type="text" name="<?= $k ?>" id="<?= $k ?>" readonly class="w-full rounded-lg border border-slate-200 field-locked px-3 py-2">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Items -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-semibold text-slate-800 mb-4">Sale Items</h2>
            <div class="ac-wrap mb-3">
                <input type="text" id="productSearch" autocomplete="off" placeholder="Type product name to search..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div id="acList" class="ac-list hidden"></div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="itemsTable">
                    <thead class="text-left text-slate-500 bg-slate-50">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2 w-24">Qty</th>
                            <th class="px-3 py-2 w-32">Price</th>
                            <th class="px-3 py-2 text-right w-32">Line Total</th>
                            <th class="px-3 py-2 w-16"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>

        <!-- Tax + totals -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Sales Tax %</label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="0" step="0.01" id="sales_tax_pct" value="0" class="w-28 rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-slate-500 text-sm">=</span>
                            <span id="salesTaxAmt" class="font-semibold text-slate-700">Rs 0.00</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">GST %</label>
                        <div class="flex items-center gap-2">
                            <input type="number" min="0" step="0.01" id="gst_pct" value="0" class="w-28 rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <span class="text-slate-500 text-sm">=</span>
                            <span id="gstAmt" class="font-semibold text-slate-700">Rs 0.00</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-2 md:pt-7">
                    <div class="flex justify-between text-sm"><span class="text-slate-500">Subtotal</span><span id="subtotal" class="font-medium">Rs 0.00</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-500">Sales Tax</span><span id="stRow" class="font-medium">Rs 0.00</span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-500">GST</span><span id="gstRow" class="font-medium">Rs 0.00</span></div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><span class="font-semibold text-slate-800">Net Total</span><span id="netTotal" class="font-bold text-blue-700">Rs 0.00</span></div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="/views/sales.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-4 py-2.5 rounded-lg">Cancel</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg">Save &amp; Generate PDF</button>
        </div>
    </form>
</div>

<script>
const PRODUCTS = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name'], 'price' => (float)$p['sale_price'], 'qty' => (int)$p['quantity']], $products), JSON_HEX_TAG | JSON_HEX_APOS) ?>;

function fmt(n) { return 'Rs ' + Number(n).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

let items = [];

function recalc() {
    let subtotal = 0;
    items.forEach(it => { subtotal += it.qty * it.price; });
    const stPct = parseFloat(document.getElementById('sales_tax_pct').value) || 0;
    const gstPct = parseFloat(document.getElementById('gst_pct').value) || 0;
    const stAmt = subtotal * stPct / 100;
    const gstAmt = subtotal * gstPct / 100;
    const net = subtotal + stAmt + gstAmt;
    document.getElementById('subtotal').textContent = fmt(subtotal);
    document.getElementById('salesTaxAmt').textContent = fmt(stAmt);
    document.getElementById('stRow').textContent = fmt(stAmt);
    document.getElementById('gstAmt').textContent = fmt(gstAmt);
    document.getElementById('gstRow').textContent = fmt(gstAmt);
    document.getElementById('netTotal').textContent = fmt(net);
    renderRows();
}

function renderRows() {
    const body = document.getElementById('itemsBody');
    body.innerHTML = '';
    items.forEach((it, idx) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="px-3 py-2">${it.name}</td>
            <td class="px-3 py-2"><input type="number" min="1" value="${it.qty}" data-i="${idx}" class="qty-input w-20 rounded-lg border border-slate-300 px-2 py-1.5"></td>
            <td class="px-3 py-2"><input type="number" min="0" step="0.01" value="${it.price}" data-i="${idx}" class="price-input w-28 rounded-lg border border-slate-300 px-2 py-1.5"></td>
            <td class="px-3 py-2 text-right">${fmt(it.qty * it.price)}</td>
            <td class="px-3 py-2 text-right"><button type="button" class="text-red-600 hover:underline text-sm" data-rm="${idx}">Remove</button></td>`;
        body.appendChild(tr);
    });
    body.querySelectorAll('.qty-input').forEach(el => el.addEventListener('input', e => {
        items[e.target.dataset.i].qty = parseFloat(e.target.value) || 0; recalc();
    }));
    body.querySelectorAll('.price-input').forEach(el => el.addEventListener('input', e => {
        items[e.target.dataset.i].price = parseFloat(e.target.value) || 0; recalc();
    }));
    body.querySelectorAll('[data-rm]').forEach(el => el.addEventListener('click', e => {
        items.splice(e.target.dataset.rm, 1); recalc();
    }));
}

// Product autocomplete
const search = document.getElementById('productSearch');
const acList = document.getElementById('acList');
let acIndex = -1, acResults = [];

search.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    if (!q) { acList.classList.add('hidden'); return; }
    acResults = PRODUCTS.filter(p => p.name.toLowerCase().includes(q)).slice(0, 8);
    if (!acResults.length) { acList.classList.add('hidden'); return; }
    acList.innerHTML = acResults.map((p, i) =>
        `<div class="ac-item" data-i="${i}">${p.name} <span class="text-slate-400">- Rs ${p.price}</span></div>`).join('');
    acList.classList.remove('hidden');
    acIndex = -1;
    acList.querySelectorAll('.ac-item').forEach(el => el.addEventListener('click', () => pick(parseInt(el.dataset.i))));
});

search.addEventListener('keydown', e => {
    if (acList.classList.contains('hidden')) return;
    const items = acList.querySelectorAll('.ac-item');
    if (e.key === 'ArrowDown') { e.preventDefault(); acIndex = Math.min(acIndex+1, items.length-1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); acIndex = Math.max(acIndex-1, 0); }
    else if (e.key === 'Enter' && acIndex >= 0) { e.preventDefault(); pick(acIndex); return; }
    else if (e.key === 'Enter') { e.preventDefault(); if (acResults[0]) pick(0); return; }
    items.forEach((el,i) => el.classList.toggle('active', i === acIndex));
});

function pick(i) {
    const p = acResults[i];
    if (!p) return;
    if (items.some(it => it.id === p.id)) { showModal('Notice', p.name + ' is already added.', 'error'); }
    else items.push({ id: p.id, name: p.name, qty: 1, price: p.price });
    search.value = '';
    acList.classList.add('hidden');
    recalc();
}

document.getElementById('sales_tax_pct').addEventListener('input', recalc);
document.getElementById('gst_pct').addEventListener('input', recalc);

// Customer code auto-fetch + lock fields
document.getElementById('customer_code').addEventListener('blur', function () {
    const code = this.value.trim();
    if (!code) return;
    fetch('/api/customer_lookup.php?code=' + encodeURIComponent(code))
        .then(r => r.json())
        .then(d => {
            const fields = ['customer_name','contact','delivery_route','salesman','ntn_no','sales_tax_no','cnic','address'];
            if (d.found) {
                fields.forEach(f => document.getElementById(f).value = (d.customer[f] || ''));
                document.getElementById('fetchBadge').classList.remove('hidden');
            } else {
                fields.forEach(f => document.getElementById(f).value = '');
                document.getElementById('fetchBadge').classList.add('hidden');
                showModal('Customer Not Found', 'No customer exists with code "' + code + '". You can still enter details manually but they won\'t be saved to a customer record.', 'error');
                fields.forEach(f => { const el = document.getElementById(f); el.readOnly = false; el.classList.remove('field-locked'); });
            }
        })
        .catch(() => showModal('Error', 'Could not fetch customer details.', 'error'));
});

// Submit
document.getElementById('saleForm').addEventListener('submit', function (e) {
    e.preventDefault();
    if (!items.length) { showModal('Error', 'Please add at least one sale item.', 'error'); return; }
    const f = e.target;
    const data = {
        customer_code: document.getElementById('customer_code').value,
        customer_name: f.customer_name.value, contact: f.contact.value,
        delivery_route: f.delivery_route.value, salesman: f.salesman.value,
        ntn_no: f.ntn_no.value, sales_tax_no: f.sales_tax_no.value,
        cnic: f.cnic.value, address: f.address.value,
        sales_tax_pct: document.getElementById('sales_tax_pct').value,
        gst_pct: document.getElementById('gst_pct').value,
        items: items.map(it => ({ product_id: it.id, qty: it.qty, price: it.price })),
    };
    fetch('/controllers/sale_order_create.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showModal('Success', d.message + ' (Order ' + d.order_no + ')', 'success');
            setTimeout(() => { window.location.href = '/controllers/sale_order_pdf.php?id=' + d.order_id; }, 900);
        } else {
            showModal('Error', d.message, 'error');
        }
    })
    .catch(() => showModal('Error', 'Submission failed.', 'error'));
});

recalc();
</script>
<?php
$content = ob_get_clean();
render_page('New Sale Order', $content, modal_markup_html());
