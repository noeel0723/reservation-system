<?php
/**
 * Profil Admin  
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/user_helper.php';

requireAdmin();

$pageTitle = 'Profil Saya';
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
            $flashSuccess = $result['message'];
            $userData = getUserById($pdo, $userId);
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

// Profile stats
require_once __DIR__ . '/../functions/reservation_helper.php';
$myReservations = getReservations($pdo, ['user_id' => $userId]);
$statTotal     = count($myReservations);
$statFinished  = count(array_filter($myReservations, fn($r) => in_array($r['status'], ['Finished', 'Selesai'])));
$statCancelled = count(array_filter($myReservations, fn($r) => in_array($r['status'], ['Cancelled', 'Rejected'])));
$activeTab = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') ? 'pass' : 'edit';

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';
?>



<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4"><?= htmlspecialchars($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4"><?= htmlspecialchars($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php
$nameParts      = explode(' ', $userData['nama_lengkap']);
$avatarInitials = strtoupper(mb_substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : ''));
$avatarBg  = $userData['role'] === 'Admin' ? '#004554' : '#44A6B5';
$roleBg    = $userData['role'] === 'Admin' ? 'rgba(0,69,84,0.1)'   : 'rgba(68,166,181,0.12)';
$roleColor = $userData['role'] === 'Admin' ? '#004554'             : '#44A6B5';
?>

<div class="row g-4">
    <!-- LEFT: Profile card -->
    <div class="col-lg-4 col-md-5">
        <div class="card border-0 shadow-sm p-4 text-center" style="border-radius:16px">
            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle text-white fw-bold"
                 style="width:88px;height:88px;font-size:2rem;background:<?= $avatarBg ?>">
                <?= $avatarInitials ?>
            </div>
            <h5 class="fw-bold mb-1"><?= htmlspecialchars($userData['nama_lengkap']) ?></h5>
            <span class="d-inline-block mb-4 px-3 py-1 rounded-pill"
                  style="background:<?= $roleBg ?>;color:<?= $roleColor ?>;border:1px solid <?= $roleColor ?>33;font-size:0.78rem;font-weight:600;align-self:center;max-width:fit-content">
                <?= $userData['role'] ?>
            </span>
            <!-- Mini stats -->
            <div class="d-flex align-items-center justify-content-center gap-4 py-3 mb-4"
                 style="border-top:1px solid #f0f0f0;border-bottom:1px solid #f0f0f0">
                <div class="text-center">
                    <div class="fw-bold lh-1 mb-1" style="font-size:1.5rem;color:#44A6B5"><?= $statTotal ?></div>
                    <div class="text-muted" style="font-size:0.7rem">Total</div>
                </div>
                <div class="text-center">
                    <div class="fw-bold lh-1 mb-1" style="font-size:1.5rem;color:#22c55e"><?= $statFinished ?></div>
                    <div class="text-muted" style="font-size:0.7rem">Selesai</div>
                </div>
                <div class="text-center">
                    <div class="fw-bold lh-1 mb-1" style="font-size:1.5rem;color:#ef4444"><?= $statCancelled ?></div>
                    <div class="text-muted" style="font-size:0.7rem">Batal</div>
                </div>
            </div>
            <!-- Info blocks -->
            <div class="d-flex flex-column gap-2 text-start">
                <div class="py-2 px-3 rounded-2" style="background:#f9fafb;border:1px solid #f0f0f0">
                    <div class="text-muted" style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.5px">Username</div>
                    <div class="fw-medium text-truncate" style="font-size:0.88rem">@<?= htmlspecialchars($userData['username']) ?></div>
                </div>
                <div class="py-2 px-3 rounded-2" style="background:#f9fafb;border:1px solid #f0f0f0">
                    <div class="text-muted" style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.5px">Jabatan</div>
                    <div class="fw-medium" style="font-size:0.88rem"><?= htmlspecialchars($userData['jabatan'] ?? '—') ?></div>
                </div>
                <div class="py-2 px-3 rounded-2" style="background:#f9fafb;border:1px solid #f0f0f0">
                    <div class="text-muted" style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.5px">No. Telepon</div>
                    <div class="fw-medium" style="font-size:0.88rem"><?= htmlspecialchars($userData['no_telp'] ?? '—') ?></div>
                </div>
                <div class="py-2 px-3 rounded-2" style="background:#f9fafb;border:1px solid #f0f0f0">
                    <div class="text-muted" style="font-size:0.68rem;text-transform:uppercase;letter-spacing:0.5px">Bergabung</div>
                    <div class="fw-medium" style="font-size:0.88rem"><?= date('d F Y', strtotime($userData['created_at'])) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Tabbed forms -->
    <div class="col-lg-8 col-md-7">
        <div class="card border-0 shadow-sm" style="border-radius:16px">
            <div class="border-bottom px-4 pt-1">
                <ul class="nav" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="profile-tab-btn <?= $activeTab === 'edit' ? 'active' : '' ?>"
                                data-bs-toggle="tab" data-bs-target="#pane-edit" type="button" role="tab">
                            <i class="bi bi-person-gear me-1"></i>Edit Profil
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="profile-tab-btn <?= $activeTab === 'pass' ? 'active' : '' ?>"
                                data-bs-toggle="tab" data-bs-target="#pane-pass" type="button" role="tab">
                            <i class="bi bi-shield-lock me-1"></i>Ganti Password
                        </button>
                    </li>
                </ul>
            </div>
            <div class="tab-content p-4">
                <!-- Edit Profil pane -->
                <div class="tab-pane fade <?= $activeTab === 'edit' ? 'show active' : '' ?>" id="pane-edit" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-medium small">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_lengkap" required
                                       value="<?= htmlspecialchars($userData['nama_lengkap']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium small">Username</label>
                                <input type="text" class="form-control bg-light" value="@<?= htmlspecialchars($userData['username']) ?>" disabled>
                                <div class="form-text">Tidak dapat diubah.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium small">Jabatan</label>
                                <input type="text" class="form-control" name="jabatan"
                                       value="<?= htmlspecialchars($userData['jabatan'] ?? '') ?>"
                                       placeholder="cth: Produser">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium small">No. Telepon</label>
                                <input type="text" class="form-control" name="no_telp"
                                       value="<?= htmlspecialchars($userData['no_telp'] ?? '') ?>"
                                       placeholder="08xxxxxxxxxx">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
                <!-- Ganti Password pane -->
                <div class="tab-pane fade <?= $activeTab === 'pass' ? 'show active' : '' ?>" id="pane-pass" role="tabpanel">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="action" value="change_password">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-medium small">Password Lama <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="old_password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium small">Password Baru <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="new_password" required placeholder="Min. 6 karakter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium small">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-warning px-4">
                                <i class="bi bi-key me-1"></i>Ganti Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-tab-btn {
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    padding: 0.75rem 1rem;
    font-size: 0.88rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}
.profile-tab-btn:hover { color: #44A6B5; }
.profile-tab-btn.active { color: #44A6B5; border-bottom-color: #44A6B5; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
