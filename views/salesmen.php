<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/modal.php';
require_login();

$salesmen = $pdo->query('SELECT * FROM salesmen ORDER BY id ASC')->fetchAll();
$totalSalesmen = count($salesmen);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted small mb-0">Manage your salesmen. Total: <strong><?= $totalSalesmen ?></strong></p>
    <button type="button" onclick="openForm()" class="btn btn-primary btn-sm">
        <?= icon('plus', 14, 'me-1') ?>
        Add Salesman
    </button>
</div>

<div class="card card-table">
    <div class="table-responsive">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>CNIC</th>
                    <th>Address</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="salesmenBody">
                <?php if (empty($salesmen)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No salesmen found.</td></tr>
                <?php else: foreach ($salesmen as $s): ?>
                    <tr>
                        <td><span class="font-monospace fw-semibold"><?= e($s['salesman_id']) ?></span></td>
                        <td class="fw-medium"><?= e($s['name']) ?></td>
                        <td class="text-muted"><?= e($s['phone'] ?: '-') ?></td>
                        <td class="text-muted"><?= e($s['cnic'] ?: '-') ?></td>
                        <td class="text-muted"><?= e($s['address'] ?: '-') ?></td>
                        <td class="text-end">
                            <button onclick='openForm(<?= json_encode($s) ?>)' class="btn btn-sm btn-outline-primary me-1">Edit</button>
                            <button onclick="doDelete(<?= $s['id'] ?>, '<?= e($s['name']) ?>')" class="btn btn-sm btn-outline-danger">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 id="formTitle" class="modal-title fw-bold">Add Salesman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="salesmanForm">
                    <input type="hidden" name="id" id="f_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Salesman ID</label>
                            <input type="text" id="f_salesman_id" class="form-control bg-light" readonly placeholder="Auto-generated">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="f_name" required class="form-control" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Phone Number</label>
                            <input type="text" name="phone" id="f_phone" class="form-control" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">CNIC</label>
                            <input type="text" name="cnic" id="f_cnic" class="form-control" autocomplete="off">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-medium">Address</label>
                            <input type="text" name="address" id="f_address" class="form-control" autocomplete="off">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitForm()" class="btn btn-primary">Save Salesman</button>
            </div>
        </div>
    </div>
</div>

<script>
var formModal = null;

function openForm(data) {
    document.getElementById('salesmanForm').reset();
    document.getElementById('f_id').value = '';
    document.getElementById('formTitle').textContent = 'Add Salesman';
    document.getElementById('f_salesman_id').value = '';
    if (data) {
        document.getElementById('formTitle').textContent = 'Edit Salesman';
        document.getElementById('f_id').value = data.id;
        document.getElementById('f_salesman_id').value = data.salesman_id || '';
        document.getElementById('f_name').value = data.name;
        document.getElementById('f_phone').value = data.phone || '';
        document.getElementById('f_cnic').value = data.cnic || '';
        document.getElementById('f_address').value = data.address || '';
    }
    if (!formModal) formModal = new bootstrap.Modal(document.getElementById('formModal'));
    formModal.show();
}

function submitForm() {
    var f = document.getElementById('salesmanForm');
    var data = {
        action: 'save', id: f.id.value,
        name: f.name.value, phone: f.phone.value, cnic: f.cnic.value, address: f.address.value
    };
    if (!data.name.trim()) {
        showModal('Error', 'Name is required.', 'error');
        return;
    }
    fetch('/controllers/salesman_save.php', {
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
    confirmModal('Delete Salesman', 'Delete salesman "' + name + '"? This cannot be undone.', 'Delete').then(ok => {
        if (!ok) return;
        fetch('/controllers/salesman_save.php', {
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
</script>
<?php
$content = ob_get_clean();
render_page('Salesmen', $content, modal_markup_html());
