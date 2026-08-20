<?php
// Shared authenticated layout — Bootstrap 5 (local)
if (!function_exists('render_page')) {
function render_page(string $title, string $content, string $footer = ''): void {
    $config = require __DIR__ . '/../config/app.php';
    $user = current_user();
    $logo = asset($config['logo']);
    $nav = [
        'dashboard'  => ['label' => 'Dashboard',      'icon' => 'speedometer2',     'href' => '/views/dashboard.php'],
        'products'   => ['label' => 'Products',       'icon' => 'boxes',             'href' => '/views/products.php'],
        'sale_new'   => ['label' => 'New Sale Order',  'icon' => 'plus-circle',       'href' => '/views/sale_order_new.php'],
        'sales'      => ['label' => 'Sales',           'icon' => 'receipt',           'href' => '/views/sales.php'],
        'customers'  => ['label' => 'Customers',       'icon' => 'people',            'href' => '/views/customers.php'],
        'salesmen'   => ['label' => 'Salesmen',        'icon' => 'person-badge',      'href' => '/views/salesmen.php'],
        'purchases'  => ['label' => 'Purchases',       'icon' => 'cart3',             'href' => '/views/purchases.php'],
        'returns'    => ['label' => 'Returns',         'icon' => 'arrow-return-left', 'href' => '/views/returns.php'],
        'reports'    => ['label' => 'Reports',         'icon' => 'bar-chart-line',    'href' => '/views/reports.php'],
    ];
    $icons = [
        'speedometer2'     => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4M3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707M2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10m9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5m.754-4.246a.389.389 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.389.389 0 0 0-.029-.518z"/><path d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A8 8 0 0 1 0 10m8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3"/></svg>',
        'plus-circle'      => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/></svg>',
        'receipt'          => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27m.217 1.338L2 2.118v11.764l.137.274.51-.51a.5.5 0 0 1 .707 0l.646.647.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.509.509.137-.274V2.118l-.137-.274-.51.51a.5.5 0 0 1-.707 0L12 1.707l-.646.647a.5.5 0 0 1-.708 0L10 1.707l-.646.647a.5.5 0 0 1-.708 0L8 1.707l-.646.647a.5.5 0 0 1-.708 0L6 1.707l-.646.647a.5.5 0 0 1-.708 0L4 1.707l-.646.647a.5.5 0 0 1-.708 0z"/><path d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m8-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5"/></svg>',
        'people'           => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.543.68-3 3-3zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/></svg>',
        'cart3'            => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/></svg>',
        'arrow-return-left'=> '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5"/></svg>',
        'boxes'            => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M7.752.066a.5.5 0 0 1 .496 0l3.75 2.143a.5.5 0 0 1 .252.434v3.995l3.498 2A.5.5 0 0 1 16 9.07v4.512a.5.5 0 0 1-.252.434l-3.75 2.143a.5.5 0 0 1-.496 0l-3.502-2-3.502 2.001a.5.5 0 0 1-.496 0l-3.75-2.143A.5.5 0 0 1 0 13.581V9.07a.5.5 0 0 1 .252-.434L3.75 6.638V2.643a.5.5 0 0 1 .252-.434zM4.25 7.504 1.508 9.071l2.742 1.567 2.742-1.567zM7.5 9.933l-2.75 1.571v3.134l2.75-1.571zm1 3.134 2.75 1.571v-3.134L8.5 9.933zm.508-3.636 2.742 1.567 2.742-1.567-2.742-1.567zm1.248-1.433V5.987l-2.75 1.571v2.01zM7.5 7.557v-2.01L4.75 3.976v2.01zM5.258 3.362l2.742 1.567 2.742-1.567L8 1.795zM15 9.933l-2.75 1.571v3.134L15 13.067zm-10.75 4.705v-3.134L1.5 9.933v3.134z"/></svg>',
        'bar-chart-line'   => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1zm1 12h2V2h-2zm-3 0V7H7v7zm-5 0v-3H2v3z"/></svg>',
        'person-badge'     => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M6.5 1A1.5 1.5 0 0 0 5 2.5V3H1.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5H11v-.5A1.5 1.5 0 0 0 9.5 1zm0 1h3a.5.5 0 0 1 .5.5v3a3.5 3.5 0 0 1-4 0v-.5a.5.5 0 0 1 .5-.5h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5"/><path d="M2 12s1-1 2.5-1 3.5 2 5 2 2.5-1 2.5-1v2s-1 1-2.5 1-3.5-2-5-2-2.5 1-2.5 1z"/><path d="M12 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4"/></svg>',
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
                        <?= $icons[$item['icon']] ?? '' ?>
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
