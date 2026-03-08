<?php
/**
 * Staff Dashboard
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireStaff();

$pageTitle = 'Dashboard';
$userId = (int)$_SESSION['user_id'];

// Statistik user
$myReservations = getReservations($pdo, ['user_id' => $userId]);
$myPending  = array_filter($myReservations, fn($r) => $r['status'] === 'Pending');
$myApproved = array_filter($myReservations, fn($r) => $r['status'] === 'Approved' && strtotime($r['waktu_selesai']) > time());
$myTotal    = count($myReservations);

// Reservasi terbaru
$recentReservations = array_slice($myReservations, 0, 5);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_user.php';
?>

<!-- Hero Banner -->
<div class="hero-banner mb-4">
    <div class="row align-items-center g-0">
        <div class="col-7 col-sm-8">
            <div class="hero-content">
                <p class="hero-greeting mb-1">Halo, <strong><?= htmlspecialchars($user['nama_lengkap']) ?></strong>!</p>
                <p class="hero-subtitle mb-3">
                    Anda memiliki
                    <strong><?= count($myPending) ?></strong> reservasi menunggu persetujuan
                    dan <strong><?= count($myApproved) ?></strong> reservasi aktif hari ini.
                </p>
                <a href="<?= BASE_URL ?>/user/reservasi_baru.php" class="btn hero-btn">
                    <i class="bi bi-plus-lg me-1"></i>Reservasi Baru
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

<!-- Stats - Skillset Style -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card stat-primary shadow-sm">
            <div class="stat-label">Menunggu Persetujuan</div>
            <div class="stat-value"><?= count($myPending) ?></div>
            <div class="stat-change up">
                <i class="bi bi-hourglass-split"></i> perlu ditinjau admin
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-light shadow-sm">
            <div class="stat-label">Reservasi Aktif</div>
            <div class="stat-value"><?= count($myApproved) ?></div>
            <div class="stat-change up">
                <i class="bi bi-calendar-check"></i> sedang berjalan
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-light shadow-sm">
            <div class="stat-label">Total Reservasi</div>
            <div class="stat-value"><?= $myTotal ?></div>
            <div class="stat-change up">
                <i class="bi bi-calendar3"></i> seluruh riwayat
            </div>
        </div>
    </div>
</div>

<!-- Recent Reservations -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Reservasi Terbaru</h6>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/user/calendar.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-calendar3 me-1"></i><span class="d-none d-sm-inline">Kalender</span>
            </a>
            <a href="<?= BASE_URL ?>/user/riwayat.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Lihat Semua</a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentReservations)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                Belum ada reservasi. <a href="<?= BASE_URL ?>/user/reservasi_baru.php">Buat sekarang!</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Resource</th>
                            <th>Keperluan</th>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentReservations as $r): ?>
                        <tr>
                            <td>
                                <span class="badge <?= $r['resource_tipe'] === 'Studio' ? 'bg-primary' : 'bg-info' ?> me-1"><?= $r['resource_tipe'] ?></span>
                                <?= htmlspecialchars($r['resource_nama']) ?>
                            </td>
                            <td><?= htmlspecialchars(mb_strimwidth($r['keperluan'], 0, 40, '…')) ?></td>
                            <td>
                                <small>
                                    <?= date('d/m/Y', strtotime($r['waktu_mulai'])) ?>
                                    <br><?= date('H:i', strtotime($r['waktu_mulai'])) ?>–<?= date('H:i', strtotime($r['waktu_selesai'])) ?>
                                </small>
                            </td>
                            <td>
                                <?php
                                $_ds  = $r['status'];
                                $_dl  = $_ds === 'Selesai' ? 'Finished' : $_ds;
                                $_dc  = match($_ds) {
                                    'Pending'   => 'rb-pending',
                                    'Approved'  => 'rb-approved',
                                    'Rejected'  => 'rb-rejected',
                                    'Cancelled' => 'rb-cancelled',
                                    'Selesai'   => 'rb-finished',
                                    default     => 'rb-cancelled',
                                };
                                $_di  = match($_ds) {
                                    'Pending'   => 'bi-hourglass-split',
                                    'Approved'  => 'bi-check-circle',
                                    'Rejected'  => 'bi-x-circle',
                                    'Cancelled' => 'bi-slash-circle',
                                    'Selesai'   => 'bi-check2-all',
                                    default     => 'bi-circle',
                                };
                                ?>
                                <span class="res-badge <?= $_dc ?>">
                                    <i class="bi <?= $_di ?> res-badge-icon"></i><?= $_dl ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'Pending'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="d-inline"
                                          onsubmit="return confirm('Batalkan reservasi ini?')">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
