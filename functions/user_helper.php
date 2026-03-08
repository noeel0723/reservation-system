<?php
/**
 * User Helper Functions
 * Authentication & User Management
 */

/**
 * Login user
 */
function loginUser(PDO $pdo, string $username, string $password): array
{
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Username atau password salah.'];
    }

    // Set session data
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['username']     = $user['username'];
    $_SESSION['role']         = $user['role'];
    $_SESSION['foto']         = $user['foto'];

    regenerateSession();

    return ['success' => true, 'message' => 'Login berhasil.', 'role' => $user['role']];
}

/**
 * Register user baru (default: Staff)
 */
function registerUser(PDO $pdo, array $data): array
{
    // Cek username unik
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $data['username']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username sudah terdaftar.'];
    }

    // Validasi password
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'message' => 'Password minimal 6 karakter.'];
    }

    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (nama_lengkap, username, password, role, jabatan, no_telp)
            VALUES (:nama, :username, :password, 'Staff', :jabatan, :no_telp)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama'     => $data['nama_lengkap'],
        ':username' => $data['username'],
        ':password' => $hashedPassword,
        ':jabatan'  => $data['jabatan'] ?? null,
        ':no_telp'  => $data['no_telp'] ?? null,
    ]);

    return ['success' => true, 'message' => 'Registrasi berhasil. Silakan login.'];
}

/**
 * Ambil semua users
 */
function getUsers(PDO $pdo, ?string $role = null): array
{
    $sql = "SELECT id, nama_lengkap, username, role, jabatan, no_telp, foto, is_active, created_at FROM users WHERE 1=1";
    $params = [];

    if ($role !== null) {
        $sql .= " AND role = :role";
        $params[':role'] = $role;
    }

    $sql .= " ORDER BY CASE WHEN role = 'Admin' THEN 0 ELSE 1 END, created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Ambil user by ID
 */
function getUserById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, nama_lengkap, username, role, jabatan, no_telp, foto, is_active, created_at FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Update profil user
 */
function updateProfile(PDO $pdo, int $id, array $data): array
{
    $sql = "UPDATE users SET nama_lengkap = :nama, jabatan = :jabatan, no_telp = :no_telp WHERE id = :id";
    $params = [
        ':nama'    => $data['nama_lengkap'],
        ':jabatan' => $data['jabatan'] ?? null,
        ':no_telp' => $data['no_telp'] ?? null,
        ':id'      => $id,
    ];

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Update session
    $_SESSION['nama_lengkap'] = $data['nama_lengkap'];

    return ['success' => true, 'message' => 'Profil berhasil diperbarui.'];
}

/**
 * Ganti password
 */
function changePassword(PDO $pdo, int $id, string $oldPassword, string $newPassword): array
{
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($oldPassword, $user['password'])) {
        return ['success' => false, 'message' => 'Password lama salah.'];
    }

    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Password baru minimal 6 karakter.'];
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->execute([':password' => $hashed, ':id' => $id]);

    return ['success' => true, 'message' => 'Password berhasil diubah.'];
}

/**
 * Toggle status aktif user (Admin only)
 */
function toggleUserActive(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return ['success' => true, 'message' => 'Status user berhasil diubah.'];
}

/**
 * Update role user (Admin only)
 */
function updateUserRole(PDO $pdo, int $id, string $role): array
{
    if (!in_array($role, ['Admin', 'Staff'], true)) {
        return ['success' => false, 'message' => 'Role tidak valid.'];
    }

    $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
    $stmt->execute([':role' => $role, ':id' => $id]);

    return ['success' => true, 'message' => 'Role user berhasil diubah.'];
}

/**
 * Reset password user (Admin only)
 */
function resetUserPassword(PDO $pdo, int $userId, string $newPassword): array
{
    if (strlen($newPassword) < 6) {
        return ['success' => false, 'message' => 'Password minimal 6 karakter.'];
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->execute([':password' => $hashed, ':id' => $userId]);

    return ['success' => true, 'message' => 'Password user berhasil direset.'];
}

/**
 * Buat user baru oleh Admin (bisa set role)
 */
function createUserByAdmin(PDO $pdo, array $data): array
{
    // Validasi username
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $data['username'])) {
        return ['success' => false, 'message' => 'tambah: Username hanya boleh huruf, angka, dan underscore (3-30 karakter).'];
    }

    // Cek username unik
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->execute([':username' => $data['username']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'tambah: Username sudah terdaftar.'];
    }

    if (strlen($data['password']) < 6) {
        return ['success' => false, 'message' => 'tambah: Password minimal 6 karakter.'];
    }

    $role = in_array($data['role'] ?? '', ['Admin', 'Staff'], true) ? $data['role'] : 'Staff';
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (nama_lengkap, username, password, role, jabatan, no_telp)
            VALUES (:nama, :username, :password, :role, :jabatan, :no_telp)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama'     => $data['nama_lengkap'],
        ':username' => $data['username'],
        ':password' => $hashedPassword,
        ':role'     => $role,
        ':jabatan'  => $data['jabatan'] ?? null,
        ':no_telp'  => $data['no_telp'] ?? null,
    ]);

    return ['success' => true, 'message' => 'User <strong>' . htmlspecialchars($data['nama_lengkap']) . '</strong> berhasil ditambahkan.'];
}
