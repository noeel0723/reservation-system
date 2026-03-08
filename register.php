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

    // Validasi
    if (empty($data['nama_lengkap']) || empty($data['username']) || empty($data['password'])) {
        $error = 'Nama, username, dan password wajib diisi.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $data['username'])) {
        $error = 'Username hanya boleh huruf, angka, dan underscore (3-30 karakter).';
    } elseif ($data['password'] !== $confirmPassword) {
        $error = 'Konfirmasi password tidak cocok.';
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - <?= SITE_FULL_NAME ?></title>
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
            padding: 2rem 0;
        }
        .register-card {
            width: 100%;
            max-width: 500px;
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 69, 84, 0.35);
        }
        .register-header {
            background: linear-gradient(135deg, #004554 0%, #006d7a 100%);
            color: #fff;
            padding: 1.5rem 2rem;
            text-align: center;
        }
        .register-body { padding: 2rem; }
        .btn-register {
            padding: 0.75rem;
            font-weight: 600;
            background-color: #44A6B5;
            border-color: #44A6B5;
        }
        .btn-register:hover {
            background-color: #004554;
            border-color: #004554;
        }
        .form-control:focus {
            border-color: #44A6B5;
            box-shadow: 0 0 0 0.2rem rgba(68, 166, 181, 0.2);
        }
        a { color: #44A6B5; }
        a:hover { color: #004554; }
    </style>
</head>
<body>
    <div class="card register-card">
        <div class="register-header">
            <img src="<?= BASE_URL ?>/assets/pictures/Logo_TVRI.svg.png" alt="TVRI"
                 width="64" height="64" style="width:64px;height:64px;object-fit:contain;display:block;margin:0 auto 0.75rem">
            <h5 class="fw-bold mb-1">Registrasi Akun Staff</h5>
            <small class="opacity-75">Bergabung dengan <?= SITE_NAME ?></small>
        </div>
        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label fw-medium">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                           required value="<?= htmlspecialchars($_POST['nama_lengkap'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label fw-medium">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="cth: budi_santoso" required
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <small class="text-muted">Huruf, angka, dan underscore (3-30 karakter)</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="jabatan" class="form-label fw-medium">Jabatan</label>
                        <input type="text" class="form-control" id="jabatan" name="jabatan"
                               placeholder="cth: Produser"
                               value="<?= htmlspecialchars($_POST['jabatan'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="no_telp" class="form-label fw-medium">No. Telepon</label>
                        <input type="text" class="form-control" id="no_telp" name="no_telp"
                               placeholder="08xxxxxxxxxx"
                               value="<?= htmlspecialchars($_POST['no_telp'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-medium">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Minimal 6 karakter" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label fw-medium">Konfirmasi Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           required>
                </div>

                <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                    <i class="bi bi-person-plus me-1"></i>Daftar
                </button>

                <div class="text-center">
                    <small class="text-muted">Sudah punya akun?
                        <a href="<?= BASE_URL ?>/index.php" class="text-decoration-none fw-medium">Login di sini</a>
                    </small>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
