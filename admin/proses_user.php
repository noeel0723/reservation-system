<?php
/**
 * Proses User Management
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/user_helper.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/kelola_user.php');
    exit;
}

requireCsrf();

$action = $_POST['action'] ?? '';

// Handle create_user before user_id check (no user_id needed)
if ($action === 'create_user') {
    $data = [
        'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
        'username'     => trim($_POST['username'] ?? ''),
        'password'     => $_POST['password'] ?? '',
        'role'         => $_POST['role'] ?? 'Staff',
        'jabatan'      => trim($_POST['jabatan'] ?? ''),
        'no_telp'      => trim($_POST['no_telp'] ?? ''),
    ];

    if (empty($data['nama_lengkap']) || empty($data['username']) || empty($data['password'])) {
        setFlash('error', 'tambah: Nama, username, dan password wajib diisi.');
    } else {
        $result = createUserByAdmin($pdo, $data);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
    }

    header('Location: ' . BASE_URL . '/admin/kelola_user.php');
    exit;
}

$userId = (int)($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    setFlash('error', 'User ID tidak valid.');
    header('Location: ' . BASE_URL . '/admin/kelola_user.php');
    exit;
}

// Prevent self-modification
if ($userId === (int)$_SESSION['user_id']) {
    setFlash('error', 'Tidak bisa mengubah akun sendiri dari halaman ini.');
    header('Location: ' . BASE_URL . '/admin/kelola_user.php');
    exit;
}

switch ($action) {
    case 'toggle_active':
        $result = toggleUserActive($pdo, $userId);
        break;

    case 'change_role':
        $role = $_POST['role'] ?? '';
        $result = updateUserRole($pdo, $userId, $role);
        break;

    case 'reset_password':
        $newPwd = $_POST['new_password'] ?? '';
        $result = resetUserPassword($pdo, $userId, $newPwd);
        break;

    default:
        $result = ['success' => false, 'message' => 'Aksi tidak valid.'];
}

setFlash($result['success'] ? 'success' : 'error', $result['message']);
header('Location: ' . BASE_URL . '/admin/kelola_user.php');
exit;
