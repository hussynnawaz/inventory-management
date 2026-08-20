<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

$error = '';

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
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &middot; <?= e($config['app_name']) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/bootstrap.min.css')) ?>">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        .login-card {
            max-width: 420px;
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        .login-card .card-body { padding: 2.5rem; }
        .form-control {
            border-color: #e2e8f0;
            border-radius: .5rem;
            padding: .6rem .85rem;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.1);
        }
        .btn-primary {
            background: #3b82f6;
            border-color: #3b82f6;
            border-radius: .5rem;
            padding: .6rem;
            font-weight: 600;
        }
        .btn-primary:hover { background: #2563eb; border-color: #2563eb; }
    </style>
</head>
<body>
    <div class="min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="w-100">
            <div class="card login-card mx-auto">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img src="<?= e($logo) ?>" alt="MJ Traders" class="mb-3" style="height:70px">
                        <h4 class="fw-bold text-dark mb-1">MJ Traders Inventory</h4>
                        <p class="text-muted small">Sign in to your account</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Username</label>
                            <input type="text" name="username" autofocus required class="form-control" placeholder="Enter username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Password</label>
                            <input type="password" name="password" required class="form-control" placeholder="Enter password">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Sign In</button>
                    </form>

                    <p class="text-muted text-center small mt-4 mb-0">
                        Default login &mdash; <code>admin</code> / <code>admin123</code>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
