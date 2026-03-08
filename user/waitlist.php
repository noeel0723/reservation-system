<?php
/**
 * User — Antrian Saya (Waitlist — Feature 10)
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/log_helper.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireStaff();

$pageTitle = 'My Waitlist';
$userId    = (int)$_SESSION['user_id'];

// Expire stale entries first
expireOldWaitlist($pdo);

$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

$entries = getWaitlistEntries($pdo, $userId);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_user.php';

$wBadge = function(string $s): string {
    $map  = ['Waiting' => 'rb-pending', 'Notified' => 'rb-approved',
             'Converted' => 'rb-finished', 'Expired' => 'rb-cancelled', 'Cancelled' => 'rb-cancelled'];
    $icon = ['Waiting' => 'bi-hourglass-split', 'Notified' => 'bi-bell-fill',
             'Converted' => 'bi-check2-all', 'Expired' => 'bi-clock-history', 'Cancelled' => 'bi-slash-circle'];
    $cls  = $map[$s]  ?? 'rb-cancelled';
    $ic   = $icon[$s] ?? 'bi-circle';
    return "<span class=\"res-badge {$cls}\"><i class=\"bi {$ic} res-badge-icon\"></i>{$s}</span>";
};
?>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($flashError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Notified Banner -->
<?php
$notifiedEntries = array_filter($entries, fn($e) => $e['status'] === 'Notified');
if (!empty($notifiedEntries)):
?>
<div class="alert border-0 mb-4 d-flex align-items-start gap-3"
     style="background:#f0fdf4;border-left:4px solid #16a34a!important;border-radius:10px">
    <i class="bi bi-bell-fill mt-1" style="color:#16a34a;font-size:1.2rem;flex-shrink:0"></i>
    <div>
        <div class="fw-semibold" style="color:#15803d">
            Slot tersedia! <?= count($notifiedEntries) ?> antrian Anda bisa dikonversi ke reservasi sekarang.
        </div>
        <div class="text-muted small mt-1">
            Segera buat reservasi sebelum slot diambil orang lain.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Intro card -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-body p-3 p-md-4 d-flex align-items-start gap-3">
        <i class="bi bi-clock-history mt-1" style="color:var(--color-moonstone);font-size:1.4rem;flex-shrink:0"></i>
        <div>
            <h6 class="fw-semibold mb-1">Antrian / Waitlist</h6>
            <p class="text-muted small mb-0">
                Daftar antrian muncul ketika jadwal yang Anda inginkan sudah terisi.
                Ketika slot menjadi kosong, status Anda akan berubah menjadi <strong>Notified</strong>
                dan Anda dapat langsung membuat reservasi.
            </p>
        </div>
    </div>
</div>

<?php if (empty($entries)): ?>
<!-- Empty state -->
<div class="card border-0 shadow-sm" style="border-radius:12px">
    <div class="card-body text-center py-5">
        <i class="bi bi-inbox d-block mb-3" style="font-size:3rem;color:var(--color-moonstone);opacity:0.4"></i>
        <h6 class="fw-semibold text-muted">Tidak ada antrian</h6>
        <p class="text-muted small mb-3">
            Saat jadwal yang Anda inginkan penuh, Anda bisa mendaftar antrian dari halaman New Reservation.
        </p>
        <a href="<?= BASE_URL ?>/user/reservasi_baru.php" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-plus-lg me-1"></i>New Reservation
        </a>
    </div>
</div>

<?php else: ?>

<!-- Waitlist cards (responsive: cards on mobile, table on desktop) -->

<!-- Mobile: Cards -->
<div class="d-md-none">
    <?php foreach ($entries as $e): ?>
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px">
        <div class="card-body p-3">
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <div>
                    <div class="fw-semibold" style="font-size:0.9rem"><?= htmlspecialchars($e['resource_nama']) ?></div>
                    <div class="text-muted" style="font-size:0.76rem">
                        <span class="badge rounded-pill" style="font-size:0.65rem;background:<?= $e['resource_tipe'] === 'Studio' ? '#e0f2fe;color:#0369a1' : '#fef9c3;color:#854d0e' ?>">
                            <?= $e['resource_tipe'] ?>
                        </span>
                    </div>
                </div>
                <?= $wBadge($e['status']) ?>
            </div>
            <div class="mb-2" style="font-size:0.82rem">
                <i class="bi bi-calendar3 me-1 text-muted"></i>
                <?= date('d M Y', strtotime($e['waktu_mulai'])) ?>
                &nbsp;<?= date('H:i', strtotime($e['waktu_mulai'])) ?>–<?= date('H:i', strtotime($e['waktu_selesai'])) ?>
            </div>
            <div class="mb-3 text-muted" style="font-size:0.8rem">
                <i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars(mb_strimwidth($e['keperluan'], 0, 60, '…')) ?>
            </div>
            <?php if ($e['status'] === 'Notified'): ?>
            <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="convert_waitlist">
                <input type="hidden" name="waitlist_id" value="<?= $e['id'] ?>">
                <button type="submit" class="btn btn-success btn-sm rounded-pill w-100 mb-2">
                    <i class="bi bi-check-circle me-1"></i>Buat Reservasi Sekarang
                </button>
            </form>
            <?php endif; ?>
            <?php if (in_array($e['status'], ['Waiting', 'Notified'])): ?>
            <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php"
                  onsubmit="return confirm('Batalkan antrian ini?')">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="cancel_waitlist">
                <input type="hidden" name="waitlist_id" value="<?= $e['id'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill w-100">
                    <i class="bi bi-x-lg me-1"></i>Batalkan Antrian
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Desktop: Table -->
<div class="d-none d-md-block">
    <div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
        <table class="table table-hover mb-0" style="font-size:0.825rem">
            <thead class="table-light">
                <tr>
                    <th>Resource</th>
                    <th>Keperluan</th>
                    <th>Jadwal</th>
                    <th>Status</th>
                    <th>Didaftar</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $e): ?>
                <tr>
                    <td>
                        <span class="badge rounded-pill me-1" style="font-size:0.65rem;background:<?= $e['resource_tipe'] === 'Studio' ? '#e0f2fe;color:#0369a1' : '#fef9c3;color:#854d0e' ?>">
                            <?= $e['resource_tipe'] ?>
                        </span>
                        <?= htmlspecialchars($e['resource_nama']) ?>
                    </td>
                    <td><?= htmlspecialchars(mb_strimwidth($e['keperluan'], 0, 40, '…')) ?></td>
                    <td style="white-space:nowrap">
                        <?= date('d/m/Y', strtotime($e['waktu_mulai'])) ?><br>
                        <span class="text-muted" style="font-size:0.76rem">
                            <?= date('H:i', strtotime($e['waktu_mulai'])) ?>–<?= date('H:i', strtotime($e['waktu_selesai'])) ?>
                        </span>
                    </td>
                    <td><?= $wBadge($e['status']) ?></td>
                    <td class="text-muted" style="font-size:0.76rem;white-space:nowrap">
                        <?= date('d/m/Y H:i', strtotime($e['created_at'])) ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if ($e['status'] === 'Notified'): ?>
                            <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="action" value="convert_waitlist">
                                <input type="hidden" name="waitlist_id" value="<?= $e['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3"
                                        style="font-size:0.76rem">
                                    <i class="bi bi-check-circle me-1"></i>Buat Reservasi
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if (in_array($e['status'], ['Waiting', 'Notified'])): ?>
                            <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="d-inline"
                                  onsubmit="return confirm('Batalkan antrian ini?')">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="action" value="cancel_waitlist">
                                <input type="hidden" name="waitlist_id" value="<?= $e['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        style="border-radius:8px;width:32px">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
