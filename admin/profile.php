<?php
/**
 * Profil Admin
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/user_helper.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireAdmin();

$pageTitle = 'My Profile';
$flashSuccess = getFlash('success');
$flashError = getFlash('error');
$userId = (int)$_SESSION['user_id'];
$userData = getUserById($pdo, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $data = [
            'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
            'jabatan'      => trim($_POST['jabatan'] ?? ''),
            'no_telp'      => trim($_POST['no_telp'] ?? ''),
        ];

        if (empty($data['nama_lengkap'])) {
            $flashError = 'Nama lengkap wajib diisi.';
        } else {
            $result = updateProfile($pdo, $userId, $data);
            if (!empty($result['success']) && $result['success']) {
                $flashSuccess = $result['message'];
                $userData = getUserById($pdo, $userId);
            } else {
                $flashError = $result['message'] ?? 'Gagal memperbarui profil.';
            }
        }
    } elseif ($action === 'change_password') {
        $oldPwd = $_POST['old_password'] ?? '';
        $newPwd = $_POST['new_password'] ?? '';
        $confirmPwd = $_POST['confirm_password'] ?? '';

        if ($newPwd !== $confirmPwd) {
            $flashError = 'Konfirmasi password tidak cocok.';
        } else {
            $result = changePassword($pdo, $userId, $oldPwd, $newPwd);
            if ($result['success']) {
                $flashSuccess = $result['message'];
            } else {
                $flashError = $result['message'];
            }
        }
    }
}

$myReservations = getReservations($pdo, ['user_id' => $userId]);
$statTotal     = count($myReservations);
$statFinished  = count(array_filter($myReservations, fn($r) => in_array($r['status'], ['Finished', 'Selesai'])));
$statCancelled = count(array_filter($myReservations, fn($r) => in_array($r['status'], ['Cancelled', 'Rejected'])));
$activeTab = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') ? 'pass' : 'edit';

$nameParts      = explode(' ', $userData['nama_lengkap'] ?? 'A');
$avatarInitials = strtoupper(mb_substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : ''));

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';
?>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3"><?= htmlspecialchars($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3"><?= htmlspecialchars($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<style>
.st-shell { border:1px solid #e5e7eb; border-radius:20px; background:#fff; overflow:hidden; }
.st-top { padding:14px 16px; border-bottom:1px solid #eef2f7; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.st-tabs { display:flex; gap:10px; padding:0 16px; border-bottom:1px solid #eef2f7; overflow:auto; }
.st-tab-btn { background:none; border:none; border-bottom:2px solid transparent; padding:.85rem .3rem; color:#6b7280; font-size:.82rem; font-weight:600; white-space:nowrap; }
.st-tab-btn.active { color:#111827; border-bottom-color:#111827; }
.st-main { padding:16px; }
.st-card { border:1px solid #eceff3; border-radius:14px; background:#fff; }
.st-avatar { width:92px; height:92px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.8rem; font-weight:800; background:linear-gradient(135deg,#6477d8,#4f46e5); margin:auto; }
.st-mini { font-size:.73rem; color:#6b7280; }
@media (max-width: 767px) {
    .st-top, .st-main { padding:12px; }
    .st-tabs { padding:0 12px; }
}
</style>

<div class="st-shell">
    <div class="st-top">
        <div>
            <h5 class="mb-0 fw-bold">Settings</h5>
            <div class="text-muted" style="font-size:.76rem">Manage your account information and preferences.</div>
        </div>
        <button type="button" class="btn btn-sm" style="background:var(--color-moonstone);color:#fff;border-radius:999px;padding:.45rem 1rem" onclick="document.getElementById('profileFormSubmit').click()">Save all changes</button>
    </div>

    <div class="st-tabs" role="tablist">
        <button class="st-tab-btn <?= $activeTab === 'edit' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#pane-edit" type="button" role="tab">Profile</button>
        <button class="st-tab-btn <?= $activeTab === 'pass' ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#pane-pass" type="button" role="tab">Change Password</button>
    </div>

    <div class="tab-content st-main">
        <div class="tab-pane fade <?= $activeTab === 'edit' ? 'show active' : '' ?>" id="pane-edit" role="tabpanel">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="update_profile">

                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <div class="st-card p-3 text-center h-100">
                            <h6 class="fw-semibold text-start">Profile Picture</h6>
                            <div class="st-avatar my-3"><?= htmlspecialchars($avatarInitials) ?></div>
                            <hr>
                            <div class="row g-2 text-start mt-1">
                                <div class="col-4"><div class="st-mini">Total</div><div class="fw-bold"><?= $statTotal ?></div></div>
                                <div class="col-4"><div class="st-mini">Finished</div><div class="fw-bold text-success"><?= $statFinished ?></div></div>
                                <div class="col-4"><div class="st-mini">Cancelled</div><div class="fw-bold text-danger"><?= $statCancelled ?></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="st-card p-3">
                            <h6 class="fw-semibold mb-3">Basic Information</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small">Full name</label>
                                    <input type="text" class="form-control" name="nama_lengkap" required value="<?= htmlspecialchars($userData['nama_lengkap'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Username</label>
                                    <input type="text" class="form-control bg-light" value="@<?= htmlspecialchars($userData['username'] ?? '') ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Position</label>
                                    <input type="text" class="form-control" name="jabatan" value="<?= htmlspecialchars($userData['jabatan'] ?? '') ?>" placeholder="Position">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Phone number</label>
                                    <input type="text" class="form-control" name="no_telp" value="<?= htmlspecialchars($userData['no_telp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Joined</label>
                                    <input type="text" class="form-control bg-light" value="<?= date('d F Y', strtotime($userData['created_at'])) ?>" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" id="profileFormSubmit" class="d-none">submit</button>
            </form>
        </div>

        <div class="tab-pane fade <?= $activeTab === 'pass' ? 'show active' : '' ?>" id="pane-pass" role="tabpanel">
            <form method="POST" class="st-card p-3" style="max-width:720px">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="change_password">
                <h6 class="fw-semibold mb-3">Change Password</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label small">Current password</label>
                        <input type="password" class="form-control" name="old_password" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">New password</label>
                        <input type="password" class="form-control" name="new_password" required placeholder="Min. 6 karakter">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Confirm password</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button type="submit" class="btn btn-warning px-4"><i class="bi bi-key me-1"></i>Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>