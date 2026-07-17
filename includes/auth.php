<?php
// Session + auth helpers
session_start();

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
