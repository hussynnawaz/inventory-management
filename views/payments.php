<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/icons.php';
require_login();

ob_start();
?>
<style>
    .status-badge { padding: 3px 10px; border-radius: 20px; font-size: .7rem; font-weight: 600; text-transform: uppercase; }
    .status-paid { background: #d1fae5; color: #065f46; }
    .status-partial { background: #fef3c7; color: #92400e; }
    .status-unpaid { background: #fee2e2; color: #991b1b; }
    .filter-pill.active { background: #2563eb; color: #fff; border-color: #2563eb; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0">Payments</h4>
        <small class="text-muted">Invoice payment status, balances, and receipts</small>
    </div>
    <a href="/views/ledger.php" class="btn btn-outline-primary btn-sm fw-semibold">Record Payment (Ledger)</a>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="stat-label">Total Sales</div>
                <div class="stat-value" id="sumSales">—</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="stat-label">Total Payments</div>
                <div class="stat-value" id="sumPayments">—</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="stat-label">Pending Payments</div>
                <div class="stat-value" id="sumPending">—</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body p-4">
                <div class="stat-label">Total Invoices</div>
                <div class="stat-value" id="sumInvoices">—</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary filter-pill active" data-status="all">All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-pill" data-status="paid">Paid</button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-pill" data-status="partial">Partially Paid</button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-pill" data-status="unpaid">Unpaid</button>
        </div>
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-medium">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><?= icon('search', 14) ?></span>
                    <input type="text" id="paySearch" class="form-control" placeholder="Customer ID, name, contact, invoice #...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium">From Date</label>
                <input type="date" id="fromDate" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium">To Date</label>
                <input type="date" id="toDate" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <button type="button" id="btnApply" class="btn btn-primary btn-sm w-100">Apply</button>
            </div>
        </div>
    </div>
</div>

<div class="card card-table">
    <div id="loadingState" class="text-center py-5 d-none">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <div class="small text-muted mt-2">Loading invoices...</div>
    </div>
    <div id="errorState" class="text-center py-5 d-none">
        <p class="text-danger small mb-2" id="errorMsg">Failed to load data.</p>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadPayments()">Retry</button>
    </div>
    <div id="tableWrap" class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Invoice Date</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-end">Sales Tax</th>
                    <th class="text-end">Advance Tax</th>
                    <th class="text-end">Grand Total</th>
                    <th class="text-end">Paid</th>
                    <th class="text-end">Remaining</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="payTableBody"></tbody>
        </table>
    </div>
    <div id="emptyState" class="text-center py-5 d-none">
        <p class="text-muted small mb-0">No invoices match your filters.</p>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
        <small class="text-muted" id="pageInfo">—</small>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" id="btnPrev" disabled>Previous</button>
            <button type="button" class="btn btn-outline-secondary" id="btnNext" disabled>Next</button>
        </div>
    </div>
</div>

<script>
let currentStatus = 'all';
let currentPage = 1;
let totalPages = 1;
let searchTimer = null;

function fmt(n) {
    return 'Rs ' + Number(n).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
function statusBadge(st) {
    const labels = { paid: 'Paid', partial: 'Partial', unpaid: 'Unpaid' };
    return '<span class="status-badge status-' + (st === 'partial' ? 'partial' : st) + '">' + (labels[st] || st) + '</span>';
}

function queryParams() {
    const p = new URLSearchParams();
    p.set('status', currentStatus);
    p.set('page', String(currentPage));
    p.set('per_page', '25');
    const q = document.getElementById('paySearch').value.trim();
    if (q) p.set('q', q);
    const fd = document.getElementById('fromDate').value;
    const td = document.getElementById('toDate').value;
    if (fd) p.set('from_date', fd);
    if (td) p.set('to_date', td);
    return p.toString();
}

async function loadPayments() {
    document.getElementById('loadingState').classList.remove('d-none');
    document.getElementById('errorState').classList.add('d-none');
    document.getElementById('emptyState').classList.add('d-none');
    document.getElementById('tableWrap').classList.add('d-none');

    try {
        const resp = await fetch('/api/payments_list.php?' + queryParams());
        const data = await resp.json();
        if (!data.success) throw new Error(data.message || 'Request failed');

        const s = data.summary;
        document.getElementById('sumSales').textContent = fmt(s.total_sales);
        document.getElementById('sumPayments').textContent = fmt(s.total_payments);
        document.getElementById('sumPending').textContent = fmt(s.pending_payments);
        document.getElementById('sumInvoices').textContent = String(s.total_invoices);

        totalPages = data.pagination.total_pages || 1;
        document.getElementById('pageInfo').textContent =
            'Page ' + data.pagination.page + ' of ' + totalPages + ' (' + data.pagination.total_rows + ' invoices)';
        document.getElementById('btnPrev').disabled = data.pagination.page <= 1;
        document.getElementById('btnNext').disabled = data.pagination.page >= totalPages;

        const tbody = document.getElementById('payTableBody');
        tbody.innerHTML = '';
        if (!data.invoices.length) {
            document.getElementById('emptyState').classList.remove('d-none');
        } else {
            document.getElementById('tableWrap').classList.remove('d-none');
            data.invoices.forEach(inv => {
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td><span class="font-monospace fw-semibold">' + esc(inv.order_no) + '</span></td>' +
                    '<td><div class="fw-medium">' + esc(inv.customer_name || '—') + '</div><small class="text-muted font-monospace">' + esc(inv.customer_code || '') + '</small></td>' +
                    '<td class="text-muted small">' + esc(inv.contact || '—') + '</td>' +
                    '<td class="text-muted small">' + esc(inv.order_date) + '</td>' +
                    '<td class="text-end small">' + fmt(inv.subtotal) + '</td>' +
                    '<td class="text-end small">' + fmt(inv.sales_tax_amt) + '</td>' +
                    '<td class="text-end small">' + fmt(inv.advanced_tax_amt) + '</td>' +
                    '<td class="text-end fw-semibold">' + fmt(inv.grand_total) + '</td>' +
                    '<td class="text-end">' + fmt(inv.amount_paid) + '</td>' +
                    '<td class="text-end">' + fmt(inv.amount_remaining) + '</td>' +
                    '<td>' + statusBadge(inv.payment_status) + '</td>' +
                    '<td class="text-end">' +
                        '<div class="btn-group btn-group-sm">' +
                        '<a href="/views/sale_order_view.php?id=' + inv.id + '" class="btn btn-outline-primary" title="View">View</a>' +
                        '<a href="/controllers/sale_order_pdf.php?id=' + inv.id + '" target="_blank" class="btn btn-outline-danger" title="PDF">PDF</a>' +
                        '</div></td>';
                tbody.appendChild(tr);
            });
        }
    } catch (err) {
        document.getElementById('errorMsg').textContent = err.message || 'Failed to load data.';
        document.getElementById('errorState').classList.remove('d-none');
    } finally {
        document.getElementById('loadingState').classList.add('d-none');
    }
}

document.querySelectorAll('.filter-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentStatus = btn.dataset.status;
        currentPage = 1;
        loadPayments();
    });
});

document.getElementById('btnApply').addEventListener('click', () => { currentPage = 1; loadPayments(); });
document.getElementById('paySearch').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { currentPage = 1; loadPayments(); }, 350);
});
document.getElementById('btnPrev').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadPayments(); } });
document.getElementById('btnNext').addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; loadPayments(); } });

loadPayments();
</script>
<?php
render_page('Payments', ob_get_clean());
