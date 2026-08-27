<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/modal.php';
require_login();

$suppliers = $pdo->query('SELECT * FROM suppliers ORDER BY id ASC')->fetchAll();

$totalSupp = count($suppliers);

// Generate next supplier code
$lastStmt = $pdo->query("SELECT code FROM suppliers WHERE code LIKE 'SP-MJ-%' ORDER BY id DESC LIMIT 1");
$lastCode = $lastStmt->fetchColumn();
if ($lastCode && preg_match('/SP-MJ-(\d+)$/', $lastCode, $m)) {
    $nextCode = 'SP-MJ-' . str_pad((int)$m[1] + 1, 2, '0', STR_PAD_LEFT);
} else {
    $nextCode = 'SP-MJ-01';
}

ob_start();
?>
<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;">
                    <?= icon('truck', 20) ?>
                </div>
                <div>
                    <div class="stat-label">Total Suppliers</div>
                    <div class="stat-value"><?= $totalSupp ?></div>
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
                <label class="form-label small fw-medium">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><?= icon('search', 14) ?></span>
                    <input type="text" id="supplierSearch" oninput="applyFilters()" placeholder="Search code, name, company, contact..." class="form-control border-start-0">
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <button type="button" onclick="openForm()" class="btn btn-primary">
                    <?= icon('plus', 14, 'me-1') ?>
                    Add Supplier
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
                    <th>Supplier Name</th>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Phone</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="supplierTableBody"></tbody>
        </table>
    </div>
    <div id="emptyState" class="text-center py-5 d-none">
        <?= icon('info-circle', 40, 'text-muted mb-2') ?>
        <p class="text-muted small mb-1">No suppliers found</p>
        <p class="text-muted" style="font-size:.75rem">Try a different search.</p>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 id="formTitle" class="modal-title fw-bold">Add Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="supplierForm">
                    <input type="hidden" name="id" id="s_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Supplier Code</label>
                            <input type="text" name="code" id="s_code" readonly class="form-control bg-light text-muted font-monospace">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Supplier Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="s_name" autocomplete="off" required class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Company Name</label>
                            <input type="text" name="company_name" id="s_company_name" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Contact Number</label>
                            <input type="text" name="contact" id="s_contact" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Phone</label>
                            <input type="text" name="phone" id="s_phone" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Email</label>
                            <input type="email" name="email" id="s_email" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">NTN No</label>
                            <input type="text" name="ntn" id="s_ntn" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">STN No</label>
                            <input type="text" name="stn" id="s_stn" autocomplete="off" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-medium">Address</label>
                            <textarea name="address" id="s_address" rows="2" class="form-control" placeholder="Optional address..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitForm()" class="btn btn-primary">Save Supplier</button>
            </div>
        </div>
    </div>
</div>

<script>
const SUPPLIERS = <?= json_encode($suppliers, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
const NEXT_CODE = <?= json_encode($nextCode) ?>;

function esc(s) { if (!s) return ''; return s.replace(/&/g, '&').replace(/</g, '<').replace(/>/g, '>').replace(/"/g, '"').replace(/'/g, '&#039;'); }
function escQ(s) { if (!s) return ''; return s.replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }

var formModal = null;

function openForm(id) {
    document.getElementById('supplierForm').reset();
    document.getElementById('s_id').value = '';
    document.getElementById('formTitle').textContent = 'Add Supplier';
    document.getElementById('s_code').value = NEXT_CODE;
    document.getElementById('s_code').readOnly = true;
    if (id) {
        const s = SUPPLIERS.find(x => x.id == id);
        if (s) {
            document.getElementById('formTitle').textContent = 'Edit Supplier';
            document.getElementById('s_id').value = s.id;
            document.getElementById('s_code').value = s.code;
            document.getElementById('s_name').value = s.name;
            document.getElementById('s_company_name').value = s.company_name;
            document.getElementById('s_contact').value = s.contact;
            document.getElementById('s_phone').value = s.phone;
            document.getElementById('s_email').value = s.email;
            document.getElementById('s_address').value = s.address;
            document.getElementById('s_ntn').value = s.ntn;
            document.getElementById('s_stn').value = s.stn;
        }
    }
    if (!formModal) formModal = new bootstrap.Modal(document.getElementById('formModal'));
    formModal.show();
}

function renderTable(list) {
    const tbody = document.getElementById('supplierTableBody');
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
    list.forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML =
            '<td><span class="font-monospace fw-semibold">' + esc(s.code) + '</span></td>' +
            '<td class="fw-medium">' + esc(s.name) + '</td>' +
            '<td class="text-muted">' + (esc(s.company_name) || '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-muted">' + (esc(s.contact) || '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-muted">' + (esc(s.phone) || '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-end">' +
                '<button onclick="openForm(' + s.id + ')" class="btn btn-sm btn-outline-primary me-1">Edit</button>' +
                '<button onclick="doDelete(' + s.id + ', \'' + escQ(s.name) + '\')" class="btn btn-sm btn-outline-danger">Delete</button>' +
            '</td>';
        tbody.appendChild(tr);
    });
}

function applyFilters() {
    const q = document.getElementById('supplierSearch').value.toLowerCase().trim();
    renderTable(SUPPLIERS.filter(s => {
        if (q && ![s.code, s.name, s.company_name, s.contact, s.phone, s.email].join(' ').toLowerCase().includes(q)) return false;
        return true;
    }));
}

function submitForm() {
    const f = document.getElementById('supplierForm');
    const data = {
        action: 'save',
        id: f.id.value,
        code: f.code.value,
        name: f.name.value,
        company_name: f.company_name.value,
        contact: f.contact.value,
        phone: f.phone.value,
        email: f.email.value,
        address: f.address.value,
        ntn: f.ntn.value,
        stn: f.stn.value
    };
    if (!data.name.trim()) { showModal('Validation Error', 'Supplier Name is required.', 'error'); return; }
    fetch('/controllers/supplier_save.php', {
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
    confirmModal('Delete Supplier', 'Delete supplier "' + name + '"? This cannot be undone.', 'Delete').then(ok => {
        if (!ok) return;
        fetch('/controllers/supplier_save.php', {
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

renderTable(SUPPLIERS);
</script>
<?php
$content = ob_get_clean();
render_page('Suppliers', $content, modal_markup_html());