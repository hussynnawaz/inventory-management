<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/modal.php';
require_login();

$customers = $pdo->query('SELECT id, code, name, contact, destination FROM customers ORDER BY name ASC')->fetchAll();

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
    .cust-ac-item { padding: 8px 14px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: .85rem; }
    .cust-ac-item:hover { background: #f0f5ff; }
    .cust-ac-item .small { color: #6b7280; font-size: .75rem; }
    .status-badge { padding: 3px 10px; border-radius: 20px; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
    .status-paid { background: #d1fae5; color: #065f46; }
    .status-partial { background: #fef3c7; color: #92400e; }
    .status-unpaid { background: #fee2e2; color: #991b1b; }
    .inv-row { cursor: pointer; transition: background .15s; }
    .inv-row:hover { background: #f0f5ff; }
    .inv-row.selected { background: #eff6ff; border-left: 3px solid #2563eb; }
    .ledger-entry { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
    .ledger-entry:last-child { border-bottom: none; }
    .payment-method-badge { padding: 2px 8px; border-radius: 12px; font-size: .7rem; font-weight: 600; }
    .method-cash { background: #dbeafe; color: #1e40af; }
    .method-bank { background: #ede9fe; color: #5b21b6; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Customer Ledger</h4>
        <small class="text-muted">Search customer or invoice to track billing, payments, and returns</small>
    </div>
</div>

<!-- Search Card (Customer ID/Name & Invoice Search) -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-medium">Search Customer</label>
                <div class="cust-ac-wrap">
                    <input type="text" id="custSearch" autocomplete="off" placeholder="Search by customer name, code or contact..." class="form-control" style="height:42px;">
                    <input type="hidden" id="custId" value="">
                    <div id="custDropdown" class="cust-ac-dropdown d-none"></div>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-medium">Search by Invoice Number</label>
                <div class="input-group" style="height:42px;">
                    <input type="text" id="invSearchInput" placeholder="e.g. SO-20261001-0001" class="form-control font-monospace" style="height:42px;">
                    <button type="button" onclick="searchInvoiceDirect()" class="btn btn-primary" style="height:42px;">
                        <?= icon('search', 16) ?>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium">Customer Code</label>
                <input type="text" id="custCode" readonly class="form-control bg-light text-muted font-monospace" style="height:42px;" placeholder="--">
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div id="summaryCards" class="row g-3 mb-4 d-none">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><?= icon('file-text', 20) ?></div>
                <div>
                    <div class="stat-label">Total Invoices</div>
                    <div class="stat-value" id="totalInvoices">0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;"><?= icon('info', 20) ?></div>
                <div>
                    <div class="stat-label">Total Sales</div>
                    <div class="stat-value" id="totalSales">Rs 0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;"><?= icon('check-circle', 20) ?></div>
                <div>
                    <div class="stat-label">Total Paid</div>
                    <div class="stat-value" id="totalPaid">Rs 0</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#fef3c7;color:#92400e;"><?= icon('info', 20) ?></div>
                <div>
                    <div class="stat-label">Outstanding</div>
                    <div class="stat-value" id="totalOutstanding">Rs 0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Selected Invoice Management Bar -->
<div id="selectedInvBar" class="card mb-4 border-primary border-2 d-none">
    <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                <?= icon('file-text', 20) ?>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold font-monospace fs-6" id="barInvNo">SO-0000</span>
                    <span id="barInvStatus" class="status-badge status-unpaid">UNPAID</span>
                </div>
                <small class="text-muted">Total: <span id="barInvTotal" class="fw-bold text-dark">Rs 0</span> &middot; Paid: <span id="barInvPaid" class="fw-bold text-success">Rs 0</span> &middot; Outstanding: <span id="barInvRem" class="fw-bold text-danger">Rs 0</span></small>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a id="barBtnView" href="#" class="btn btn-outline-primary btn-sm fw-semibold"><?= icon('eye', 14, 'me-1') ?>View</a>
            <a id="barBtnEdit" href="#" class="btn btn-outline-warning btn-sm fw-semibold"><?= icon('edit', 14, 'me-1') ?>Edit</a>
            <a id="barBtnPdf" href="#" target="_blank" class="btn btn-outline-danger btn-sm fw-semibold"><?= icon('file-text', 14, 'me-1') ?>PDF</a>
            <button type="button" id="barBtnPay" onclick="openPaymentModal(selectedInvoiceId)" class="btn btn-success btn-sm fw-semibold"><?= icon('plus-circle', 14, 'me-1') ?>Add Payment</button>
            <button type="button" onclick="openInvoiceReturnModal(selectedInvoiceId)" class="btn btn-outline-dark btn-sm fw-semibold"><?= icon('rotate-ccw', 14, 'me-1') ?>Return</button>
        </div>
    </div>
</div>

<!-- Invoice List + Payment History -->
<div id="ledgerContent" class="d-none">
    <div class="row g-4">
        <!-- Invoices List -->
        <div class="col-lg-7">
            <div class="card mb-4">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Customer Invoices</h6>
                    <small class="text-muted">Click an invoice to view &amp; manage</small>
                </div>
                <div id="invoiceList" class="card-body p-0" style="max-height:480px;overflow-y:auto;">
                    <div class="text-center py-5 text-muted">
                        <?= icon('file-text', 36, 'mb-2') ?>
                        <div class="small">Select a customer to view invoices</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0">Payment History</h6>
                    <span id="payHistoryFilterBadge" class="badge bg-light text-secondary d-none">Filtered by Invoice</span>
                </div>
                <div id="paymentHistory" class="card-body" style="max-height:480px;overflow-y:auto;">
                    <div class="text-center py-5 text-muted">
                        <div class="small">No payments recorded yet</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Customer</label>
                    <input type="text" id="payCustName" readonly class="form-control bg-light text-muted">
                </div>
                <div id="payInvoiceInfo" class="mb-3 d-none">
                    <label class="form-label small fw-medium">Invoice</label>
                    <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded">
                        <span id="payInvoiceNo" class="fw-semibold font-monospace"></span>
                        <span id="payInvoiceOutstanding" class="text-danger fw-bold"></span>
                    </div>
                    <input type="hidden" id="paySaleOrderId" value="">
                </div>
                <div id="payGeneralInfo" class="mb-3 d-none">
                    <label class="form-label small fw-medium">Outstanding Balance (All Invoices)</label>
                    <div id="payOutstanding" class="fs-5 fw-bold text-danger">Rs 0</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Payment Amount (Rs) <span class="text-danger">*</span></label>
                    <input type="number" id="payAmount" step="0.01" min="0.01" class="form-control form-control-lg" placeholder="0.00" onwheel="this.blur()">
                    <div id="payAmountHint" class="form-text text-muted small"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Payment Date <span class="text-danger">*</span></label>
                    <input type="date" id="payDate" class="form-control">
                    <div class="form-text text-muted small">Date the customer actually paid. This is independent of the invoice date.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Payment Method <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2">
                        <button type="button" id="btnCash" onclick="setMethod('cash')" class="btn btn-outline-primary flex-fill active">
                            <?= icon('banknote', 14, 'me-1') ?> Cash
                        </button>
                        <button type="button" id="btnBank" onclick="setMethod('bank')" class="btn btn-outline-primary flex-fill">
                            <?= icon('landmark', 14, 'me-1') ?> Bank
                        </button>
                    </div>
                </div>
                <div id="cashFields">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Collector Name <span class="text-danger">*</span></label>
                        <input type="text" id="payCollector" class="form-control" placeholder="Enter collector name">
                    </div>
                </div>
                <div id="bankFields" class="d-none">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Banking Channel <span class="text-danger">*</span></label>
                        <input type="text" id="payBankChannel" class="form-control" placeholder="e.g. HBL, UBL, Meezan, JazzCash, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Transaction ID <span class="text-danger">*</span></label>
                        <input type="text" id="payTxnId" class="form-control" placeholder="Enter transaction/reference ID">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Notes (optional)</label>
                    <input type="text" id="payNotes" class="form-control" placeholder="Optional notes...">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitPayment()" class="btn btn-success">
                    <?= icon('check-circle', 14, 'me-1') ?> Save Payment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Return Modal for Ledger -->
<div class="modal fade" id="ledgerReturnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Create Invoice Return</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="p-3 bg-light rounded-3 mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Invoice:</small>
                        <span id="retModalInvNo" class="fw-bold font-monospace ms-1">SO-0000</span>
                    </div>
                    <div>
                        <small class="text-muted">Customer:</small>
                        <span id="retModalCustName" class="fw-semibold ms-1">Customer</span>
                    </div>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Product Name</th>
                                <th class="text-center">Sold Qty</th>
                                <th class="text-center">Returned</th>
                                <th class="text-center">Returnable</th>
                                <th class="text-center" style="width:110px">Return Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th>Reason</th>
                            </tr>
                        </thead>
                        <tbody id="retModalItemsBody">
                            <tr><td colspan="7" class="text-center text-muted py-4">Loading invoice items...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded-3">
                    <span class="fw-bold">Total Refund Amount:</span>
                    <span id="retModalTotalAmt" class="fs-5 fw-bold text-danger">Rs 0.00</span>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="submitLedgerReturnBtn" onclick="submitLedgerReturn()" class="btn btn-primary fw-semibold">
                    <?= icon('rotate-ccw', 14, 'me-1') ?> Process Return &amp; Restock
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CUSTOMERS = <?= json_encode($customers, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
let selectedCust = null;
let selectedInvoiceId = null;
let invoices = [];
let payments = [];
let currentMethod = 'cash';
let paymentModal = null;
let ledgerReturnModal = null;
let currentReturnItems = [];

function fmt(n) { return 'Rs ' + parseFloat(n).toLocaleString('en-PK', {minimumFractionDigits:2, maximumFractionDigits:2}); }
function esc(s) { if (!s) return ''; return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function round2(n) { return Math.round(n * 100) / 100; }
// Local (not UTC) today's date as YYYY-MM-DD, matching the server clock.
function localToday() {
    var d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

// --- Customer Autocomplete ---
document.getElementById('custSearch').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    var dd = document.getElementById('custDropdown');
    if (q.length < 1) { dd.classList.add('d-none'); return; }
    var matches = CUSTOMERS.filter(c =>
        c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q) || (c.contact||'').toLowerCase().includes(q)
    );
    if (!matches.length) { dd.innerHTML = '<div class="cust-ac-item text-muted">No customers found</div>'; dd.classList.remove('d-none'); return; }
    dd.innerHTML = matches.slice(0, 15).map(c =>
        '<div class="cust-ac-item" onclick="selectCustomer(' + c.id + ')">' +
            '<div class="fw-semibold">' + esc(c.name) + '</div>' +
            '<div class="small">' + esc(c.code) + (c.contact ? ' &middot; ' + esc(c.contact) : '') + '</div>' +
        '</div>'
    ).join('');
    dd.classList.remove('d-none');
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.cust-ac-wrap')) {
        document.getElementById('custDropdown').classList.add('d-none');
    }
});

function selectCustomer(id) {
    var c = CUSTOMERS.find(x => x.id == id);
    if (!c) return;
    selectedCust = c;
    selectedInvoiceId = null;
    document.getElementById('custSearch').value = c.name;
    document.getElementById('custId').value = c.id;
    document.getElementById('custCode').value = c.code;
    document.getElementById('custDropdown').classList.add('d-none');
    document.getElementById('selectedInvBar').classList.add('d-none');
    loadLedger(c.id);
}

// Search directly by Invoice Number
function searchInvoiceDirect() {
    var q = document.getElementById('invSearchInput').value.trim();
    if (!q) { showModal('Notice', 'Please enter an invoice number to search.', 'error'); return; }
    fetch('/api/sale_order_search.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(res => {
            if (!res.length) {
                showModal('Not Found', 'No invoice found matching: ' + q, 'error');
                return;
            }
            const order = res[0];
            if (order.customer_id) {
                selectCustomer(order.customer_id);
                setTimeout(() => selectInvoice(order.id), 400);
            }
        });
}

// --- Load Ledger Data ---
async function loadLedger(custId) {
    document.getElementById('summaryCards').classList.remove('d-none');
    document.getElementById('ledgerContent').classList.remove('d-none');
    document.getElementById('invoiceList').innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
    document.getElementById('paymentHistory').innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

    try {
        const resp = await fetch('/api/ledger_data.php?customer_id=' + custId);
        const data = await resp.json();
        if (!data.success) throw new Error(data.message || 'Failed to load');

        invoices = data.invoices || [];
        payments = data.payments || [];

        renderSummary(data.totals);
        renderInvoices();
        renderPayments();
        updateInvBar();
    } catch (err) {
        document.getElementById('invoiceList').innerHTML = '<div class="text-center py-4 text-danger small">Failed to load data: ' + esc(err.message) + '</div>';
        document.getElementById('paymentHistory').innerHTML = '<div class="text-center py-4 text-danger small">Failed to load data</div>';
    }
}

function renderSummary(t) {
    document.getElementById('totalInvoices').textContent = t.invoice_count;
    document.getElementById('totalSales').textContent = fmt(t.total_sales);
    document.getElementById('totalPaid').textContent = fmt(t.total_paid);
    var outstanding = (typeof t.total_outstanding !== 'undefined')
        ? round2(t.total_outstanding)
        : round2(t.total_sales - t.total_paid);
    var el = document.getElementById('totalOutstanding');
    el.textContent = fmt(outstanding);
    el.className = 'stat-value ' + (outstanding > 0 ? 'text-danger' : 'text-success');
}

function renderInvoices() {
    var el = document.getElementById('invoiceList');
    if (!invoices.length) {
        el.innerHTML = '<div class="text-center py-5 text-muted"><div class="small">No invoices found for this customer</div></div>';
        return;
    }
    el.innerHTML = invoices.map(inv => {
        var outstanding = inv.outstanding;
        var status = outstanding <= 0 ? 'paid' : (inv.paid > 0 ? 'partial' : 'unpaid');
        var statusLabel = status === 'paid' ? 'PAID' : status === 'partial' ? 'PARTIAL' : 'UNPAID';
        var isSelected = selectedInvoiceId === inv.id;
        return '<div class="d-flex align-items-center justify-content-between px-3 py-3 border-bottom inv-row' + (isSelected ? ' selected' : '') + '" onclick="selectInvoice(' + inv.id + ')">' +
            '<div>' +
                '<div class="fw-semibold font-monospace" style="font-size:.9rem;">' + esc(inv.order_no) + '</div>' +
                '<div class="text-muted" style="font-size:.75rem;">Date: ' + esc(inv.order_date) + '</div>' +
            '</div>' +
            '<div class="text-end">' +
                '<div class="fw-semibold" style="font-size:.9rem;">Total: ' + fmt(inv.total) + '</div>' +
                '<small class="text-muted d-block" style="font-size:.75rem;">Paid: ' + fmt(inv.paid) + ' &middot; Rem: ' + fmt(inv.outstanding) + '</small>' +
                '<div class="mt-1"><span class="status-badge status-' + status + '">' + statusLabel + '</span></div>' +
            '</div>' +
        '</div>';
    }).join('');
}

function renderPayments() {
    var el = document.getElementById('paymentHistory');
    var badge = document.getElementById('payHistoryFilterBadge');
    var filtered = payments;
    if (selectedInvoiceId) {
        filtered = payments.filter(p => p.sale_order_id == selectedInvoiceId);
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }

    if (!filtered.length) {
        el.innerHTML = '<div class="text-center py-5 text-muted"><div class="small">No payments recorded for this ' + (selectedInvoiceId ? 'invoice' : 'customer') + '</div></div>';
        return;
    }
    el.innerHTML = '<div class="ledger-list">' + filtered.map(p => {
        var methodClass = p.payment_method === 'cash' ? 'method-cash' : 'method-bank';
        var methodLabel = p.payment_method === 'cash' ? 'Cash' : 'Bank';
        var detail = '';
        if (p.payment_method === 'cash' && p.collector_name) {
            detail = '<div class="text-muted" style="font-size:.7rem;">Collector: ' + esc(p.collector_name) + '</div>';
        } else if (p.payment_method === 'bank') {
            detail = '<div class="text-muted" style="font-size:.7rem;">' + esc(p.bank_channel) + ' &middot; Txn: ' + esc(p.transaction_id) + '</div>';
        }
        return '<div class="ledger-entry">' +
            '<div class="d-flex justify-content-between align-items-start">' +
                '<div>' +
                    '<div class="d-flex align-items-center gap-2">' +
                        '<span class="payment-method-badge ' + methodClass + '">' + methodLabel + '</span>' +
                        '<span class="fw-semibold" style="font-size:.85rem;">' + fmt(p.amount) + '</span>' +
                    '</div>' +
                    '<div class="text-muted mt-1" style="font-size:.75rem;">' + esc(p.receipt_no) + ' &middot; Paid: ' + esc(p.payment_date_display || p.payment_date || '-') + '</div>' +
                    '<div class="text-muted" style="font-size:.7rem;">Recorded: ' + esc(p.created_at) + '</div>' +
                    detail +
                '</div>' +
                '<div class="text-end">' +
                    '<a href="/controllers/payment_receipt.php?receipt_no=' + encodeURIComponent(p.receipt_no) + '" target="_blank" class="btn btn-sm btn-outline-success" title="View Receipt PDF" style="font-size:.75rem;">' +
                        'Receipt' +
                    '</a>' +
                '</div>' +
            '</div>' +
        '</div>';
    }).join('') + '</div>';
}

// --- Invoice Selection ---
function updateInvBar() {
    const bar = document.getElementById('selectedInvBar');
    var inv = invoices.find(i => i.id == selectedInvoiceId);
    if (!inv) {
        selectedInvoiceId = null;
        bar.classList.add('d-none');
        return;
    }

    bar.classList.remove('d-none');
    document.getElementById('barInvNo').textContent = inv.order_no;
    document.getElementById('barInvTotal').textContent = fmt(inv.total);
    document.getElementById('barInvPaid').textContent = fmt(inv.paid);
    document.getElementById('barInvRem').textContent = fmt(inv.outstanding);

    var status = inv.outstanding <= 0 ? 'paid' : (inv.paid > 0 ? 'partial' : 'unpaid');
    var statusLabel = status === 'paid' ? 'PAID' : status === 'partial' ? 'PARTIAL' : 'UNPAID';
    var stEl = document.getElementById('barInvStatus');
    stEl.className = 'status-badge status-' + status;
    stEl.textContent = statusLabel;

    document.getElementById('barBtnView').href = '/views/sale_order_view.php?id=' + inv.id;
    document.getElementById('barBtnEdit').href = '/views/sale_order_new.php?edit=' + inv.id;
    document.getElementById('barBtnPdf').href = '/controllers/sale_order_pdf.php?id=' + inv.id;
    document.getElementById('barBtnPay').disabled = inv.outstanding <= 0;
}

function selectInvoice(id) {
    var inv = invoices.find(i => i.id == id);
    if (!inv) return;
    selectedInvoiceId = id;
    renderInvoices();
    renderPayments();
    updateInvBar();
}

// --- Payment Modal ---
function setMethod(m) {
    currentMethod = m;
    document.getElementById('btnCash').classList.toggle('active', m === 'cash');
    document.getElementById('btnBank').classList.toggle('active', m === 'bank');
    document.getElementById('cashFields').classList.toggle('d-none', m !== 'cash');
    document.getElementById('bankFields').classList.toggle('d-none', m !== 'bank');
}

function openPaymentModal(invoiceId) {
    if (!selectedCust) { showModal('Notice', 'Please select a customer first.', 'error'); return; }

    document.getElementById('payCollector').value = '';
    document.getElementById('payTxnId').value = '';
    document.getElementById('payBankChannel').value = '';
    document.getElementById('payNotes').value = '';
    // Payment Date defaults to today, but is always user-editable so a
    // historical payment can be back-dated to the day it was actually made.
    document.getElementById('payDate').value = localToday();
    setMethod('cash');

    document.getElementById('payCustName').value = selectedCust.name;

    var invoiceInfo = document.getElementById('payInvoiceInfo');
    var generalInfo = document.getElementById('payGeneralInfo');
    var amountInput = document.getElementById('payAmount');
    var hint = document.getElementById('payAmountHint');

    if (invoiceId) {
        var inv = invoices.find(i => i.id == invoiceId);
        if (!inv || inv.outstanding <= 0) return;

        selectedInvoiceId = invoiceId;
        invoiceInfo.classList.remove('d-none');
        generalInfo.classList.add('d-none');
        document.getElementById('payInvoiceNo').textContent = inv.order_no;
        document.getElementById('payInvoiceOutstanding').textContent = fmt(inv.outstanding);
        document.getElementById('paySaleOrderId').value = inv.id;
        amountInput.value = round2(inv.outstanding);
        amountInput.max = inv.outstanding;
        hint.textContent = 'Full invoice amount pre-filled. You can edit for partial payment.';
    } else {
        selectedInvoiceId = null;
        invoiceInfo.classList.add('d-none');
        generalInfo.classList.remove('d-none');
        document.getElementById('paySaleOrderId').value = '';

        var totalOutstanding = round2(invoices.reduce((s, i) => s + i.outstanding, 0));
        document.getElementById('payOutstanding').textContent = fmt(totalOutstanding);
        document.getElementById('payOutstanding').className = 'fs-5 fw-bold ' + (totalOutstanding > 0 ? 'text-danger' : 'text-success');
        amountInput.value = '';
        amountInput.max = totalOutstanding;
        hint.textContent = totalOutstanding > 0 ? 'Max: ' + fmt(totalOutstanding) : 'No outstanding balance.';
    }

    renderInvoices();

    if (!paymentModal) paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
    paymentModal.show();
}

async function submitPayment() {
    if (!selectedCust) return;

    var amount = round2(parseFloat(document.getElementById('payAmount').value) || 0);
    var saleOrderId = document.getElementById('paySaleOrderId').value || null;

    if (amount <= 0) { showModal('Validation Error', 'Please enter a valid payment amount.', 'error'); return; }

    var maxAllowed = 0;
    if (saleOrderId) {
        var inv = invoices.find(i => i.id == saleOrderId);
        if (inv) maxAllowed = inv.outstanding;
    } else {
        maxAllowed = round2(invoices.reduce((s, i) => s + i.outstanding, 0));
    }

    if (amount > maxAllowed + 0.01) {
        showModal('Validation Error', 'Payment amount (' + fmt(amount) + ') exceeds outstanding balance (' + fmt(maxAllowed) + ').', 'error');
        return;
    }

    var data = {
        customer_id: selectedCust.id,
        sale_order_id: saleOrderId ? parseInt(saleOrderId) : null,
        amount: amount,
        payment_method: currentMethod,
    };

    var payDate = document.getElementById('payDate').value;
    if (!payDate) { showModal('Validation Error', 'Payment date is required.', 'error'); return; }
    data.payment_date = payDate;

    if (currentMethod === 'cash') {
        data.collector_name = document.getElementById('payCollector').value.trim();
        if (!data.collector_name) { showModal('Validation Error', 'Collector name is required for cash payments.', 'error'); return; }
    } else {
        data.bank_channel = document.getElementById('payBankChannel').value.trim();
        data.transaction_id = document.getElementById('payTxnId').value.trim();
        if (!data.bank_channel) { showModal('Validation Error', 'Banking channel is required for bank payments.', 'error'); return; }
        if (!data.transaction_id) { showModal('Validation Error', 'Transaction ID is required for bank payments.', 'error'); return; }
    }

    data.notes = document.getElementById('payNotes').value.trim();

    try {
        const resp = await fetch('/controllers/payment_create.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        const result = await resp.json();

        if (result.success) {
            if (paymentModal) paymentModal.hide();
            showModal('Payment Recorded', 'Receipt: ' + result.receipt_no + '\nPayment Date: ' + (result.payment_date_display || result.payment_date) + '\nAmount: ' + fmt(result.amount) + '\nRemaining: ' + fmt(result.remaining_balance), 'success');
            window.open('/controllers/payment_receipt.php?receipt_no=' + encodeURIComponent(result.receipt_no), '_blank');
            loadLedger(selectedCust.id);
        } else {
            showModal('Error', result.message, 'error');
        }
    } catch (err) {
        showModal('Error', 'Failed to process payment: ' + err.message, 'error');
    }
}

// --- Invoice Returns Modal ---
function openInvoiceReturnModal(invoiceId) {
    var inv = invoices.find(i => i.id == invoiceId);
    if (!inv) return;

    document.getElementById('retModalInvNo').textContent = inv.order_no;
    document.getElementById('retModalCustName').textContent = selectedCust ? selectedCust.name : 'Customer';

    const tbody = document.getElementById('retModalItemsBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading items...</td></tr>';

    fetch('/api/sale_order_items.php?order_id=' + invoiceId)
        .then(r => r.json())
        .then(items => {
            currentReturnItems = items;
            renderLedgerReturnModalItems();
        });

    if (!ledgerReturnModal) ledgerReturnModal = new bootstrap.Modal(document.getElementById('ledgerReturnModal'));
    ledgerReturnModal.show();
}

function renderLedgerReturnModalItems() {
    const tbody = document.getElementById('retModalItemsBody');
    tbody.innerHTML = '';
    if (!currentReturnItems.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No items found on this invoice.</td></tr>';
        document.getElementById('retModalTotalAmt').textContent = 'Rs 0.00';
        return;
    }

    let totalRefund = 0;
    currentReturnItems.forEach((it, idx) => {
        const returnQty = it.retQty || 0;
        const lineRefund = round2(returnQty * it.price);
        totalRefund += lineRefund;

        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><div class="fw-semibold small">' + esc(it.product_name) + '</div><small class="text-muted font-monospace">' + esc(it.sku || '') + '</small></td>' +
            '<td class="text-center fw-bold">' + it.quantity + '</td>' +
            '<td class="text-center text-danger fw-semibold">' + it.returned_quantity + '</td>' +
            '<td class="text-center text-success fw-bold">' + it.returnable_quantity + '</td>' +
            '<td><input type="number" min="0" max="' + it.returnable_quantity + '" value="' + returnQty + '" onchange="updateLedgerRetQty(' + idx + ', this.value)" class="form-control form-control-sm text-center" onwheel="this.blur()"></td>' +
            '<td class="text-end">Rs ' + round2(it.price).toFixed(2) + '</td>' +
            '<td><input type="text" placeholder="Reason..." value="' + esc(it.reason || '') + '" onchange="updateLedgerRetReason(' + idx + ', this.value)" class="form-control form-control-sm"></td>';
        tbody.appendChild(tr);
    });

    document.getElementById('retModalTotalAmt').textContent = fmt(totalRefund);
}

function updateLedgerRetQty(idx, val) {
    const qty = parseInt(val) || 0;
    const maxQty = currentReturnItems[idx].returnable_quantity;
    if (qty > maxQty) {
        showModal('Return Limit Exceeded', 'Cannot return more than maximum returnable quantity of ' + maxQty + '.', 'error');
        currentReturnItems[idx].retQty = maxQty;
    } else {
        currentReturnItems[idx].retQty = Math.max(0, qty);
    }
    renderLedgerReturnModalItems();
}

function updateLedgerRetReason(idx, val) {
    currentReturnItems[idx].reason = val;
}

function submitLedgerReturn() {
    const itemsToReturn = currentReturnItems.filter(it => (it.retQty || 0) > 0).map(it => ({
        product_id: it.product_id,
        qty: it.retQty,
        refund_price: it.price,
        reason: it.reason || 'Customer Return'
    }));

    if (!itemsToReturn.length) {
        showModal('Notice', 'Please specify a return quantity greater than 0 for at least one item.', 'error');
        return;
    }

    const btn = document.getElementById('submitLedgerReturnBtn');
    btn.disabled = true;
    btn.textContent = 'Processing Return...';

    fetch('/controllers/return_create.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            sale_order_id: selectedInvoiceId,
            items: itemsToReturn
        })
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        btn.textContent = 'Process Return & Restock';
        if (d.success) {
            if (ledgerReturnModal) ledgerReturnModal.hide();
            showModal('Success', d.message, 'success');
            loadLedger(selectedCust.id);
        } else {
            showModal('Error', d.message, 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Process Return & Restock';
        showModal('Error', 'Failed to process return.', 'error');
    });
}
</script>
<?php
$content = ob_get_clean();
render_page('Customer Ledger', $content, modal_markup_html());
