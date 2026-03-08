<?php
/**
 * Riwayat Reservasi User
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireStaff();

$pageTitle = 'Reservation History';
$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

$userId      = (int)$_SESSION['user_id'];
$filterStatus = $_GET['status']    ?? '';
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to']   ?? '';
$searchQuery  = $_GET['q']         ?? '';
$sortBy       = $_GET['sort']      ?? 'newest';

// Count per status (always from all user reservations)
$allForCount = getReservations($pdo, ['user_id' => $userId]);
$counts = ['all' => count($allForCount), 'Pending' => 0, 'Approved' => 0, 'Rejected' => 0, 'Cancelled' => 0, 'Finished' => 0];
foreach ($allForCount as $r) {
    if ($r['status'] === 'Selesai' || $r['status'] === 'Finished') {
        $counts['Finished']++;
    } elseif (isset($counts[$r['status']])) {
        $counts[$r['status']]++;
    }
}

// Build filters for actual data
$filters = ['user_id' => $userId];
if ($filterStatus) $filters['status'] = ($filterStatus === 'Finished') ? 'Selesai' : $filterStatus;
if ($dateFrom)     $filters['date_from'] = $dateFrom . ' 00:00:00';
if ($dateTo)       $filters['date_to']   = $dateTo   . ' 23:59:59';

$reservations = getReservations($pdo, $filters);

// PHP-side search
if ($searchQuery) {
    $q = strtolower($searchQuery);
    $reservations = array_values(array_filter($reservations, fn($r) =>
        str_contains(strtolower($r['resource_nama']), $q) ||
        str_contains(strtolower($r['keperluan']), $q)
    ));
}

// Sort
if ($sortBy === 'oldest') {
    usort($reservations, fn($a, $b) => strtotime($a['created_at']) - strtotime($b['created_at']));
} elseif ($sortBy === 'az') {
    usort($reservations, fn($a, $b) => strcmp($a['resource_nama'], $b['resource_nama']));
}

// Helper: build tab URL preserving date/search/sort
function tabLink(string $status, string $df, string $dt, string $q, string $sort): string {
    $p = [];
    if ($status) $p['status']    = $status;
    if ($df)     $p['date_from'] = $df;
    if ($dt)     $p['date_to']   = $dt;
    if ($q)      $p['q']         = $q;
    if ($sort && $sort !== 'newest') $p['sort'] = $sort;
    return '?' . http_build_query($p);
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_user.php';
?>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3"><?= htmlspecialchars($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3"><?= htmlspecialchars($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<style>
.rh-shell { border:1px solid #e5e7eb; border-radius:18px; background:#fff; overflow:hidden; }
.rh-head { padding:14px 16px; border-bottom:1px solid #edf2f7; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.rh-tabs { display:flex; gap:8px; flex-wrap:wrap; }
.rh-tab { border:1px solid #e5e7eb; border-radius:999px; padding:6px 11px; font-size:.74rem; font-weight:700; color:#4b5563; text-decoration:none; background:#fff; }
.rh-tab.active { background:#4f46e5; border-color:#4f46e5; color:#fff; }
.rh-tools { padding:12px 16px; border-bottom:1px solid #edf2f7; background:#fcfcfd; }
.rh-table th { background:#f8fafc; color:#6b7280; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; }
.rh-table td { font-size:.82rem; vertical-align:middle; border-color:#f1f5f9; }
.rh-resource, .rh-purpose, .rh-note { max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block; }
@media (max-width: 767px) {
    .rh-head, .rh-tools { padding:12px; }
    .rh-resource, .rh-purpose, .rh-note { max-width:120px; }
}
</style>

<div class="rh-shell">
    <div class="rh-head">
        <div>
            <h5 class="mb-0 fw-bold">Reservation History</h5>
            <div class="text-muted" style="font-size:.76rem">Track your request timeline and statuses.</div>
        </div>
        <div class="text-muted" style="font-size:.78rem">Showing <?= count($reservations) ?> entries</div>
    </div>

    <div class="px-3 px-md-4 pt-3">
        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-3">
            <div class="rh-tabs mb-0">
                <a href="<?= tabLink('', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>" class="rh-tab <?= !$filterStatus ? 'active' : '' ?>">All (<?= $counts['all'] ?>)</a>
                <a href="<?= tabLink('Pending', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>" class="rh-tab <?= $filterStatus === 'Pending' ? 'active' : '' ?>">Pending (<?= $counts['Pending'] ?>)</a>
                <a href="<?= tabLink('Approved', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>" class="rh-tab <?= $filterStatus === 'Approved' ? 'active' : '' ?>">Approved (<?= $counts['Approved'] ?>)</a>
                <a href="<?= tabLink('Rejected', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>" class="rh-tab <?= $filterStatus === 'Rejected' ? 'active' : '' ?>">Rejected (<?= $counts['Rejected'] ?>)</a>
                <a href="<?= tabLink('Cancelled', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>" class="rh-tab <?= $filterStatus === 'Cancelled' ? 'active' : '' ?>">Cancelled (<?= $counts['Cancelled'] ?>)</a>
                <a href="<?= tabLink('Finished', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>" class="rh-tab <?= $filterStatus === 'Finished' ? 'active' : '' ?>">Finished (<?= $counts['Finished'] ?>)</a>
            </div>
            <form method="GET" class="m-0" style="min-width:min(100%,330px)">
                <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
                <?php if ($dateFrom): ?><input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"><?php endif; ?>
                <?php if ($dateTo): ?><input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"><?php endif; ?>
                <?php if ($sortBy && $sortBy !== 'newest'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy) ?>"><?php endif; ?>
                <div class="input-group input-group-sm search-pill-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control search-pill-input" placeholder="Search resource or purpose" value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
            </form>
        </div>
    </div>

    <div class="rh-tools">
        <form method="GET" class="row g-2 align-items-center" id="historyFilterForm">
            <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
            <div class="col-6 col-md-2">
                <input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
            </div>
            <div class="col-6 col-md-2">
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest</option>
                    <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Oldest</option>
                    <option value="az" <?= $sortBy === 'az' ? 'selected' : '' ?>>A-Z</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-2">
                <button class="btn btn-sm btn-primary w-100" type="submit">Apply</button>
                <a class="btn btn-sm btn-outline-secondary" href="<?= tabLink($filterStatus, '', '', '', 'newest') ?>"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <?php if (empty($reservations)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x d-block mb-3" style="font-size:2.3rem;opacity:.3"></i>
            <p class="mb-1 fw-medium">No reservation history found</p>
            <small>Try another filter or open <a href="<?= BASE_URL ?>/user/reservasi_baru.php" class="text-decoration-none" style="color:var(--color-moonstone)">New Reservation</a>.</small>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table rh-table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Resource</th>
                        <th>Purpose</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Admin Note</th>
                        <th class="pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td class="ps-3 text-muted">#<?= str_pad((string)$r['id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <span class="rh-resource fw-semibold"><?= htmlspecialchars($r['resource_nama']) ?></span>
                            <span class="text-muted" style="font-size:.72rem"><?= $r['resource_tipe'] ?></span>
                        </td>
                        <td><span class="rh-purpose"><?= htmlspecialchars($r['keperluan']) ?></span></td>
                        <td><?= date('d/m/Y', strtotime($r['waktu_mulai'])) ?></td>
                        <td><?= date('H:i', strtotime($r['waktu_mulai'])) ?>&ndash;<?= date('H:i', strtotime($r['waktu_selesai'])) ?></td>
                        <td>
                            <?php
                            $displaySt = ($r['status'] === 'Selesai') ? 'Finished' : $r['status'];
                            $rbCls = match($r['status']) {
                                'Pending'   => 'rb-pending',
                                'Approved'  => 'rb-approved',
                                'Rejected'  => 'rb-rejected',
                                'Cancelled' => 'rb-cancelled',
                                'Selesai'   => 'rb-finished',
                                default     => 'rb-cancelled',
                            };
                            $rbIco = match($r['status']) {
                                'Pending'   => 'bi-hourglass-split',
                                'Approved'  => 'bi-check-circle',
                                'Rejected'  => 'bi-x-circle',
                                'Cancelled' => 'bi-slash-circle',
                                'Selesai'   => 'bi-check2-all',
                                default     => 'bi-circle',
                            };
                            ?>
                            <span class="res-badge <?= $rbCls ?>"><i class="bi <?= $rbIco ?> res-badge-icon"></i><?= $displaySt ?></span>
                        </td>
                        <td><span class="rh-note text-muted fst-italic"><?= $r['catatan_admin'] ? htmlspecialchars($r['catatan_admin']) : '-' ?></span></td>
                        <td class="pe-3">
                            <?php if ($r['status'] === 'Pending'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="d-inline" onsubmit="return confirm('Batalkan reservasi ini?')">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" style="font-size:.73rem;padding:.2rem .62rem"><i class="bi bi-x me-1"></i>Cancel</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
