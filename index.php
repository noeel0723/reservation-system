<?php
/**
 * Login & Landing Page
 * Sistem Reservasi Studio & Alat Siaran TVRI
 */
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/functions/user_helper.php';

if (isLoggedIn()) {
    redirectByRole();
}

$error = getFlash('error');
$success = getFlash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        $result = loginUser($pdo, $username, $password);
        if ($result['success']) {
            redirectByRole();
        } else {
            $error = $result['message'];
        }
    }
}

$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?= SITE_FULL_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.2rem;
            background:
                radial-gradient(1200px 380px at 50% 110%, rgba(255,255,255,0.9), rgba(255,255,255,0.2) 50%, transparent 65%),
                linear-gradient(180deg, #84cbd4 0%, #d8eff3 56%, #f4fafb 100%);
        }
        .auth-clouds {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .auth-clouds::before,
        .auth-clouds::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            background: rgba(255,255,255,0.55);
            filter: blur(2px);
        }
        .auth-clouds::before {
            width: 360px;
            height: 100px;
            left: -40px;
            bottom: 70px;
        }
        .auth-clouds::after {
            width: 420px;
            height: 120px;
            right: -50px;
            bottom: 55px;
        }
        .auth-wrap {
            width: 100%;
            max-width: 430px;
            position: relative;
            z-index: 1;
        }
        .auth-card {
            border: 1px solid rgba(255,255,255,0.65);
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255,255,255,0.82), rgba(255,255,255,0.7));
            box-shadow: 0 22px 52px rgba(0, 69, 84, 0.18);
            backdrop-filter: blur(8px);
            overflow: hidden;
        }
        .auth-header {
            padding: 1.4rem 1.4rem 0.7rem;
            text-align: center;
        }
        .auth-icon {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            margin: 0 auto 0.75rem;
            background: linear-gradient(145deg, rgba(255,255,255,0.95), rgba(233,241,246,0.92));
            border: 1px solid rgba(0,69,84,0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0, 69, 84, 0.1);
        }
        .auth-icon img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }
        .auth-title {
            font-size: 1.55rem;
            font-weight: 800;
            color: #10212f;
            margin-bottom: 0.25rem;
        }
        .auth-sub {
            color: #60788c;
            font-size: 0.86rem;
            margin-bottom: 0;
        }
        .auth-body {
            padding: 0.9rem 1.4rem 1.35rem;
        }
        .auth-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #40586a;
            margin-bottom: 0.35rem;
        }
        .auth-control {
            border: 1px solid #d8e4ec;
            border-radius: 12px;
            font-size: 0.86rem;
            padding: 0.65rem 0.8rem;
            background: rgba(255,255,255,0.9);
        }
        .auth-control:focus {
            border-color: #44A6B5;
            box-shadow: 0 0 0 0.18rem rgba(68, 166, 181, 0.18);
        }
        .auth-login-btn {
            margin-top: 0.55rem;
            border: none;
            border-radius: 12px;
            padding: 0.7rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #004554 0%, #0a7484 52%, #44A6B5 100%);
            box-shadow: 0 10px 20px rgba(0, 69, 84, 0.24);
        }
        .auth-login-btn:hover {
            color: #fff;
            filter: brightness(1.05);
        }
        .auth-foot {
            text-align: center;
            font-size: 0.8rem;
            color: #5f7488;
            margin-top: 0.95rem;
        }
        .auth-foot a {
            color: #004554;
            font-weight: 700;
            text-decoration: none;
        }
        .auth-brand {
            text-align: center;
            margin-top: 0.7rem;
            font-size: 0.76rem;
            color: #5f7488;
        }
        .auth-brand strong {
            color: #004554;
        }
    </style>
</head>
<body>
    <div class="auth-clouds"></div>

    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon"><img src="<?= BASE_URL ?>/assets/pictures/Logo_TVRI.svg.png" alt="TVRI logo"></div>
                <h1 class="auth-title">Sign in</h1>
                <p class="auth-sub">Access SITARU reservation workspace</p>
            </div>

            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success py-2 small mb-3"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                    <div class="mb-2">
                        <label for="username" class="auth-label">Username</label>
                        <input type="text" class="form-control auth-control" id="username" name="username" placeholder="Enter username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="mb-1">
                        <div class="d-flex align-items-center justify-content-between">
                            <label for="password" class="auth-label">Password</label>
                            <a href="#" class="small text-decoration-none" style="color:#60788c" data-bs-toggle="modal" data-bs-target="#lupaPasswordModal">Forgot password?</a>
                        </div>
                        <div class="input-group">
                            <input type="password" class="form-control auth-control" id="password" name="password" placeholder="Enter password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-radius:12px;border-color:#d8e4ec;margin-left:0.35rem">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn auth-login-btn w-100">
                        Get Started
                    </button>

                    <div class="auth-foot">
                        Don't have an account? <a href="<?= BASE_URL ?>/register.php">Register here</a>
                    </div>
                    <div class="auth-brand">Powered by <strong><?= SITE_NAME ?></strong></div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            var pwd = document.getElementById('password');
            var icon = this.querySelector('i');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>

    <div class="modal fade" id="lupaPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0" style="border-radius:14px">
                <div class="modal-body text-center py-4 px-4">
                    <div style="width:48px;height:48px;background:rgba(68,166,181,0.14);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem">
                        <i class="bi bi-key" style="font-size:1.15rem;color:#004554"></i>
                    </div>
                    <h6 class="fw-bold mb-2">Forgot Password?</h6>
                    <p class="text-muted small mb-3">Please contact the SITARU administrator to reset your password.</p>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
