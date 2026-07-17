<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user']      = $user;
            header('Location: /views/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

$config = require __DIR__ . '/config/app.php';
$logo = asset($config['logo']);
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &middot; <?= e($config['app_name']) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/output.css')) ?>">
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-slate-200">
            <div class="flex flex-col items-center mb-6">
                <img src="<?= e($logo) ?>" alt="MJ Traders" class="h-20 w-auto mb-3">
                <h1 class="text-xl font-semibold text-slate-800"><?= e($config['app_name']) ?></h1>
                <p class="text-sm text-slate-500">Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                    <input type="text" name="username" autofocus required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" name="password" required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition">
                    Sign In
                </button>
            </form>

            <p class="text-xs text-slate-400 text-center mt-6">
                Default login &mdash; <span class="font-mono">admin</span> / <span class="font-mono">admin123</span>
            </p>
        </div>
    </div>
</body>
</html>
