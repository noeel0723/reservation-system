<?php
/**
 * Admin — Kelola Waitlist / Antrian (Feature 10)
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/log_helper.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireAdmin();

$pageTitle = 'Waitlist Queue';

// Expire stale entries on every load
expireOldWaitlist($pdo);

// Handle admin actions (cancel a waitlist entry)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $wid    = (int)($_POST['waitlist_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($wid > 0 && $action === 'cancel_admin') {
        $pdo->prepare("UPDATE waitlist SET status = 'Cancelled' WHERE id = :id")->execute([':id' => $wid]);
        logActivity($pdo, 'cancel', 'waitlist', $wid, "Admin membatalkan antrian #$wid.");
        setFlash('success', 'Entri antrian berhasil dibatalkan.');
    }

    header('Location: ' . BASE_URL . '/admin/waitlist.php');
    exit;
}

// Filters
$filterStatus = in_array($_GET['status'] ?? '', ['Waiting','Notified','Converted','Expired','Cancelled'])
              ? $_GET['status'] : '';

$allEntries = getWaitlistEntries($pdo, null, $filterStatus);

// Summary counts
$statusCounts = $pdo->query(
    "SELECT status, COUNT(*) AS cnt FROM waitlist GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';

$wBadge = function(string $s): string {
    $map = [
        'Waiting'   => 'rb-pending',
        'Notified'  => 'rb-approved',
        'Converted' => 'rb-finished',
        'Expired'   => 'rb-cancelled',
        'Cancelled' => 'rb-cancelled',
    ];
    $icon = [
        'Waiting'   => 'bi-hourglass-split',
        'Notified'  => 'bi-bell-fill',
        'Converted' => 'bi-check2-all',
        'Expired'   => 'bi-clock-history',
        'Cancelled' => 'bi-slash-circle',
    ];
    $cls  = $map[$s]  ?? 'rb-cancelled';
    $icon = $icon[$s] ?? 'bi-circle';
    return "<span class=\"res-badge {$cls}\"><i class=\"bi {$icon} res-badge-icon\"></i>{$s}</span>";
};
?>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
    <?= htmlspecialchars($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <?php
    $summaryItems = [
        ['Menunggu',     'Waiting',   'bi-hourglass-split', '#fffbeb', '#b45309', '#fcd34d'],
        ['Dinotifikasi', 'Notified',  'bi-bell-fill',       '#f0fdf4', '#15803d', '#bbf7d0'],
        ['Dikonversi',   'Converted', 'bi-check2-all',      '#eff6ff', '#1d4ed8', '#bfdbfe'],
        ['Kedaluwarsa',  'Expired',   'bi-clock-history',   '#f9fafb', '#374151', '#e5e7eb'],
    ];
    foreach ($summaryItems as [$label, $key, $icon, $bg, $color, $border]):
        $cnt = $statusCounts[$key] ?? 0;
    ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm" style="border-radius:12px">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="rounded-2 d-flex align-items-center justify-content-center"
                      style="width:38px;height:38px;background:<?= $bg ?>;color:<?= $color ?>;border:1.5px solid <?= $border ?>;flex-shrink:0;font-size:1.1rem">
                    <i class="bi <?= $icon ?>"></i>
                </span>
                <div>
                    <div class="fw-bold" style="font-size:1.3rem;line-height:1"><?= $cnt ?></div>
                    <div class="text-muted" style="font-size:0.78rem"><?= $label ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter Pills -->
<div class="d-flex align-items-center gap-2 flex-wrap mb-3">
    <?php
    $pills = ['' => 'Semua', 'Waiting' => 'Menunggu', 'Notified' => 'Dinotifikasi',
              'Converted' => 'Dikonversi', 'Expired' => 'Kedaluwarsa', 'Cancelled' => 'Dibatalkan'];
    foreach ($pills as $key => $label):
    ?>
    <a href="?status=<?= $key ?>"
       class="btn btn-sm px-3 <?= $filterStatus === $key ? 'btn-dark text-white' : 'btn-outline-secondary' ?>"
       style="border-radius:50px;font-size:0.78rem">
        <?= $label ?>
        <?php if ($key !== '' && isset($statusCounts[$key])): ?>
        <span class="badge bg-secondary ms-1 rounded-pill" style="font-size:0.65rem"><?= $statusCounts[$key] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:0.825rem">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Resource</th>
                    <th>Jadwal</th>
                    <th>Keperluan</th>
                    <th>Status</th>
                    <th>Didaftar</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allEntries)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:0.3"></i>
                        Tidak ada entri antrian.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($allEntries as $e): ?>
                <tr>
                    <td class="text-muted"><?= $e['id'] ?></td>
                    <td>
                        <div class="fw-medium"><?= htmlspecialchars($e['user_nama']) ?></div>
                        <?php if ($e['jabatan']): ?>
                        <div class="text-muted" style="font-size:0.73rem"><?= htmlspecialchars($e['jabatan']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge rounded-pill" style="font-size:0.68rem;background:<?= $e['resource_tipe'] === 'Studio' ? '#e0f2fe;color:#0369a1' : '#fef9c3;color:#854d0e' ?>">
                            <?= $e['resource_tipe'] ?>
                        </span>
                        <div style="font-size:0.8rem"><?= htmlspecialchars($e['resource_nama']) ?></div>
                    </td>
                    <td style="white-space:nowrap;font-size:0.78rem">
                        <?= date('d/m/Y', strtotime($e['waktu_mulai'])) ?><br>
                        <?= date('H:i', strtotime($e['waktu_mulai'])) ?>–<?= date('H:i', strtotime($e['waktu_selesai'])) ?>
                    </td>
                    <td><?= htmlspecialchars(mb_strimwidth($e['keperluan'], 0, 40, '…')) ?></td>
                    <td><?= $wBadge($e['status']) ?></td>
                    <td class="text-muted" style="font-size:0.76rem;white-space:nowrap">
                        <?= date('d/m/Y H:i', strtotime($e['created_at'])) ?>
                    </td>
                    <td>
                        <?php if (in_array($e['status'], ['Waiting', 'Notified'])): ?>
                        <form method="POST" class="d-inline"
                              onsubmit="return confirm('Batalkan antrian ini?')">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="cancel_admin">
                            <input type="hidden" name="waitlist_id" value="<?= $e['id'] ?>">
                            <button type="submit" class="btn btn-sm"
                                    style="background:#fff0f0;color:#dc3545;border:1px solid #f5c6cb;border-radius:8px">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
