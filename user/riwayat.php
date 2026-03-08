<?php
/**
 * Riwayat Reservasi User
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireStaff();

$pageTitle = 'Riwayat Reservasi';
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
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">

        <!-- Row 1: Tabs + Date Range -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 px-4 pt-4 pb-3 border-bottom">
            <div class="riwayat-tabs d-flex gap-1 flex-wrap">
                <a href="<?= tabLink('', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>"
                   class="riwayat-tab <?= !$filterStatus ? 'active' : '' ?>">
                    Semua <span class="tab-count"><?= $counts['all'] ?></span>
                </a>
                <a href="<?= tabLink('Pending', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>"
                   class="riwayat-tab <?= $filterStatus === 'Pending' ? 'active' : '' ?>">
                    Pending <span class="tab-count"><?= $counts['Pending'] ?></span>
                </a>
                <a href="<?= tabLink('Approved', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>"
                   class="riwayat-tab <?= $filterStatus === 'Approved' ? 'active' : '' ?>">
                    Approved <span class="tab-count"><?= $counts['Approved'] ?></span>
                </a>
                <a href="<?= tabLink('Rejected', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>"
                   class="riwayat-tab <?= $filterStatus === 'Rejected' ? 'active' : '' ?>">
                    Rejected <span class="tab-count"><?= $counts['Rejected'] ?></span>
                </a>
                <a href="<?= tabLink('Cancelled', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>"
                   class="riwayat-tab <?= $filterStatus === 'Cancelled' ? 'active' : '' ?>">
                    Cancelled <span class="tab-count"><?= $counts['Cancelled'] ?></span>
                </a>
                <a href="<?= tabLink('Finished', $dateFrom, $dateTo, $searchQuery, $sortBy) ?>"
                   class="riwayat-tab <?= $filterStatus === 'Finished' ? 'active' : '' ?>">
                    Finished <span class="tab-count"><?= $counts['Finished'] ?></span>
                </a>
            </div>

            <!-- Date Range Filter -->
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
                <?php if ($searchQuery): ?><input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>"><?php endif; ?>
                <?php if ($sortBy !== 'newest'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy) ?>"><?php endif; ?>
                <div class="date-range-pill d-flex align-items-center gap-2">
                    <div class="date-range-input">
                        <i class="bi bi-calendar3"></i>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                    </div>
                    <span class="text-muted small">To</span>
                    <div class="date-range-input">
                        <i class="bi bi-calendar3"></i>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="padding:0.25rem 0.65rem;font-size:0.78rem">Terapkan</button>
                    <?php if ($dateFrom || $dateTo): ?>
                        <a href="<?= tabLink($filterStatus, '', '', $searchQuery, $sortBy) ?>" class="text-muted" title="Reset tanggal"><i class="bi bi-x-circle" style="font-size:0.85rem"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Row 2: Search + Sort -->
        <form method="GET" class="d-flex align-items-center justify-content-between gap-3 px-4 py-3 border-bottom">
            <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <?php if ($dateFrom): ?><input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"><?php endif; ?>
            <?php if ($dateTo): ?><input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"><?php endif; ?>
            <div class="riwayat-search-wrap d-flex align-items-center gap-2">
                <i class="bi bi-search text-muted" style="font-size:0.85rem"></i>
                <input type="text" name="q" class="riwayat-search-input"
                       placeholder="Cari resource atau keperluan..."
                       value="<?= htmlspecialchars($searchQuery) ?>">
            </div>
            <div class="d-flex align-items-center gap-2">
                <select name="sort" class="sort-select" onchange="this.form.submit()">
                    <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>Terlama</option>
                    <option value="az"     <?= $sortBy === 'az'     ? 'selected' : '' ?>>A - Z Resource</option>
                </select>
                <i class="bi bi-funnel-fill text-muted" style="font-size:0.8rem"></i>
            </div>
        </form>

        <!-- Table -->
        <?php if (empty($reservations)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calendar-x d-block mb-3" style="font-size:2.5rem;opacity:0.3"></i>
                <p class="mb-1 fw-medium">Tidak ada reservasi ditemukan</p>
                <small>Coba ubah filter atau <a href="<?= BASE_URL ?>/user/reservasi_baru.php" class="text-decoration-none" style="color:var(--color-moonstone)">ajukan reservasi baru</a></small>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table riwayat-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">
                                <span class="d-flex align-items-center gap-1">
                                    # <i class="bi bi-chevron-expand" style="font-size:0.6rem;opacity:0.4"></i>
                                </span>
                            </th>
                            <th>Resource</th>
                            <th>Keperluan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>Catatan Admin</th>
                            <th class="pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="riwayat-id">#<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="resource-thumb flex-shrink-0">
                                        <i class="bi <?= $r['resource_tipe'] === 'Studio' ? 'bi-camera-reels' : 'bi-camera-video' ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium" style="font-size:0.875rem"><?= htmlspecialchars($r['resource_nama']) ?></div>
                                        <div class="text-muted" style="font-size:0.75rem"><?= $r['resource_tipe'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:0.875rem"><?= htmlspecialchars($r['keperluan']) ?></td>
                            <td style="font-size:0.875rem"><?= date('d/m/Y', strtotime($r['waktu_mulai'])) ?></td>
                            <td style="font-size:0.875rem"><?= date('H:i', strtotime($r['waktu_mulai'])) ?>&ndash;<?= date('H:i', strtotime($r['waktu_selesai'])) ?></td>
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
                                <span class="res-badge <?= $rbCls ?>">
                                    <i class="bi <?= $rbIco ?> res-badge-icon"></i><?= $displaySt ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted fst-italic" style="font-size:0.8rem">
                                    <?= $r['catatan_admin'] ? htmlspecialchars($r['catatan_admin']) : '-' ?>
                                </span>
                            </td>
                            <td class="pe-4">
                                <?php if ($r['status'] === 'Pending'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="d-inline"
                                          onsubmit="return confirm('Batalkan reservasi ini?')">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" style="font-size:0.75rem;padding:0.2rem 0.65rem">
                                            <i class="bi bi-x me-1"></i>Batal
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.8rem">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-2 border-top">
                <small class="text-muted">Menampilkan <strong><?= count($reservations) ?></strong> reservasi</small>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
