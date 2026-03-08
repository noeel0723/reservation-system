<?php
/**
 * Authentication & Authorization Guard
 * Handles session security and RBAC
 */

/**
 * Start secure session
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => false, // Set true jika pakai HTTPS
            'httponly'  => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

/**
 * Regenerate session ID untuk mencegah session fixation
 */
function regenerateSession(): void
{
    session_regenerate_id(true);
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Ambil data user dari session
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'           => $_SESSION['user_id'],
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? '',
        'username'     => $_SESSION['username'] ?? '',
        'role'         => $_SESSION['role'] ?? '',
        'foto'         => $_SESSION['foto'] ?? null,
    ];
}

/**
 * Guard: Redirect ke login jika belum login
 */
function requireLogin(): void
{
    startSecureSession();
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
        header('Location: /reservasi-sistem/index.php');
        exit;
    }
}

/**
 * Guard: Hanya Admin yang boleh akses
 * Mencegah Staff menembak URL admin secara langsung
 */
function requireAdmin(): void
{
    requireLogin();
    if ($_SESSION['role'] !== 'Admin') {
        $_SESSION['flash_error'] = 'Akses ditolak. Halaman ini khusus Admin.';
        header('Location: /reservasi-sistem/user/dashboard.php');
        exit;
    }
}

/**
 * Guard: Hanya Staff yang boleh akses
 */
function requireStaff(): void
{
    requireLogin();
    if ($_SESSION['role'] !== 'Staff') {
        $_SESSION['flash_error'] = 'Akses ditolak.';
        header('Location: /reservasi-sistem/admin/dashboard.php');
        exit;
    }
}

/**
 * Redirect berdasarkan role setelah login
 */
function redirectByRole(): void
{
    if ($_SESSION['role'] === 'Admin') {
        header('Location: /reservasi-sistem/admin/dashboard.php');
    } else {
        header('Location: /reservasi-sistem/user/dashboard.php');
    }
    exit;
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash_' . $type] = $message;
}

/**
 * Get & clear flash message
 */
function getFlash(string $type): ?string
{
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

/**
 * Validate CSRF token
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token dari form submission
 */
function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF dan redirect jika gagal
 */
function requireCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        setFlash('error', 'Sesi tidak valid. Silakan coba lagi.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/reservasi-sistem/'));
        exit;
    }
}
