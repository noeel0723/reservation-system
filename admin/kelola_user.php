<?php
/**
 * Admin - Kelola User
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/user_helper.php';

requireAdmin();

$pageTitle = 'User Management';
$flashSuccess = getFlash('success');
$flashError = getFlash('error');

$filterRole = $_GET['role'] ?? '';
$users = getUsers($pdo, $filterRole ?: null);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';
?>



<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><?= $flashSuccess ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><?= htmlspecialchars(preg_replace('/^tambah: /', '', $flashError)) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Toolbar: filter pills + search + add button -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="d-flex" style="background:#f3f4f6;border-radius:8px;padding:3px">
            <a href="?role=" class="btn btn-sm px-3 <?= !$filterRole ? 'btn-dark text-white' : 'bg-transparent text-muted border-0' ?>" style="border-radius:6px;font-size:0.78rem">Semua</a>
            <a href="?role=Admin" class="btn btn-sm px-3 <?= $filterRole === 'Admin' ? 'btn-dark text-white' : 'bg-transparent text-muted border-0' ?>" style="border-radius:6px;font-size:0.78rem">Admin</a>
            <a href="?role=Staff" class="btn btn-sm px-3 <?= $filterRole === 'Staff' ? 'btn-dark text-white' : 'bg-transparent text-muted border-0' ?>" style="border-radius:6px;font-size:0.78rem">Staff</a>
        </div>
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3"
                data-bs-toggle="modal" data-bs-target="#modalTambahUser">
            <i class="bi bi-person-plus me-1"></i><span class="d-none d-sm-inline">Tambah User</span><span class="d-sm-none">+User</span>
        </button>
    </div>
    <div class="input-group input-group-sm" style="max-width:220px">
        <span class="input-group-text bg-white border-end-0 pe-1"><i class="bi bi-search text-muted" style="font-size:0.8rem"></i></span>
        <input type="text" id="userSearch" class="form-control border-start-0 ps-1" placeholder="Cari nama / username...">
    </div>
</div>

<!-- User Table -->
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="userTable">
            <thead>
                <tr style="background:#f9fafb;border-bottom:1px solid #e5e7eb">
                    <th class="ps-4 py-3" style="font-size:0.78rem;color:#6b7280;font-weight:600;width:40%">Nama User</th>
                    <th class="py-3" style="font-size:0.78rem;color:#6b7280;font-weight:600">Role</th>
                    <th class="py-3" style="font-size:0.78rem;color:#6b7280;font-weight:600">Status</th>
                    <th class="py-3" style="font-size:0.78rem;color:#6b7280;font-weight:600">Terdaftar</th>
                    <th class="py-3 pe-4 text-end" style="font-size:0.78rem;color:#6b7280;font-weight:600">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">Tidak ada user ditemukan.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u):
                    $nameParts = explode(' ', $u['nama_lengkap']);
                    $initials  = strtoupper(mb_substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : ''));
                    $avatarBg  = $u['role'] === 'Admin' ? '#004554' : '#44A6B5';
                    $isSelf    = (int)$u['id'] === (int)$_SESSION['user_id'];
                ?>
                <tr class="user-row" data-name="<?= strtolower(htmlspecialchars($u['nama_lengkap'] . ' ' . $u['username'])) ?>">
                    <!-- Name + username -->
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center text-white fw-bold rounded-circle"
                                 style="width:38px;height:38px;background:<?= $avatarBg ?>;font-size:0.78rem">
                                <?= $initials ?>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size:0.88rem">
                                    <?= htmlspecialchars($u['nama_lengkap']) ?>
                                    <?php if ($isSelf): ?><span class="badge bg-light text-muted ms-1" style="font-size:0.65rem;border:1px solid #dee2e6">Anda</span><?php endif; ?>
                                </div>
                                <div class="text-muted" style="font-size:0.76rem">@<?= htmlspecialchars($u['username']) ?><?= $u['jabatan'] ? ' · ' . htmlspecialchars($u['jabatan']) : '' ?></div>
                            </div>
                        </div>
                    </td>
                    <!-- Role -->
                    <td class="py-3">
                        <span class="badge rounded-pill px-2 py-1"
                              style="font-size:0.72rem;background:<?= $u['role'] === 'Admin' ? 'rgba(0,69,84,0.1)' : 'rgba(68,166,181,0.12)' ?>;color:<?= $u['role'] === 'Admin' ? '#004554' : '#44A6B5' ?>;border:1px solid <?= $u['role'] === 'Admin' ? '#004554' : '#44A6B5' ?>33">
                            <?= $u['role'] ?>
                        </span>
                    </td>
                    <!-- Status -->
                    <td class="py-3">
                        <span class="d-inline-flex align-items-center gap-1" style="font-size:0.78rem;color:<?= $u['is_active'] ? '#16a34a' : '#9ca3af' ?>">
                            <span style="font-size:0.5rem">●</span><?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </td>
                    <!-- Date -->
                    <td class="py-3 text-muted" style="font-size:0.78rem"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <!-- Actions -->
                    <td class="py-3 pe-4 text-end">
                        <?php if (!$isSelf): ?>
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                <!-- Toggle Active -->
                                <form method="POST" action="<?= BASE_URL ?>/admin/proses_user.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm" title="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                            onclick="return confirm('<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> user ini?')"
                                            style="background:<?= $u['is_active'] ? '#fff8e1' : '#f0fdf4' ?>;color:<?= $u['is_active'] ? '#b45309' : '#15803d' ?>;border:1px solid <?= $u['is_active'] ? '#fde68a' : '#bbf7d0' ?>;border-radius:6px;width:30px;height:30px;padding:0">
                                        <i class="bi bi-<?= $u['is_active'] ? 'pause-circle' : 'play-circle' ?>" style="font-size:0.8rem"></i>
                                    </button>
                                </form>
                                <!-- Toggle Role -->
                                <form method="POST" action="<?= BASE_URL ?>/admin/proses_user.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="role" value="<?= $u['role'] === 'Admin' ? 'Staff' : 'Admin' ?>">
                                    <button type="submit" class="btn btn-sm" title="Jadikan <?= $u['role'] === 'Admin' ? 'Staff' : 'Admin' ?>"
                                            onclick="return confirm('Ubah role menjadi <?= $u['role'] === 'Admin' ? 'Staff' : 'Admin' ?>?')"
                                            style="background:#f0f9fb;color:#44A6B5;border:1px solid #cce8ed;border-radius:6px;width:30px;height:30px;padding:0">
                                        <i class="bi bi-arrow-repeat" style="font-size:0.8rem"></i>
                                    </button>
                                </form>
                                <!-- Reset Password -->
                                <button type="button" class="btn btn-sm" title="Reset Password"
                                        data-bs-toggle="modal" data-bs-target="#resetPassModal"
                                        data-id="<?= $u['id'] ?>" data-nama="<?= htmlspecialchars($u['nama_lengkap']) ?>"
                                        style="background:#f8f9fa;color:#6c757d;border:1px solid #dee2e6;border-radius:6px;width:30px;height:30px;padding:0">
                                    <i class="bi bi-key" style="font-size:0.8rem"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('userSearch').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.user-row').forEach(function(row) {
        row.style.display = (!q || row.dataset.name.includes(q)) ? '' : 'none';
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<!-- Modal Tambah User -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-labelledby="modalTambahUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/admin/proses_user.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="create_user">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold" id="modalTambahUserLabel">
                        <i class="bi bi-person-plus me-2" style="color:var(--color-moonstone)"></i>Tambah User Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-medium small">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="nama_lengkap" required
                                   placeholder="cth: Budi Santoso">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="username" required
                                   placeholder="cth: budi_santoso"
                                   pattern="[a-zA-Z0-9_]{3,30}" title="Huruf, angka, underscore (3-30 karakter)">
                            <div class="form-text">Huruf, angka, underscore (3-30 karakter)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Role <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="role" required>
                                <option value="Staff" selected>Staff</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <input type="password" class="form-control form-control-sm" name="password" id="newUserPass" required
                                       placeholder="Min. 6 karakter">
                                <button class="btn btn-outline-secondary" type="button" id="toggleNewPass">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">Jabatan</label>
                            <input type="text" class="form-control form-control-sm" name="jabatan"
                                   placeholder="cth: Produser">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium small">No. Telepon</label>
                            <input type="text" class="form-control form-control-sm" name="no_telp"
                                   placeholder="08xxxxxxxxxx">
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4">
                        <i class="bi bi-person-check me-1"></i>Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Reset Password -->
<div class="modal fade" id="resetPassModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/admin/proses_user.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="resetPassUserId">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-key me-2" style="color:var(--color-moonstone)"></i>Reset Password: <span></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-medium small">Password Baru <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="password" class="form-control form-control-sm" name="new_password" id="resetPassInput"
                               placeholder="Min. 6 karakter" required minlength="6">
                        <button class="btn btn-outline-secondary" type="button" id="toggleResetPass">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Password lama akan digantikan dengan password baru ini.</div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4">
                        <i class="bi bi-check-circle me-1"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle password visibility in modal
document.getElementById('toggleNewPass').addEventListener('click', function() {
    var inp = document.getElementById('newUserPass');
    var icon = this.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
// Reset Password modal: populate userId + name
document.getElementById('resetPassModal').addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('resetPassUserId').value = btn.dataset.id;
    this.querySelector('.modal-title span').textContent = btn.dataset.nama;
    document.getElementById('resetPassInput').value = '';
});
// Toggle password visibility for reset modal
document.getElementById('toggleResetPass').addEventListener('click', function() {
    var inp = document.getElementById('resetPassInput');
    var icon = this.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
<?php if ($flashError && strpos($flashError, 'tambah') !== false): ?>
// Re-open modal if there was a creation error
document.addEventListener('DOMContentLoaded', function() {
    var modal = new bootstrap.Modal(document.getElementById('modalTambahUser'));
    modal.show();
});
<?php endif; ?>
</script>
