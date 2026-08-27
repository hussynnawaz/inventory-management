<?php
// Session + auth helpers
session_start();
date_default_timezone_set('Asia/Karachi');

function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /index.php');
        exit;
    }
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// Build a correct public asset URL using the configured base_url.
// Paths are relative to the public/ directory (the web root).
function asset(string $path): string {
    $config = require __DIR__ . '/../config/app.php';
    $base = rtrim($config['base_url'], '/');
    return $base . '/public/' . ltrim($path, '/');
}
