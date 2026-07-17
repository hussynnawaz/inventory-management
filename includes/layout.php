<?php
// Shared authenticated layout
// Usage: require_once __DIR__ . '/../includes/layout.php';
//        then call render_page($title, $content_html) OR include after setting $page_title + $page_content
function render_page(string $title, string $content): void {
    $config = require __DIR__ . '/../config/app.php';
    $user = current_user();
    $logo = $config['logo'];
    $nav = [
        'dashboard'  => ['label' => 'Dashboard',  'href' => '/views/dashboard.php',  'icon' => 'M3 12l9-9 9 9M5 10v10h14V10'],
        'sales'      => ['label' => 'Sales',      'href' => '/views/sales.php',      'icon' => 'M3 3h18v18H3zM3 9h18M9 21V9'],
        'purchases'  => ['label' => 'Purchases',  'href' => '/views/purchases.php',  'icon' => 'M3 7h18v12H3zM3 7l3-4h12l3 4'],
        'returns'    => ['label' => 'Returns',    'href' => '/views/returns.php',    'icon' => 'M3 10h12M3 10l4-4M3 10l4 4M21 14H9m12 0l-4 4m4-4l-4-4'],
        'inventory'  => ['label' => 'Inventory',  'href' => '/views/inventory.php',  'icon' => 'M4 4h16v16H4zM4 9h16M9 4v16'],
        'reports'    => ['label' => 'Reports',    'href' => '/views/reports.php',    'icon' => 'M4 20V10M10 20V4M16 20v-6M22 20H2'],
    ];
    $current = basename($_SERVER['PHP_SELF']);
    ?>
    <!DOCTYPE html>
    <html lang="en" class="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= e($title) ?> &middot; <?= e($config['app_name']) ?></title>
        <link rel="stylesheet" href="/public/assets/css/output.css">
    </head>
    <body class="bg-slate-100 text-slate-800">
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <aside class="w-64 bg-white border-r border-slate-200 flex flex-col">
                <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-200">
                    <img src="/<?= e($logo) ?>" alt="MJ Traders" class="h-10 w-auto">
                    <span class="font-semibold text-slate-800 leading-tight">MJ Traders</span>
                </div>
                <nav class="flex-1 px-3 py-4 space-y-1">
                    <?php foreach ($nav as $key => $item): ?>
                        <a href="<?= $item['href'] ?>"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                                  <?= basename($item['href']) === $current ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100' ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
                            </svg>
                            <?= e($item['label']) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="px-5 py-4 border-t border-slate-200">
                    <p class="text-sm font-medium text-slate-700"><?= e($user['full_name'] ?? $user['username']) ?></p>
                    <p class="text-xs text-slate-400 mb-2"><?= e(ucfirst($user['role'])) ?></p>
                    <a href="/logout.php" class="text-sm text-red-600 hover:underline">Sign out</a>
                </div>
            </aside>

            <!-- Main -->
            <main class="flex-1 overflow-x-hidden">
                <header class="h-16 bg-white border-b border-slate-200 flex items-center px-8">
                    <h1 class="text-lg font-semibold text-slate-800"><?= e($title) ?></h1>
                </header>
                <div class="p-8">
                    <?= $content ?>
                </div>
            </main>
        </div>
    </body>
    </html>
    <?php
}
