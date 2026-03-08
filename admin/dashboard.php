<?php
/**
 * Admin Dashboard
 * Statistik & Overview
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';
require_once __DIR__ . '/../functions/resource_helper.php';

requireAdmin();

// Auto-mark reservasi selesai
autoMarkSelesai($pdo);

// AJAX: ping untuk auto-refresh JS
if (isset($_GET['ping'])) {
    header('Content-Type: application/json');
    echo json_encode(['pending' => (int)$pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Pending'")->fetchColumn()]);
    exit;
}

$pageTitle = 'Dashboard Admin';
$stats = getDashboardStats($pdo);

// Reservasi terbaru (5 terakhir)
$recentReservations = getReservations($pdo);
$recentReservations = array_slice($recentReservations, 0, 5);

// Pending reservations
$pendingReservations = getReservations($pdo, ['status' => 'Pending']);

// Reservasi hari ini (untuk timeline)
$todayReservations = $pdo->query(
    "SELECT r.id, r.waktu_mulai, r.waktu_selesai, r.keperluan, r.status,
            res.nama AS resource_nama, res.tipe AS resource_tipe,
            u.nama_lengkap AS peminjam
     FROM reservations r
     JOIN resources res ON r.resource_id = res.id
     JOIN users u ON r.user_id = u.id
     WHERE DATE(r.waktu_mulai) = CURDATE()
       AND r.status IN ('Approved', 'Pending')
     ORDER BY r.waktu_mulai ASC
     LIMIT 10"
)->fetchAll();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';
?>

<!-- Hero Banner -->
<div class="hero-banner mb-4">
    <div class="row align-items-center g-0">
        <div class="col-7 col-sm-8">
            <div class="hero-content">
                <p class="hero-greeting mb-1">Halo, <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong>!</p>
                <p class="hero-subtitle mb-3">
                    Terdapat <strong><?= $stats['pending'] ?></strong> reservasi menunggu persetujuan
                    dan <strong><?= $stats['active'] ?></strong> reservasi sedang aktif saat ini.
                </p>
                <a href="<?= BASE_URL ?>/admin/kelola_reservasi.php?status=Pending" class="btn hero-btn">
                    <i class="bi bi-hourglass-split me-1"></i>Tinjau Pending
                </a>
            </div>
        </div>
        <div class="col-5 col-sm-4 text-end">
            <div class="hero-logo-wrap">
                <img src="<?= BASE_URL ?>/assets/pictures/Logo_TVRI.svg.png" alt="TVRI" class="hero-logo">
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards - Skillset Style -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-primary shadow-sm">
            <div class="stat-label">Menunggu Persetujuan</div>
            <div class="stat-value"><?= $stats['pending'] ?></div>
            <div class="stat-change up">
                <i class="bi bi-hourglass-split"></i> perlu ditinjau
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-light shadow-sm">
            <div class="stat-label">Reservasi Aktif</div>
            <div class="stat-value"><?= $stats['active'] ?></div>
            <div class="stat-change up">
                <i class="bi bi-arrow-up-short"></i> sedang berjalan
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-light shadow-sm">
            <div class="stat-label">Resource Tersedia</div>
            <div class="stat-value"><?= $stats['resources'] ?></div>
            <div class="stat-change up">
                <i class="bi bi-hdd-stack"></i> studio & alat
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card stat-light shadow-sm">
            <div class="stat-label">User Terdaftar</div>
            <div class="stat-value"><?= $stats['users'] ?></div>
            <div class="stat-change up">
                <i class="bi bi-people"></i> total pengguna
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Pending Reservations -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-hourglass-split me-2" style="color: #fbbf24"></i>Menunggu Persetujuan</h6>
                <a href="<?= BASE_URL ?>/admin/kelola_reservasi.php?status=Pending" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingReservations)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                        Tidak ada reservasi yang menunggu persetujuan.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Peminjam</th>
                                    <th>Resource</th>
                                    <th>Waktu</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($pendingReservations, 0, 5) as $r): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($r['peminjam']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($r['jabatan'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $r['resource_tipe'] === 'Studio' ? 'bg-primary' : 'bg-info' ?>">
                                            <?= htmlspecialchars($r['resource_tipe']) ?>
                                        </span>
                                        <?= htmlspecialchars($r['resource_nama']) ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?= date('d/m/Y H:i', strtotime($r['waktu_mulai'])) ?>
                                            <br>s/d <?= date('d/m/Y H:i', strtotime($r['waktu_selesai'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center flex-nowrap">
                                            <form method="POST" action="<?= BASE_URL ?>/admin/proses_reservasi.php" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                                <input type="hidden" name="action" value="Approved">
                                                <input type="hidden" name="redirect" value="dashboard">
                                                <button type="submit" class="btn btn-success btn-sm py-0 px-2" title="Approve"
                                                        onclick="return confirm('Approve reservasi ini?')">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm py-0 px-2" title="Tolak"
                                                    data-bs-toggle="modal" data-bs-target="#dashRejectModal"
                                                    data-id="<?= $r['id'] ?>">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>/admin/kelola_reservasi.php?detail=<?= $r['id'] ?>"
                                               class="btn btn-outline-secondary btn-sm py-0 px-2" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Today's Timeline -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-calendar-day me-2" style="color:var(--color-moonstone)"></i>Jadwal Hari Ini</h6>
                <small class="text-muted"><?= date('d M Y') ?></small>
            </div>
            <div class="card-body p-0" style="max-height:420px;overflow-y:auto">
                <?php if (empty($todayReservations)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-check d-block mb-2" style="font-size:2rem;opacity:0.25"></i>
                        <p class="small mb-0">Tidak ada jadwal hari ini</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($todayReservations as $t): ?>
                    <div class="d-flex align-items-start gap-3 px-3 py-3 border-bottom">
                        <div class="text-center flex-shrink-0" style="min-width:46px">
                            <div class="fw-bold" style="font-size:0.75rem;color:var(--color-midnight-green)"><?= date('H:i', strtotime($t['waktu_mulai'])) ?></div>
                            <div style="font-size:0.65rem;color:#ccc;line-height:1.2">&boxv;</div>
                            <div style="font-size:0.73rem;color:#888"><?= date('H:i', strtotime($t['waktu_selesai'])) ?></div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold text-truncate" style="font-size:0.83rem"><?= htmlspecialchars($t['resource_nama']) ?></div>
                            <div class="text-muted text-truncate" style="font-size:0.75rem"><?= htmlspecialchars($t['peminjam']) ?></div>
                            <span class="res-badge <?= $t['status'] === 'Approved' ? 'rb-approved' : 'rb-pending' ?>" style="font-size:0.68rem;padding:0.25em 0.6em">
                                <i class="bi <?= $t['status'] === 'Approved' ? 'bi-check-circle' : 'bi-hourglass-split' ?> res-badge-icon"></i><?= $t['status'] ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Reject Modal -->
<div class="modal fade" id="dashRejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/admin/proses_reservasi.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="reservation_id" id="dashRejectId">
                <input type="hidden" name="action" value="Rejected">
                <input type="hidden" name="redirect" value="dashboard">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-x-circle text-danger me-2"></i>Tolak Reservasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-medium small">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="catatan" rows="3" placeholder="Berikan alasan penolakan..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Tolak Reservasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Pending Toast -->
<div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
    <div id="newPendingToast" class="toast align-items-center border-0" style="background:#fbbf24;color:#1a2332" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-medium">
                <i class="bi bi-bell-fill me-2"></i>Ada <span id="toastPendingCount">0</span> reservasi baru menunggu persetujuan!
            </div>
            <button type="button" class="btn-close btn-close-dark ms-auto me-2" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
// Reject modal: isi reservation_id dari tombol
document.querySelectorAll('[data-bs-target="#dashRejectModal"]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('dashRejectId').value = this.dataset.id;
    });
});

// Auto-refresh polling: cek pending baru setiap 60 detik
var _pendingCount = <?= (int)$stats['pending'] ?>;
document.addEventListener('DOMContentLoaded', function() {
    var _toastEl = document.getElementById('newPendingToast');
    var _toast = new bootstrap.Toast(_toastEl, {delay: 6000});
    setInterval(function() {
        fetch('<?= BASE_URL ?>/admin/dashboard.php?ping=1')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.pending > _pendingCount) {
                    _pendingCount = data.pending;
                    document.getElementById('toastPendingCount').textContent = data.pending;
                    _toast.show();
                }
            })
            .catch(function() {});
    }, 60000);
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
