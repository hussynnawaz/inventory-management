<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/modal.php';
require_login();

$products = $pdo->query('SELECT * FROM products ORDER BY name ASC')->fetchAll();

$totalProducts = count($products);
$totalStock    = (int)$pdo->query('SELECT COALESCE(SUM(quantity),0) FROM products')->fetchColumn();
$lowStock      = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE quantity <= 5')->fetchColumn();
$totalValue    = (float)$pdo->query('SELECT COALESCE(SUM(sale_price * quantity),0) FROM products')->fetchColumn();

ob_start();
?>
<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M7.752.066a.5.5 0 0 1 .496 0l3.75 2.143a.5.5 0 0 1 .252.434v3.995l3.498 2A.5.5 0 0 1 16 9.07v4.512a.5.5 0 0 1-.252.434l-3.75 2.143a.5.5 0 0 1-.496 0l-3.502-2-3.502 2.001a.5.5 0 0 1-.496 0l-3.75-2.143A.5.5 0 0 1 0 13.581V9.07a.5.5 0 0 1 .252-.434L3.75 6.638V2.643a.5.5 0 0 1 .252-.434zM4.25 7.504 1.508 9.071l2.742 1.567 2.742-1.567zM7.5 9.933l-2.75 1.571v3.134l2.75-1.571zm1 3.134 2.75 1.571v-3.134L8.5 9.933zm.508-3.636 2.742 1.567 2.742-1.567-2.742-1.567zm1.248-1.433V5.987l-2.75 1.571v2.01zM7.5 7.557v-2.01L4.75 3.976v2.01zM5.258 3.362l2.742 1.567 2.742-1.567L8 1.795zM15 9.933l-2.75 1.571v3.134L15 13.067zm-10.75 4.705v-3.134L1.5 9.933v3.134z"/></svg>
                </div>
                <div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-value"><?= number_format($totalProducts) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#d1fae5;color:#065f46;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v2A1.5 1.5 0 0 1 14.5 7H9v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7H1.5A1.5 1.5 0 0 1 0 5.5zM6.854 10.146a.5.5 0 1 0-.708.708L7.293 12l-1.147 1.146a.5.5 0 0 0 .708.708L8 12.707l1.146 1.147a.5.5 0 0 0 .708-.708L8.707 12l1.147-1.146a.5.5 0 0 0-.708-.708L8 11.293z"/></svg>
                </div>
                <div>
                    <div class="stat-label">Total Stock Units</div>
                    <div class="stat-value"><?= number_format($totalStock) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#fee2e2;color:#991b1b;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16M7 6.5v3a.5.5 0 0 0 1 0v-3a.5.5 0 0 0-1 0M8 11a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/></svg>
                </div>
                <div>
                    <div class="stat-label">Low Stock (≤5)</div>
                    <div class="stat-value"><?= number_format($lowStock) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="stat-icon" style="background:#fef3c7;color:#92400e;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/></svg>
                </div>
                <div>
                    <div class="stat-label">Inventory Value</div>
                    <div class="stat-value" style="font-size:1.2rem;">Rs <?= number_format($totalValue, 0) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Search + Add -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label small fw-medium">Search Products</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0"/></svg></span>
                    <input type="text" id="prodSearch" oninput="applyFilters()" placeholder="Search name, SKU, category..." class="form-control border-start-0">
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <button type="button" onclick="openForm()" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/></svg>
                    Add Product
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
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th class="text-end">Cost Price</th>
                    <th class="text-end">Sale Price</th>
                    <th class="text-end">Stock</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="productTableBody"></tbody>
        </table>
    </div>
    <div id="emptyState" class="text-center py-5 d-none">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="text-muted mb-2" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h13A1.5 1.5 0 0 1 16 3.5v2A1.5 1.5 0 0 1 14.5 7H9v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7H1.5A1.5 1.5 0 0 1 0 5.5zM6.854 10.146a.5.5 0 1 0-.708.708L7.293 12l-1.147 1.146a.5.5 0 0 0 .708.708L8 12.707l1.146 1.147a.5.5 0 0 0 .708-.708L8.707 12l1.147-1.146a.5.5 0 0 0-.708-.708L8 11.293z"/></svg>
        <p class="text-muted small mb-1">No products found</p>
        <p class="text-muted" style="font-size:.75rem">Add your first product to get started.</p>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 id="productModalTitle" class="modal-title fw-bold">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" name="id" id="p_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="p_name" autocomplete="off" required class="form-control" placeholder="e.g. Widget A">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">SKU <span class="text-danger">*</span></label>
                            <input type="text" name="sku" id="p_sku" autocomplete="off" required class="form-control font-monospace" placeholder="e.g. WDG-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Category</label>
                            <input type="text" name="category" id="p_category" autocomplete="off" class="form-control" placeholder="e.g. Electronics">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Initial Stock</label>
                            <input type="number" name="quantity" id="p_quantity" min="0" value="0" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Purchase Price (Rs)</label>
                            <input type="number" name="cost_price" id="p_cost_price" min="0" step="0.01" value="0" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Selling Price (Rs)</label>
                            <input type="number" name="sale_price" id="p_sale_price" min="0" step="0.01" value="0" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-medium">Description</label>
                            <textarea name="description" id="p_description" rows="2" class="form-control" placeholder="Optional product description..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitProduct()" class="btn btn-primary">Save Product</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Inventory Modal -->
<div class="modal fade" id="stockModal" tabindex="-1" aria-labelledby="stockModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header border-0 pb-0">
                <h5 id="stockModalTitle" class="modal-title fw-bold">Update Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center fw-bold small" style="width:40px;height:40px;flex-shrink:0" id="stockAvatar">P</div>
                        <div>
                            <div class="fw-bold small" id="stockProductName">Product</div>
                            <small class="text-muted">Current Stock: <span class="fw-semibold" id="stockCurrentQty">0</span></small>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="stock_product_id">
                <div class="mb-3">
                    <label class="form-label small fw-medium">Adjustment Mode</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary stock-mode-btn active" data-mode="set" onclick="setStockMode('set')">Set To</button>
                        <button type="button" class="btn btn-sm btn-outline-primary stock-mode-btn" data-mode="add" onclick="setStockMode('add')">Add Stock</button>
                        <button type="button" class="btn btn-sm btn-outline-primary stock-mode-btn" data-mode="subtract" onclick="setStockMode('subtract')">Remove</button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Quantity <span class="text-danger">*</span></label>
                    <input type="number" id="stock_quantity" min="0" value="0" class="form-control fw-semibold">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" onclick="submitStock()" class="btn btn-primary">Update Stock</button>
            </div>
        </div>
    </div>
</div>

<script>
const PRODUCTS = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS) ?>;

function esc(s) { if (!s) return ''; return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
function escQ(s) { if (!s) return ''; return s.replace(/\\/g, '\\\\').replace(/'/g, "\\'"); }
function fmt(n) { return 'Rs ' + Number(n).toLocaleString('en-PK', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }

var productModal = null;
var stockModal = null;
var stockMode = 'set';

function openForm(id) {
    document.getElementById('productForm').reset();
    document.getElementById('p_id').value = '';
    document.getElementById('productModalTitle').textContent = 'Add Product';
    if (id) {
        const p = PRODUCTS.find(x => x.id == id);
        if (p) {
            document.getElementById('productModalTitle').textContent = 'Edit Product';
            document.getElementById('p_id').value = p.id;
            document.getElementById('p_name').value = p.name;
            document.getElementById('p_sku').value = p.sku;
            document.getElementById('p_category').value = p.category;
            document.getElementById('p_quantity').value = p.quantity;
            document.getElementById('p_cost_price').value = p.cost_price;
            document.getElementById('p_sale_price').value = p.sale_price;
            document.getElementById('p_description').value = p.description || '';
        }
    }
    if (!productModal) productModal = new bootstrap.Modal(document.getElementById('productModal'));
    productModal.show();
}

function openStock(id) {
    const p = PRODUCTS.find(x => x.id == id);
    if (!p) return;
    document.getElementById('stock_product_id').value = p.id;
    document.getElementById('stockProductName').textContent = p.name;
    document.getElementById('stockCurrentQty').textContent = p.quantity;
    document.getElementById('stockAvatar').textContent = p.name.charAt(0).toUpperCase();
    document.getElementById('stock_quantity').value = 0;
    setStockMode('set');
    if (!stockModal) stockModal = new bootstrap.Modal(document.getElementById('stockModal'));
    stockModal.show();
}

function setStockMode(mode) {
    stockMode = mode;
    document.querySelectorAll('.stock-mode-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.mode === mode);
    });
    const labels = { set: 'Set quantity to', add: 'Add quantity', subtract: 'Remove quantity' };
    document.querySelector('#stockModal .form-label[for="stock_quantity"]')?.remove();
}

function renderTable(list) {
    const tbody = document.getElementById('productTableBody');
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
    list.forEach(p => {
        const tr = document.createElement('tr');
        const stockBadge = p.quantity <= 5
            ? '<span class="badge bg-danger">' + p.quantity + '</span>'
            : '<span class="fw-semibold">' + p.quantity + '</span>';
        tr.innerHTML =
            '<td><span class="font-monospace fw-semibold">' + esc(p.sku) + '</span></td>' +
            '<td class="fw-medium">' + esc(p.name) + '</td>' +
            '<td>' + (esc(p.category) || '<span class="text-muted">-</span>') + '</td>' +
            '<td class="text-end">' + fmt(p.cost_price) + '</td>' +
            '<td class="text-end">' + fmt(p.sale_price) + '</td>' +
            '<td class="text-end">' + stockBadge + '</td>' +
            '<td class="text-end">' +
                '<button onclick="openStock(' + p.id + ')" class="btn btn-sm btn-outline-success me-1" title="Update Stock">Stock</button>' +
                '<button onclick="openForm(' + p.id + ')" class="btn btn-sm btn-outline-primary me-1">Edit</button>' +
                '<button onclick="doDelete(' + p.id + ', \'' + escQ(p.name) + '\')" class="btn btn-sm btn-outline-danger">Delete</button>' +
            '</td>';
        tbody.appendChild(tr);
    });
}

function applyFilters() {
    const q = document.getElementById('prodSearch').value.toLowerCase().trim();
    renderTable(PRODUCTS.filter(p => {
        if (q && ![p.name, p.sku, p.category].join(' ').toLowerCase().includes(q)) return false;
        return true;
    }));
}

function submitProduct() {
    const f = document.getElementById('productForm');
    const data = {
        action: 'save', id: f.id.value, name: f.name.value, sku: f.sku.value,
        category: f.category.value, description: f.description.value,
        cost_price: f.cost_price.value, sale_price: f.sale_price.value,
        quantity: f.quantity.value
    };
    if (!data.name.trim()) { showModal('Validation Error', 'Product Name is required.', 'error'); return; }
    if (!data.sku.trim()) { showModal('Validation Error', 'SKU is required.', 'error'); return; }
    fetch('/controllers/product_save.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showModal('Success', d.message, 'success'); if (productModal) productModal.hide(); setTimeout(() => location.reload(), 800); }
        else { showModal('Error', d.message, 'error'); }
    })
    .catch(() => showModal('Error', 'Submission failed.', 'error'));
}

function submitStock() {
    const id  = document.getElementById('stock_product_id').value;
    const qty = document.getElementById('stock_quantity').value;
    if (!id || qty === '') { showModal('Validation Error', 'Please enter a quantity.', 'error'); return; }
    fetch('/controllers/product_save.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'update_stock', id: id, mode: stockMode, quantity: qty })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showModal('Success', d.message, 'success');
            if (stockModal) stockModal.hide();
            setTimeout(() => location.reload(), 800);
        } else {
            showModal('Error', d.message, 'error');
        }
    })
    .catch(() => showModal('Error', 'Stock update failed.', 'error'));
}

function doDelete(id, name) {
    if (!confirm('Delete product "' + name + '"? This cannot be undone.')) return;
    fetch('/controllers/product_save.php', {
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

renderTable(PRODUCTS);
</script>
<?php
$content = ob_get_clean();
render_page('Products', $content, modal_markup_html());
