<?php
/**
 * Login & Landing Page
 * Sistem Reservasi Studio & Alat Siaran TVRI
 */
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/functions/user_helper.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    redirectByRole();
}

$error = getFlash('error');
$success = getFlash('success');

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_FULL_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #B2D5E2 0%, #44A6B5 50%, #004554 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 69, 84, 0.35);
        }
        .login-header {
            background: linear-gradient(135deg, #004554 0%, #006d7a 100%);
            color: #fff;
            padding: 2rem 2rem 1.5rem;
            text-align: center;
        }
        .login-header .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }
        .login-body { padding: 2rem; }
        .form-control:focus {
            border-color: #44A6B5;
            box-shadow: 0 0 0 0.2rem rgba(68, 166, 181, 0.2);
        }
        .btn-login {
            padding: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
            background-color: #44A6B5;
            border-color: #44A6B5;
        }
        .btn-login:hover {
            background-color: #004554;
            border-color: #004554;
        }
        a { color: #44A6B5; }
        a:hover { color: #004554; }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <div class="logo-icon">
                <img src="<?= BASE_URL ?>/assets/pictures/Logo_TVRI.svg.png" alt="TVRI" width="72" height="72" style="width:72px;height:72px;object-fit:contain">
            </div>
            <h4 class="fw-bold mb-1"><?= SITE_NAME ?></h4>
            <p class="mb-0 opacity-75 small">Sistem Tata Ruang & Alat TVRI</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                    <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="mb-3">
                    <label for="username" class="form-label fw-medium">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                               placeholder="Masukkan username" required
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-medium">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Masukkan password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
                </button>

                <div class="text-center">
                    <small class="text-muted">Belum punya akun?
                        <a href="<?= BASE_URL ?>/register.php" class="text-decoration-none fw-medium">Daftar di sini</a>
                    </small>
                </div>
                <div class="text-center mt-2">
                    <small><a href="#" class="text-muted text-decoration-none" data-bs-toggle="modal" data-bs-target="#lupaPasswordModal">
                        <i class="bi bi-question-circle me-1"></i>Lupa Password?
                    </a></small>
                </div>
            </form>

            <hr class="my-3">
            <div class="text-center">
                <small class="text-muted">Demo: admin / password</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const icon = this.querySelector('i');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>
    <!-- Lupa Password Modal -->
    <div class="modal fade" id="lupaPasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4 px-4">
                    <div style="width:52px;height:52px;background:rgba(68,166,181,0.12);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                        <i class="bi bi-key" style="font-size:1.4rem;color:#44A6B5"></i>
                    </div>
                    <h6 class="fw-bold mb-2">Lupa Password?</h6>
                    <p class="text-muted small mb-3">Hubungi administrator sistem SITARU untuk mereset password akun Anda.</p>
                    <div style="background:rgba(68,166,181,0.08);border:1px solid rgba(68,166,181,0.2);border-radius:8px;padding:0.6rem 0.85rem;font-size:0.8rem;text-align:left;margin-bottom:1rem">
                        <i class="bi bi-info-circle me-1" style="color:#44A6B5"></i>
                        Informasikan <strong>username</strong> Anda kepada admin.
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" data-bs-dismiss="modal">Mengerti</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
