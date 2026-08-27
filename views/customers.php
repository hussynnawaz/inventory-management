<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/modal.php';
require_login();

$customers = $pdo->query('SELECT * FROM customers ORDER BY id ASC')->fetchAll();

$totalCust = count($customers);
$routesList = array_unique(array_filter(array_column($customers, 'destination')));
$totalRoutes = count($routesList);

// Generate next customer code
$lastStmt = $pdo->query("SELECT code FROM customers WHERE code LIKE 'CR-MJ-%' ORDER BY id DESC LIMIT 1");
$lastCode = $lastStmt->fetchColumn();
if ($lastCode && preg_match('/CR-MJ-(\d+)$/', $lastCode, $m)) {
    $nextCode = 'CR-MJ-' . str_pad((int)$m[1] + 1, 2, '0', STR_PAD_LEFT);
} else {
    $nextCode = 'CR-MJ-01';
}

ob_start();
?>
<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <?= icon('people', 20) ?>
                </div>
                <div>
                    <div class="stat-label">Total Customers</div>
                    <div class="stat-value"><?= $totalCust ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;">
                    <?= icon('pin-map', 20) ?>
                </div>
                <div>
                    <div class="stat-label">Destinations</div>
                    <div class="stat-value"><?= $totalRoutes ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter + Add -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label small fw-medium">Search</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><?= icon('search', 14) ?></span>
                            <input type="text" id="custSearch" oninput="applyFilters()" placeholder="Search code, name, contact or destination..." class="form-control border-start-0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-medium">Route</label>
                        <select id="routeFilter" onchange="applyFilters()" class="form-select">
                            <option value="">All Destinations</option>
                            <?php foreach ($routesList as $route): ?>
                                <option value="<?= e($route) ?>"><?= e($route) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <button type="button" onclick="openForm()" class="btn btn-primary">
                    <?= icon('plus', 14, 'me-1') ?>
                    Add Customer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card card-table">
    <div id="tableContainer" class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer Name</th>
                    <th>Contact</th>
                    <th>Destination</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="customerTableBody"></tbody>
        </table>
    </div>
    <div id="emptyState" class="text-center py-5 d-none">
        <?= icon('info-circle', 40, 'text-muted mb-2') ?>
        <p class="text-muted small mb-1">No customers found</p>
        <p class="text-muted" style="font-size:.75rem">Try a different search or filter.</p>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 id="formTitle" class="modal-title fw-bold">Add Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="customerForm">
                    <input type="hidden" name="id" id="f_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Customer Code</label>
                            <input type="text" name="code" id="f_code" readonly class="form-control bg-light text-muted font-monospace">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="f_name" autocomplete="off" required class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Contact Number</label>
                            <input type="text" name="contact" id="f_contact" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Destination</label>
                            <input type="text" name="destination" id="f_delivery_route" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">NTN No</label>
                            <input type="text" name="ntn_no" id="f_ntn_no" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Sales Tax No</label>
                            <input type="text" name="sales_tax_no" id="f_sales_tax_no" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">CNIC</label>
                            <input type="text" name="cnic" id="f_cnic" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-medium">Address</label>
                            <input type="text" name="address" id="f_address" autocomplete="off" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitForm()" class="btn btn-primary">Save Customer</button>
            </div>
        </div>
    </div>
</div>

<script>
const CUSTOMERS = <?= json_encode($customers, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
const NEXT_CODE = <?= json_encode($nextCode) ?>;

function esc(s) { if (!s) return ''; return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
function escQ(s) { if (!s) return ''; return s.replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }

var formModal = null;

function openForm(id) {
    document.getElementById('customerForm').reset();
    document.getElementById('f_id').value = '';
    document.getElementById('formTitle').textContent = 'Add Customer';
    document.getElementById('f_code').value = NEXT_CODE;
    document.getElementById('f_code').readOnly = true;
    if (id) {
        const c = CUSTOMERS.find(x => x.id == id);
        if (c) {
            document.getElementById('formTitle').textContent = 'Edit Customer';
            document.getElementById('f_id').value = c.id;
            document.getElementById('f_code').value = c.code;
            document.getElementById('f_name').value = c.name;
            document.getElementById('f_contact').value = c.contact;
            document.getElementById('f_delivery_route').value = c.destination;
            document.getElementById('f_ntn_no').value = c.ntn_no;
            document.getElementById('f_sales_tax_no').value = c.sales_tax_no;
            document.getElementById('f_cnic').value = c.cnic;
            document.getElementById('f_address').value = c.address;
        }
    }
    if (!formModal) formModal = new bootstrap.Modal(document.getElementById('formModal'));
    formModal.show();
}

function renderTable(list) {
    const tbody = document.getElementById('customerTableBody');
    const tableContainer = document.getElementById('tableContainer');
    const emptyState = document.getElementById('emptyState');
    tbody.innerHTML = '';
    if (!list.length) {
        tableContainer.classList.add('d-none');
        emptyState.classList.remove('d-none');
        return;
    }
    tableContainer.classList.remove('d-none');
    emptyState.classList.add('d-none');
    list.forEach(c => {
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><span class="font-monospace fw-semibold">' + esc(c.code) + '</span></td>' +
            '<td class="fw-medium">' + esc(c.name) + '</td>' +
            '<td class="text-muted">' + (esc(c.contact) || '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-muted">' + (esc(c.destination) || '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-end">' +
                '<button onclick="openForm(' + c.id + ')" class="btn btn-sm btn-outline-primary me-1">Edit</button>' +
                '<button onclick="doDelete(' + c.id + ', \'' + escQ(c.name) + '\')" class="btn btn-sm btn-outline-danger">Delete</button>' +
            '</td>';
        tbody.appendChild(tr);
    });
}

function applyFilters() {
    const q = document.getElementById('custSearch').value.toLowerCase().trim();
    const route = document.getElementById('routeFilter').value;
    renderTable(CUSTOMERS.filter(c => {
        if (route && c.destination !== route) return false;
        if (q && ![c.code, c.name, c.contact, c.destination].join(' ').toLowerCase().includes(q)) return false;
        return true;
    }));
}

function submitForm() {
    const f = document.getElementById('customerForm');
    const data = {
        action: 'save', id: f.id.value, code: f.code.value, name: f.name.value,
        contact: f.contact.value, destination: f.destination.value,
        ntn_no: f.ntn_no.value, sales_tax_no: f.sales_tax_no.value, cnic: f.cnic.value, address: f.address.value
    };
    if (!data.name.trim()) { showModal('Validation Error', 'Customer Name is required.', 'error'); return; }
    fetch('/controllers/customer_save.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showModal('Success', d.message, 'success'); if (formModal) formModal.hide(); setTimeout(() => location.reload(), 800); }
        else { showModal('Error', d.message, 'error'); }
    })
    .catch(() => showModal('Error', 'Submission failed.', 'error'));
}

function doDelete(id, name) {
    confirmModal('Delete Customer', 'Delete customer "' + name + '"? This cannot be undone.', 'Delete').then(ok => {
        if (!ok) return;
        fetch('/controllers/customer_save.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'delete', id: id })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) { showModal('Success', d.message, 'success'); setTimeout(() => location.reload(), 800); }
            else { showModal('Error', d.message, 'error'); }
        })
        .catch(() => showModal('Error', 'Delete failed.', 'error'));
    });
}

renderTable(CUSTOMERS);
</script>
<?php
$content = ob_get_clean();
render_page('Customers', $content, modal_markup_html());
