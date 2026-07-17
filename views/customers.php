<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/modal.php';
require_login();

$customers = $pdo->query('SELECT * FROM customers ORDER BY code ASC')->fetchAll();

// Auto-generate next customer code, e.g. CUST-0001
$count = (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$nextCode = 'CUST-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

ob_start();
?>
<div class="flex justify-between items-center mb-4">
    <p class="text-sm text-slate-500">Manage customers and their details. Customer code is auto-generated.</p>
    <button type="button" onclick="openForm()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Add Customer</button>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm">
    <table class="w-full text-sm">
        <thead class="text-left text-slate-500 bg-slate-50">
            <tr>
                <th class="px-5 py-3">Code</th>
                <th class="px-5 py-3">Name</th>
                <th class="px-5 py-3">Contact</th>
                <th class="px-5 py-3">Route</th>
                <th class="px-5 py-3">Salesman</th>
                <th class="px-5 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php if (empty($customers)): ?>
                <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">No customers yet.</td></tr>
            <?php else: foreach ($customers as $c): ?>
                <tr>
                    <td class="px-5 py-3 font-mono"><?= e($c['code']) ?></td>
                    <td class="px-5 py-3 font-medium"><?= e($c['name']) ?></td>
                    <td class="px-5 py-3"><?= e($c['contact']) ?></td>
                    <td class="px-5 py-3"><?= e($c['delivery_route']) ?></td>
                    <td class="px-5 py-3"><?= e($c['salesman']) ?></td>
                    <td class="px-5 py-3 text-right space-x-2">
                        <button type="button" onclick="openForm(<?= $c['id'] ?>)" class="text-blue-600 hover:underline text-sm">Edit</button>
                        <button type="button" onclick="doDelete(<?= $c['id'] ?>, '<?= e(addslashes($c['name'])) ?>')" class="text-red-600 hover:underline text-sm">Delete</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Customer form modal -->
<div id="formModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4 overflow-y-auto">
    <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl border border-slate-200 my-8">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 id="formTitle" class="font-semibold text-slate-800">Add Customer</h3>
            <button type="button" onclick="closeForm()" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <form id="customerForm" class="px-5 py-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="id" id="f_id">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Customer Code</label>
                <input type="text" name="code" id="f_code" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 field-locked px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Customer Name *</label>
                <input type="text" name="name" id="f_name" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Contact Number</label>
                <input type="text" name="contact" id="f_contact" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Delivery Route</label>
                <input type="text" name="delivery_route" id="f_delivery_route" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Sales Man</label>
                <input type="text" name="salesman" id="f_salesman" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">NTN No</label>
                <input type="text" name="ntn_no" id="f_ntn_no" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Sales Tax No</label>
                <input type="text" name="sales_tax_no" id="f_sales_tax_no" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">CNIC</label>
                <input type="text" name="cnic" id="f_cnic" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Address</label>
                <input type="text" name="address" id="f_address" class="w-full rounded-lg border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </form>
        <div class="px-5 py-3 border-t border-slate-200 flex justify-end gap-3">
            <button type="button" onclick="closeForm()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Cancel</button>
            <button type="button" onclick="submitForm()" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Save</button>
        </div>
    </div>
</div>

<script>
const CUSTOMERS = <?= json_encode($customers, JSON_HEX_TAG | JSON_HEX_APOS) ?>;
const NEXT_CODE = <?= json_encode($nextCode) ?>;

function openModal(id) { const m = document.getElementById(id); m.classList.remove('hidden'); m.classList.add('flex'); }
function closeModal(id) { const m = document.getElementById(id); m.classList.add('hidden'); m.classList.remove('flex'); }

function openForm(id) {
    document.getElementById('customerForm').reset();
    document.getElementById('f_id').value = '';
    document.getElementById('formTitle').textContent = 'Add Customer';
    // Code is auto-generated and read-only
    document.getElementById('f_code').value = NEXT_CODE;
    if (id) {
        const c = CUSTOMERS.find(x => x.id == id);
        if (c) {
            document.getElementById('formTitle').textContent = 'Edit Customer';
            document.getElementById('f_id').value = c.id;
            document.getElementById('f_code').value = c.code;
            document.getElementById('f_name').value = c.name;
            document.getElementById('f_contact').value = c.contact;
            document.getElementById('f_delivery_route').value = c.delivery_route;
            document.getElementById('f_salesman').value = c.salesman;
            document.getElementById('f_ntn_no').value = c.ntn_no;
            document.getElementById('f_sales_tax_no').value = c.sales_tax_no;
            document.getElementById('f_cnic').value = c.cnic;
            document.getElementById('f_address').value = c.address;
        }
    }
    openModal('formModal');
}

function closeForm() { closeModal('formModal'); }

function submitForm() {
    const f = document.getElementById('customerForm');
    const data = {
        action: 'save',
        id: f.id.value,
        code: f.code.value,
        name: f.name.value,
        contact: f.contact.value,
        delivery_route: f.delivery_route.value,
        salesman: f.salesman.value,
        ntn_no: f.ntn_no.value,
        sales_tax_no: f.sales_tax_no.value,
        cnic: f.cnic.value,
        address: f.address.value,
    };
    fetch('/controllers/customer_save.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showModal('Success', d.message, 'success'); closeForm(); setTimeout(() => location.reload(), 800); }
        else { showModal('Error', d.message, 'error'); }
    })
    .catch(() => showModal('Error', 'Submission failed.', 'error'));
}

function doDelete(id, name) {
    if (!confirm('Delete customer "' + name + '"? This cannot be undone.')) return;
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
}
</script>
<?php
$content = ob_get_clean();
render_page('Customers', $content, modal_markup_html());
