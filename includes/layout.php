<?php
// Shared authenticated layout — Bootstrap 5 (local)
require_once __DIR__ . '/icons.php';
if (!function_exists('render_page')) {
function render_page(string $title, string $content, string $footer = ''): void {
    $config = require __DIR__ . '/../config/app.php';
    $user = current_user();
    $logo = asset($config['logo']);
    $nav = [
        'dashboard'  => ['label' => 'Dashboard',      'icon' => 'speedometer',        'href' => '/views/dashboard.php'],
        'products'   => ['label' => 'Products',       'icon' => 'boxes',              'href' => '/views/products.php'],
        'sale_new'   => ['label' => 'New Sale Order',  'icon' => 'plus-circle',        'href' => '/views/sale_order_new.php'],
        'sales'      => ['label' => 'Sales',           'icon' => 'receipt',            'href' => '/views/sales.php'],
        'customers'  => ['label' => 'Customers',       'icon' => 'people',             'href' => '/views/customers.php'],
        'suppliers'  => ['label' => 'Suppliers',       'icon' => 'truck',              'href' => '/views/suppliers.php'],
        'salesmen'   => ['label' => 'Salesmen',        'icon' => 'person-badge',       'href' => '/views/salesmen.php'],
        'purchases'  => ['label' => 'Purchases',       'icon' => 'cart',               'href' => '/views/purchases.php'],
        'ledger'     => ['label' => 'Ledger',           'icon' => 'wallet',             'href' => '/views/ledger.php'],
        'returns'    => ['label' => 'Returns',         'icon' => 'arrow-return-left',  'href' => '/views/returns.php'],
        'reports'    => ['label' => 'Reports',         'icon' => 'bar-chart',          'href' => '/views/reports.php'],
    ];
    $current = basename($_SERVER['PHP_SELF']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> &middot; <?= e($config['app_name']) ?></title>
        <link rel="stylesheet" href="<?= e(asset('assets/css/bootstrap.min.css')) ?>">
        <style>
            :root {
                --sidebar-width: 250px;
                --sidebar-bg: #111827;
                --sidebar-active: #3b82f6;
                --sidebar-hover: rgba(255,255,255,.06);
                --topbar-h: 56px;
            }
            * { box-sizing: border-box; }
            body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; margin: 0; }

            /* Sidebar */
            .app-sidebar {
                position: fixed; top: 0; left: 0; bottom: 0;
                width: var(--sidebar-width);
                background: var(--sidebar-bg);
                display: flex; flex-direction: column;
                z-index: 1040;
                transition: transform .25s ease;
            }
            .sidebar-brand {
                height: var(--topbar-h);
                display: flex; align-items: center; gap: .7rem;
                padding: 0 1.2rem;
                border-bottom: 1px solid rgba(255,255,255,.08);
                flex-shrink: 0;
            }
            .sidebar-brand img { height: 34px; border-radius: 8px; }
            .sidebar-brand span { color: #fff; font-weight: 700; font-size: .95rem; letter-spacing: -.01em; }
            .sidebar-nav { flex: 1; overflow-y: auto; padding: .75rem .65rem; }
            .sidebar-nav::-webkit-scrollbar { width: 4px; }
            .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }
            .sidebar-section {
                font-size: .65rem; font-weight: 700; letter-spacing: .1em;
                text-transform: uppercase; color: rgba(255,255,255,.3);
                padding: .85rem .65rem .35rem;
            }
            .nav-item-link {
                display: flex; align-items: center; gap: .7rem;
                padding: .55rem .75rem;
                border-radius: .5rem;
                color: rgba(255,255,255,.6);
                text-decoration: none;
                font-size: .825rem;
                font-weight: 500;
                margin-bottom: 2px;
                transition: all .15s ease;
            }
            .nav-item-link:hover { background: var(--sidebar-hover); color: #fff; }
            .nav-item-link.active {
                background: var(--sidebar-active); color: #fff;
                box-shadow: 0 2px 10px rgba(59,130,246,.35);
            }
            .nav-item-link svg { flex-shrink: 0; opacity: .7; }
            .nav-item-link.active svg { opacity: 1; }
            .sidebar-footer {
                padding: .85rem 1rem;
                border-top: 1px solid rgba(255,255,255,.08);
                flex-shrink: 0;
            }
            .sidebar-footer .user-name { color: #fff; font-size: .8rem; font-weight: 600; }
            .sidebar-footer .user-role { color: rgba(255,255,255,.4); font-size: .7rem; }
            .sidebar-footer .sign-out {
                color: #f87171; font-size: .75rem; text-decoration: none;
                transition: color .15s;
            }
            .sidebar-footer .sign-out:hover { color: #fca5a5; }

            /* Main area */
            .app-main {
                margin-left: var(--sidebar-width);
                min-height: 100vh;
                display: flex; flex-direction: column;
                transition: margin .25s ease;
            }
            .app-topbar {
                height: var(--topbar-h);
                background: #fff;
                border-bottom: 1px solid #e2e8f0;
                display: flex; align-items: center;
                padding: 0 1.5rem;
                position: sticky; top: 0; z-index: 100;
                box-shadow: 0 1px 3px rgba(0,0,0,.04);
            }
            .app-topbar .page-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
            .app-body { flex: 1; padding: 1.25rem 1.5rem; }

            /* Stat Cards */
            .stat-card {
                border: none;
                border-radius: .75rem;
                box-shadow: 0 1px 3px rgba(0,0,0,.06);
                transition: box-shadow .2s, transform .2s;
            }
            .stat-card:hover {
                box-shadow: 0 4px 12px rgba(0,0,0,.1);
                transform: translateY(-1px);
            }
            .stat-card .stat-icon {
                width: 44px; height: 44px;
                border-radius: .6rem;
                display: flex; align-items: center; justify-content: center;
                font-size: 1.2rem;
                flex-shrink: 0;
            }
            .stat-card .stat-label {
                font-size: .72rem; font-weight: 600; text-transform: uppercase;
                letter-spacing: .05em; color: #64748b;
            }
            .stat-card .stat-value {
                font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1.2;
            }

            /* Card Tables */
            .card-table {
                border: none; border-radius: .75rem;
                box-shadow: 0 1px 3px rgba(0,0,0,.06);
                overflow: hidden;
            }
            .card-table .card-header {
                background: #fff;
                border-bottom: 1px solid #f1f5f9;
                padding: .85rem 1.25rem;
            }
            .card-table .table { margin: 0; }
            .card-table .table thead th {
                background: #f8fafc;
                color: #64748b;
                font-size: .72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .05em;
                border-bottom: 1px solid #f1f5f9;
                padding: .6rem 1rem;
            }
            .card-table .table tbody td {
                padding: .7rem 1rem;
                font-size: .85rem;
                color: #334155;
                border-bottom: 1px solid #f8fafc;
                vertical-align: middle;
            }
            .card-table .table tbody tr:last-child td { border-bottom: none; }
            .card-table .table tbody tr { transition: background .15s; }
            .card-table .table tbody tr:hover td { background: #f8fafc; }

            /* Cards */
            .card {
                border: none;
                border-radius: .75rem;
                box-shadow: 0 1px 3px rgba(0,0,0,.06);
            }
            .card-header {
                background: #fff;
                border-bottom: 1px solid #f1f5f9;
            }

            /* Forms */
            .form-label { font-size: .8rem; font-weight: 600; color: #334155; margin-bottom: .3rem; }
            .form-control, .form-select {
                border-color: #e2e8f0;
                border-radius: .5rem;
                font-size: .85rem;
                padding: .5rem .75rem;
                transition: border-color .15s, box-shadow .15s;
            }
            .form-control:focus, .form-select:focus {
                border-color: var(--sidebar-active);
                box-shadow: 0 0 0 3px rgba(59,130,246,.1);
            }
            .form-control[readonly] { background: #f8fafc; color: #64748b; }

            /* Buttons */
            .btn { border-radius: .5rem; font-size: .85rem; font-weight: 500; padding: .45rem 1rem; transition: all .15s; }
            .btn-primary { background: #3b82f6; border-color: #3b82f6; }
            .btn-primary:hover { background: #2563eb; border-color: #2563eb; }
            .btn-sm { padding: .35rem .75rem; font-size: .8rem; }

            /* Nav tabs */
            .nav-tabs .nav-link {
                border: none;
                color: #94a3b8;
                border-bottom: 2px solid transparent;
                padding: .4rem .75rem;
                transition: all .15s;
            }
            .nav-tabs .nav-link:hover { color: #334155; }
            .nav-tabs .nav-link.active {
                color: var(--sidebar-active);
                border-bottom-color: var(--sidebar-active);
                background: transparent;
            }

            /* Badges */
            .badge-soft-success { background: #d1fae5; color: #065f46; font-size: .72rem; }
            .badge-soft-danger  { background: #fee2e2; color: #991b1b; font-size: .72rem; }
            .badge-soft-warning { background: #fef3c7; color: #92400e; font-size: .72rem; }
            .badge-soft-info    { background: #dbeafe; color: #1e40af; font-size: .72rem; }

            /* Responsive */
            @media (max-width: 991.98px) {
                .app-sidebar { transform: translateX(-100%); }
                .app-sidebar.show { transform: translateX(0); }
                .app-main { margin-left: 0; }
            }
        </style>
    </head>
    <body>
        <!-- Sidebar -->
        <aside class="app-sidebar">
            <div class="sidebar-brand">
                <img src="<?= e($logo) ?>" alt="MJ Traders">
                <span>MJ Traders</span>
            </div>
            <nav class="sidebar-nav">
                <div class="sidebar-section">Main Menu</div>
                <?php foreach ($nav as $item): ?>
                    <a href="<?= $item['href'] ?>" class="nav-item-link <?= basename($item['href']) === $current ? 'active' : '' ?>">
                        <?= icon($item['icon'], 16) ?>
                        <?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sidebar-footer">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                         style="width:32px;height:32px;font-size:.8rem;background:#3b82f6;flex-shrink:0">
                        <?= strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="user-name"><?= e($user['full_name'] ?? $user['username']) ?></div>
                        <div class="user-role"><?= e(ucfirst($user['role'])) ?></div>
                    </div>
                </div>
                <a href="/logout.php" class="sign-out d-block">&#8617; Sign out</a>
            </div>
        </aside>

        <!-- Main -->
        <div class="app-main">
            <header class="app-topbar">
                <h1 class="page-title"><?= e($title) ?></h1>
            </header>
            <main class="app-body">
                <?= $content ?>
            </main>
        </div>

        <script src="<?= e(asset('assets/js/bootstrap.bundle.min.js')) ?>"></script>
        <?= $footer ?>
    </body>
    </html>
    <?php
}
}
