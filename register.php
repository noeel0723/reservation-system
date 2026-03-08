<?php
/**
 * Register Page
 */
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/functions/user_helper.php';

if (isLoggedIn()) {
    redirectByRole();
}

$error = '';
$success = getFlash('success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $data = [
        'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
        'username'     => trim($_POST['username'] ?? ''),
        'password'     => $_POST['password'] ?? '',
        'jabatan'      => trim($_POST['jabatan'] ?? ''),
        'no_telp'      => trim($_POST['no_telp'] ?? ''),
    ];

    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($data['nama_lengkap']) || empty($data['username']) || empty($data['password'])) {
        $error = 'Full name, username, and password are required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $data['username'])) {
        $error = 'Username must be 3-30 chars and only contain letters, numbers, underscore.';
    } elseif ($data['password'] !== $confirmPassword) {
        $error = 'Password confirmation does not match.';
    } else {
        $result = registerUser($pdo, $data);
        if ($result['success']) {
            setFlash('success', $result['message']);
            header('Location: ' . BASE_URL . '/index.php');
            exit;
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
    <title>Sign Up - <?= SITE_FULL_NAME ?></title>
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
        }
        .auth-clouds::before {
            width: 340px;
            height: 95px;
            left: -40px;
            bottom: 72px;
        }
        .auth-clouds::after {
            width: 410px;
            height: 120px;
            right: -45px;
            bottom: 52px;
        }
        .auth-wrap {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 1;
        }
        .auth-card {
            border: 1px solid rgba(255,255,255,0.65);
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255,255,255,0.82), rgba(255,255,255,0.72));
            box-shadow: 0 22px 52px rgba(0, 69, 84, 0.18);
            backdrop-filter: blur(8px);
            overflow: hidden;
        }
        .auth-header {
            padding: 1.25rem 1.45rem 0.7rem;
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
            font-size: 1.4rem;
            font-weight: 800;
            color: #10212f;
            margin-bottom: 0.2rem;
        }
        .auth-sub {
            color: #60788c;
            font-size: 0.84rem;
            margin-bottom: 0;
        }
        .auth-body {
            padding: 0.85rem 1.45rem 1.35rem;
        }
        .auth-label {
            font-size: 0.76rem;
            font-weight: 700;
            color: #40586a;
            margin-bottom: 0.3rem;
        }
        .auth-control {
            border: 1px solid #d8e4ec;
            border-radius: 12px;
            font-size: 0.86rem;
            padding: 0.62rem 0.78rem;
            background: rgba(255,255,255,0.92);
        }
        .auth-control:focus {
            border-color: #44A6B5;
            box-shadow: 0 0 0 0.18rem rgba(68, 166, 181, 0.18);
        }
        .auth-submit-btn {
            margin-top: 0.5rem;
            border: none;
            border-radius: 12px;
            padding: 0.7rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #004554 0%, #0a7484 52%, #44A6B5 100%);
            box-shadow: 0 10px 20px rgba(0, 69, 84, 0.24);
        }
        .auth-submit-btn:hover {
            color: #fff;
            filter: brightness(1.05);
        }
        .auth-foot {
            text-align: center;
            font-size: 0.8rem;
            color: #5f7488;
            margin-top: 0.9rem;
        }
        .auth-foot a {
            color: #004554;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="auth-clouds"></div>

    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-icon"><img src="<?= BASE_URL ?>/assets/pictures/Logo_TVRI.svg.png" alt="TVRI logo"></div>
                <h1 class="auth-title">Sign up</h1>
                <p class="auth-sub">Create a new account to access SITARU workspace.</p>
            </div>

            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small mb-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success py-2 small mb-3"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                    <div class="mb-2">
                        <label for="nama_lengkap" class="auth-label">Full Name</label>
                        <input type="text" class="form-control auth-control" id="nama_lengkap" name="nama_lengkap" required value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>">
                    </div>

                    <div class="mb-2">
                        <label for="username" class="auth-label">Username</label>
                        <input type="text" class="form-control auth-control" id="username" name="username" placeholder="example: budi_santoso" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label for="jabatan" class="auth-label">Position</label>
                            <input type="text" class="form-control auth-control" id="jabatan" name="jabatan" placeholder="Producer" value="<?= htmlspecialchars($_POST['jabatan'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="no_telp" class="auth-label">Phone</label>
                            <input type="text" class="form-control auth-control" id="no_telp" name="no_telp" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_telp'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-12 col-md-6">
                            <label for="password" class="auth-label">Password</label>
                            <input type="password" class="form-control auth-control" id="password" name="password" placeholder="Minimum 6 characters" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="confirm_password" class="auth-label">Confirm Password</label>
                            <input type="password" class="form-control auth-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn auth-submit-btn w-100">
                        Create Account
                    </button>

                    <div class="auth-foot">
                        Already have an account? <a href="<?= BASE_URL ?>/index.php">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
