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

$statusPendingCount  = count(array_filter($myReservations, fn($r) => $r['status'] === 'Pending'));
$statusApprovedCount = count(array_filter($myReservations, fn($r) => in_array($r['status'], ['Approved', 'Selesai'], true)));
$statusRejectedCount = count(array_filter($myReservations, fn($r) => $r['status'] === 'Rejected'));

$statusTotal = max(1, $statusPendingCount + $statusApprovedCount + $statusRejectedCount);
$pendingPercent  = (int)round(($statusPendingCount / $statusTotal) * 100);
$approvedPercent = (int)round(($statusApprovedCount / $statusTotal) * 100);
$rejectedPercent = (int)round(($statusRejectedCount / $statusTotal) * 100);

$nextReservation = null;
$nowTs = time();
foreach ($myReservations as $reservation) {
    if (!in_array($reservation['status'], ['Pending', 'Approved'], true)) {
        continue;
    }

    $startTs = strtotime($reservation['waktu_mulai']);
    if ($startTs === false || $startTs < $nowTs) {
        continue;
    }

    if ($nextReservation === null || $startTs < strtotime($nextReservation['waktu_mulai'])) {
        $nextReservation = $reservation;
    }
}

$successBookingCount = count(array_filter($myReservations, fn($r) => in_array($r['status'], ['Approved', 'Selesai'], true)));
$decisionCount = count(array_filter($myReservations, fn($r) => in_array($r['status'], ['Approved', 'Selesai', 'Rejected'], true)));
$approvalRatio = $decisionCount > 0 ? round(($successBookingCount / $decisionCount) * 100, 1) : 0;

$durationMinutes = 0;
$durationSamples = 0;
foreach ($myReservations as $reservation) {
    if (!in_array($reservation['status'], ['Approved', 'Selesai'], true)) {
        continue;
    }

    $startTs = strtotime($reservation['waktu_mulai']);
    $endTs = strtotime($reservation['waktu_selesai']);
    if ($startTs === false || $endTs === false || $endTs <= $startTs) {
        continue;
    }

    $durationMinutes += (int)(($endTs - $startTs) / 60);
    $durationSamples++;
}

$averageSessionMinutes = $durationSamples > 0 ? (int)round($durationMinutes / $durationSamples) : 0;
$avgHours = intdiv($averageSessionMinutes, 60);
$avgMinutes = $averageSessionMinutes % 60;
$averageSessionText = $averageSessionMinutes > 0
    ? sprintf('%dh %02dm', $avgHours, $avgMinutes)
    : 'No data';

// Reservasi terbaru
$recentReservations = array_slice($myReservations, 0, 5);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_user.php';
?>

<style>
    .widget-card {
        border: 1px solid #e7ecef;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(2, 34, 42, 0.06);
    }
    .widget-card .card-header {
        background: #fff;
        border-bottom: 1px solid #edf1f4;
    }
    .tracker-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.8rem;
    }
    .tracker-meta {
        min-width: 82px;
        text-align: right;
        font-size: 0.78rem;
        color: #5c7280;
        font-weight: 600;
    }
    .tracker-progress {
        height: 10px;
        border-radius: 999px;
        background: #eef3f5;
        overflow: hidden;
    }
    .tracker-progress .progress-bar {
        border-radius: 999px;
    }
    .next-countdown {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.6rem;
        margin-top: 1rem;
    }
    .count-item {
        border-radius: 12px;
        border: 1px solid #e4ebef;
        background: #f8fbfc;
        text-align: center;
        padding: 0.55rem 0.35rem;
    }
    .count-value {
        font-weight: 800;
        color: #004554;
        font-size: 1.05rem;
        line-height: 1;
    }
    .count-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #607b89;
        margin-top: 0.15rem;
    }
    .snapshot-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.7rem;
    }
    .snapshot-item {
        border-radius: 12px;
        border: 1px solid #e4ebef;
        background: #fbfdfe;
        padding: 0.75rem 0.7rem;
    }
    .snapshot-label {
        font-size: 0.74rem;
        color: #627a89;
        margin-bottom: 0.35rem;
    }
    .snapshot-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: #103746;
        line-height: 1.1;
    }
    @media (max-width: 767.98px) {
        .snapshot-grid {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 575.98px) {
        .next-countdown {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

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
                    <i class="bi bi-plus-lg me-1"></i>New Reservation
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

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card widget-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-pie-chart me-2"></i>Approval Status Tracker</h6>
                <small class="text-muted">Total: <?= $statusPendingCount + $statusApprovedCount + $statusRejectedCount ?></small>
            </div>
            <div class="card-body">
                <div class="tracker-row">
                    <div>
                        <div class="fw-semibold">Pending</div>
                        <small class="text-muted"><?= $statusPendingCount ?> reservations</small>
                    </div>
                    <div class="tracker-meta"><?= $pendingPercent ?>%</div>
                </div>
                <div class="tracker-progress mb-3">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $pendingPercent ?>%"></div>
                </div>

                <div class="tracker-row">
                    <div>
                        <div class="fw-semibold">Approved</div>
                        <small class="text-muted"><?= $statusApprovedCount ?> reservations</small>
                    </div>
                    <div class="tracker-meta"><?= $approvedPercent ?>%</div>
                </div>
                <div class="tracker-progress mb-3">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $approvedPercent ?>%"></div>
                </div>

                <div class="tracker-row">
                    <div>
                        <div class="fw-semibold">Rejected</div>
                        <small class="text-muted"><?= $statusRejectedCount ?> reservations</small>
                    </div>
                    <div class="tracker-meta"><?= $rejectedPercent ?>%</div>
                </div>
                <div class="tracker-progress">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $rejectedPercent ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card widget-card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-alarm me-2"></i>My Next Reservation</h6>
                <?php if ($nextReservation): ?>
                    <span class="badge rounded-pill text-bg-light border"><?= htmlspecialchars($nextReservation['status']) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$nextReservation): ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-calendar2-x fs-4 d-block mb-2"></i>
                        No upcoming reservation.
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold fs-6"><?= htmlspecialchars($nextReservation['resource_nama']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($nextReservation['keperluan']) ?></div>
                        </div>
                        <div class="text-lg-end text-muted small">
                            <div><?= date('d M Y', strtotime($nextReservation['waktu_mulai'])) ?></div>
                            <div><?= date('H:i', strtotime($nextReservation['waktu_mulai'])) ?> - <?= date('H:i', strtotime($nextReservation['waktu_selesai'])) ?></div>
                        </div>
                    </div>

                    <div class="next-countdown" data-countdown-target="<?= date('c', strtotime($nextReservation['waktu_mulai'])) ?>">
                        <div class="count-item">
                            <div class="count-value" data-unit="days">0</div>
                            <div class="count-label">Days</div>
                        </div>
                        <div class="count-item">
                            <div class="count-value" data-unit="hours">0</div>
                            <div class="count-label">Hours</div>
                        </div>
                        <div class="count-item">
                            <div class="count-value" data-unit="minutes">0</div>
                            <div class="count-label">Minutes</div>
                        </div>
                        <div class="count-item">
                            <div class="count-value" data-unit="seconds">0</div>
                            <div class="count-label">Seconds</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card widget-card mb-4">
    <div class="card-header">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-graph-up-arrow me-2"></i>Personal Productivity Snapshot</h6>
    </div>
    <div class="card-body">
        <div class="snapshot-grid">
            <div class="snapshot-item">
                <div class="snapshot-label">Total Successful Bookings</div>
                <div class="snapshot-value"><?= $successBookingCount ?></div>
            </div>
            <div class="snapshot-item">
                <div class="snapshot-label">Approval Ratio</div>
                <div class="snapshot-value"><?= number_format((float)$approvalRatio, 1) ?>%</div>
            </div>
            <div class="snapshot-item">
                <div class="snapshot-label">Average Session Duration</div>
                <div class="snapshot-value"><?= htmlspecialchars($averageSessionText) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Reservations -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Reservasi Terbaru</h6>
        <div class="d-flex gap-2">
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

<script>
    (function () {
        const countdownRoot = document.querySelector('.next-countdown');
        if (!countdownRoot) {
            return;
        }

        const targetAttr = countdownRoot.getAttribute('data-countdown-target');
        if (!targetAttr) {
            return;
        }

        const targetTime = new Date(targetAttr).getTime();
        if (Number.isNaN(targetTime)) {
            return;
        }

        const setUnit = function (unit, value) {
            const el = countdownRoot.querySelector('[data-unit="' + unit + '"]');
            if (el) {
                el.textContent = String(value);
            }
        };

        const tick = function () {
            const now = Date.now();
            let diff = Math.max(0, targetTime - now);

            const days = Math.floor(diff / 86400000);
            diff -= days * 86400000;
            const hours = Math.floor(diff / 3600000);
            diff -= hours * 3600000;
            const minutes = Math.floor(diff / 60000);
            diff -= minutes * 60000;
            const seconds = Math.floor(diff / 1000);

            setUnit('days', days);
            setUnit('hours', String(hours).padStart(2, '0'));
            setUnit('minutes', String(minutes).padStart(2, '0'));
            setUnit('seconds', String(seconds).padStart(2, '0'));
        };

        tick();
        window.setInterval(tick, 1000);
    })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
