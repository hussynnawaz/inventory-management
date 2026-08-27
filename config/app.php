<?php
// Application configuration
date_default_timezone_set('Asia/Karachi');
return [
    'app_name' => 'MJ Traders Inventory',
    // Base URL of the app relative to web root, e.g. '/awais' or '' if public/ is the doc root.
    // Leave empty and serve public/ as the document root for clean URLs,
    // or set to your subfolder (e.g. '/awais') when placed inside htdocs/awais.
    'base_url' => '',
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'inventory',
        'user'     => 'root',
        'pass'     => '',
        'charset'  => 'utf8mb4',
    ],
    'logo' => 'assets/mj-traders.png',
];
