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

<style>
.um-shell { border:1px solid #e5e7eb; border-radius:18px; background:#fff; overflow:hidden; }
.um-head { padding:14px 16px; border-bottom:1px solid #f1f3f5; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.um-toolbar { padding:12px 16px; border-bottom:1px solid #f1f3f5; background:#fcfcfd; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.um-filter { border:1px solid #e5e7eb; border-radius:999px; padding:6px 12px; font-size:.76rem; font-weight:600; color:#4b5563; text-decoration:none; background:#fff; }
.um-filter.active { background:#3b82f6; color:#fff; border-color:#3b82f6; }
.um-count { border:1px solid #dbeafe; background:#eff6ff; color:#1d4ed8; font-size:.72rem; padding:4px 10px; border-radius:999px; font-weight:700; }
.um-card { border:1px solid #e5e7eb; border-radius:16px; background:#fff; padding:14px; height:100%; transition:.18s; }
.um-card:hover { transform:translateY(-2px); box-shadow:0 10px 28px rgba(15,23,42,.08); }
.um-avatar { width:46px; height:46px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:.8rem; }
.um-name { font-size:.93rem; font-weight:700; color:#1f2937; }
.um-meta { font-size:.74rem; color:#6b7280; }
.um-chip { display:inline-flex; align-items:center; gap:5px; border-radius:999px; padding:4px 10px; font-size:.7rem; font-weight:700; }
.um-chip.role-admin { background:#ecfeff; color:#0e7490; border:1px solid #a5f3fc; }
.um-chip.role-staff { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.um-chip.status-on { background:#ecfdf3; color:#047857; border:1px solid #a7f3d0; }
.um-chip.status-off { background:#f3f4f6; color:#6b7280; border:1px solid #e5e7eb; }
.um-tags { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin:10px 0 12px; }
.um-actions { display:flex; gap:8px; flex-wrap:wrap; }
.um-actions .btn { border-radius:10px; font-size:.74rem; font-weight:600; }
@media (max-width: 575px) {
    .um-head, .um-toolbar { padding:12px; }
}
</style>

<div class="um-shell">
    <div class="um-head">
        <div>
            <h5 class="mb-0 fw-bold">Users</h5>
            <div class="text-muted" style="font-size:.76rem">Manage account roles, status, and credentials.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="um-count" id="userCount"><?= count($users) ?> users</span>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                <i class="bi bi-person-plus me-1"></i>Add User
            </button>
        </div>
    </div>

    <div class="um-toolbar">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="?role=" class="um-filter <?= !$filterRole ? 'active' : '' ?>">All</a>
            <a href="?role=Admin" class="um-filter <?= $filterRole === 'Admin' ? 'active' : '' ?>">Admin</a>
            <a href="?role=Staff" class="um-filter <?= $filterRole === 'Staff' ? 'active' : '' ?>">Staff</a>
        </div>
        <div class="input-group input-group-sm" style="max-width:260px">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted" style="font-size:.8rem"></i></span>
            <input type="text" id="userSearch" class="form-control border-start-0" placeholder="Search by name or username...">
        </div>
    </div>

    <div class="p-3 p-md-4">
        <div class="row g-3" id="userCardsGrid">
            <?php if (empty($users)): ?>
                <div class="col-12" id="userEmptyState">
                    <div class="text-center py-5 text-muted">No users found.</div>
                </div>
            <?php endif; ?>

            <?php foreach ($users as $u):
                $nameParts = explode(' ', $u['nama_lengkap']);
                $initials  = strtoupper(mb_substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : ''));
                $avatarBg  = $u['role'] === 'Admin' ? '#0284c7' : '#3b82f6';
                $isSelf    = (int)$u['id'] === (int)$_SESSION['user_id'];
            ?>
            <div class="col-12 col-md-6 col-xl-4 user-row" data-name="<?= strtolower(htmlspecialchars($u['nama_lengkap'] . ' ' . $u['username'])) ?>">
                <div class="um-card">
                    <div class="d-flex align-items-start gap-3">
                        <div class="um-avatar" style="background:<?= $avatarBg ?>"><?= $initials ?></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="um-name text-truncate"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                <?php if ($isSelf): ?><span class="badge text-bg-light border">You</span><?php endif; ?>
                            </div>
                            <div class="um-meta">@<?= htmlspecialchars($u['username']) ?><?= $u['jabatan'] ? ' · ' . htmlspecialchars($u['jabatan']) : '' ?></div>
                        </div>
                    </div>

                    <div class="um-tags">
                        <span class="um-chip <?= $u['role'] === 'Admin' ? 'role-admin' : 'role-staff' ?>"><i class="bi bi-person-badge"></i><?= htmlspecialchars($u['role']) ?></span>
                        <span class="um-chip <?= $u['is_active'] ? 'status-on' : 'status-off' ?>"><i class="bi bi-circle-fill" style="font-size:.45rem"></i><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </div>

                    <div class="um-meta mb-3"><i class="bi bi-calendar3 me-1"></i>Joined <?= date('d M Y', strtotime($u['created_at'])) ?></div>

                    <?php if (!$isSelf): ?>
                    <div class="um-actions">
                        <form method="POST" action="<?= BASE_URL ?>/admin/proses_user.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn btn-sm"
                                    onclick="return confirm('<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?> user ini?')"
                                    style="background:<?= $u['is_active'] ? '#fffbeb' : '#ecfdf3' ?>;color:<?= $u['is_active'] ? '#b45309' : '#047857' ?>;border:1px solid <?= $u['is_active'] ? '#fde68a' : '#a7f3d0' ?>">
                                <i class="bi bi-<?= $u['is_active'] ? 'pause-circle' : 'play-circle' ?> me-1"></i><?= $u['is_active'] ? 'Pause' : 'Activate' ?>
                            </button>
                        </form>

                        <form method="POST" action="<?= BASE_URL ?>/admin/proses_user.php" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="role" value="<?= $u['role'] === 'Admin' ? 'Staff' : 'Admin' ?>">
                            <button type="submit" class="btn btn-sm"
                                    onclick="return confirm('Ubah role menjadi <?= $u['role'] === 'Admin' ? 'Staff' : 'Admin' ?>?')"
                                    style="background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe">
                                <i class="bi bi-arrow-repeat me-1"></i>Switch Role
                            </button>
                        </form>

                        <button type="button" class="btn btn-sm"
                                data-bs-toggle="modal" data-bs-target="#resetPassModal"
                                data-id="<?= $u['id'] ?>" data-nama="<?= htmlspecialchars($u['nama_lengkap']) ?>"
                                style="background:#f9fafb;color:#374151;border:1px solid #e5e7eb">
                            <i class="bi bi-key me-1"></i>Reset Password
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="col-12 d-none" id="userSearchEmpty">
                <div class="text-center py-5 text-muted">No matching users.</div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('userSearch').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    var visible = 0;
    document.querySelectorAll('.user-row').forEach(function(row) {
        var match = (!q || row.dataset.name.includes(q));
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    var cnt = document.getElementById('userCount');
    if (cnt) cnt.textContent = visible + ' users';
    var emptyEl = document.getElementById('userSearchEmpty');
    if (emptyEl) emptyEl.classList.toggle('d-none', visible > 0);
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
