<?php
/**
 * Admin - Kelola Reservasi
 * Approve / Reject / Lihat Detail
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';
require_once __DIR__ . '/../functions/resource_helper.php';

requireAdmin();

// Auto-mark Selesai: reservasi Approved yang waktu_selesai sudah lewat
autoMarkSelesai($pdo);

$pageTitle = 'Reservation Management';
$flashSuccess = getFlash('success');
$flashError = getFlash('error');

// Filter
$filterStatus = $_GET['status'] ?? '';
$filterResource = $_GET['resource_id'] ?? '';
$filters = [];
if ($filterStatus) $filters['status'] = $filterStatus;
if ($filterResource) $filters['resource_id'] = (int)$filterResource;

$reservations = getReservations($pdo, $filters);
$resources = getResources($pdo);

$metricTotal    = count($reservations);
$metricPending  = count(array_filter($reservations, fn($r) => $r['status'] === 'Pending'));
$metricApproved = count(array_filter($reservations, fn($r) => $r['status'] === 'Approved'));
$metricFinished = count(array_filter($reservations, fn($r) => in_array($r['status'], ['Finished', 'Selesai'])));

// Detail modal data
$detailData = null;
if (!empty($_GET['detail'])) {
    $detailData = getReservationById($pdo, (int)$_GET['detail']);
}

// Pagination
$perPage    = in_array((int)($_GET['per_page'] ?? 6), [6, 10, 20]) ? (int)($_GET['per_page'] ?? 6) : 6;
$totalAll   = count($reservations);
$totalPages = max(1, (int)ceil($totalAll / $perPage));
$page       = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
$reservations = array_slice($reservations, ($page - 1) * $perPage, $perPage);
$emptyRows  = max(0, $perPage - count($reservations));

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';
?>



<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3"><?= htmlspecialchars($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3"><?= htmlspecialchars($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<style>
.rv-shell { border:1px solid #e5e7eb; border-radius:18px; background:#fff; overflow:hidden; }
.rv-head { padding:14px 16px; border-bottom:1px solid #eef2f5; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
.rv-controls { padding:12px 16px; border-bottom:1px solid #eef2f5; background:#fcfcfd; }
.rv-kpi { border:1px solid #e5e7eb; border-radius:12px; padding:10px 12px; background:#fff; }
.rv-kpi .num { font-size:1.4rem; font-weight:800; color:#0f172a; line-height:1; }
.rv-kpi .lbl { font-size:.72rem; color:#6b7280; text-transform:uppercase; letter-spacing:.05em; }
.rv-table th { background:#f8fafc; color:#6b7280; font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #e5e7eb; }
.rv-table td { font-size:.82rem; vertical-align:middle; border-color:#f1f5f9; }
.rv-user, .rv-purpose { max-width:220px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.rv-actions { display:flex; gap:6px; justify-content:center; flex-wrap:wrap; }
@media (max-width: 767px) {
    .rv-head, .rv-controls { padding:12px; }
    .rv-user, .rv-purpose { max-width:140px; }
}
</style>

<div class="rv-shell mb-3">
    <div class="rv-head">
        <div>
            <h5 class="mb-0 fw-bold">Reservations</h5>
            <div class="text-muted" style="font-size:.76rem">Review and process reservation requests.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <div class="rv-kpi"><div class="lbl">Total</div><div class="num"><?= $metricTotal ?></div></div>
            <div class="rv-kpi"><div class="lbl">Pending</div><div class="num" style="color:#b45309"><?= $metricPending ?></div></div>
            <div class="rv-kpi"><div class="lbl">Approved</div><div class="num" style="color:#15803d"><?= $metricApproved ?></div></div>
            <div class="rv-kpi"><div class="lbl">Finished</div><div class="num" style="color:#0e7490"><?= $metricFinished ?></div></div>
        </div>
    </div>

    <div class="rv-controls">
        <form method="GET" class="row g-2 align-items-end" id="reservationsFilterForm">
            <div class="col-12 col-md-4 col-lg-3">
                <label class="form-label small fw-medium mb-1">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <?php foreach (['Pending', 'Approved', 'Rejected', 'Cancelled', 'Finished'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-5 col-lg-4">
                <label class="form-label small fw-medium mb-1">Resource</label>
                <select name="resource_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Resources</option>
                    <?php foreach ($resources as $res): ?>
                        <option value="<?= $res['id'] ?>" <?= $filterResource == $res['id'] ? 'selected' : '' ?>><?= htmlspecialchars($res['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <select class="form-select form-select-sm" name="per_page" onchange="this.form.submit()">
                    <?php foreach ([6, 10, 20] as $rpp): ?>
                    <option value="<?= $rpp ?>" <?= $perPage === $rpp ? 'selected' : '' ?>><?= $rpp ?> rows</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-12 col-lg-3">
                <a href="<?= BASE_URL ?>/admin/kelola_reservasi.php" class="btn btn-sm btn-outline-secondary w-100">Reset Filters</a>
            </div>
        </form>
    </div>

    <div class="table-responsive" style="min-height:420px">
        <table class="table rv-table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th class="ps-3">ID</th>
                    <th>Borrower</th>
                    <th>Resource</th>
                    <th>Purpose</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th class="text-center pe-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">No reservations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservations as $i => $r): ?>
                    <tr>
                        <td class="ps-3 text-muted">#<?= str_pad((string)($i + 1 + (($page - 1) * $perPage)), 3, '0', STR_PAD_LEFT) ?></td>
                        <td>
                            <div class="rv-user fw-semibold"><?= htmlspecialchars($r['peminjam']) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($r['jabatan'] ?? '-') ?></div>
                        </td>
                        <td>
                            <span class="badge <?= $r['resource_tipe'] === 'Studio' ? 'bg-primary' : 'bg-info' ?> me-1"><?= $r['resource_tipe'] ?></span>
                            <span class="rv-user"><?= htmlspecialchars($r['resource_nama']) ?></span>
                        </td>
                        <td><span class="rv-purpose"><?= htmlspecialchars($r['keperluan']) ?></span></td>
                        <td>
                            <div style="font-size:.76rem"><?= date('d M Y', strtotime($r['waktu_mulai'])) ?></div>
                            <div class="text-muted" style="font-size:.72rem"><?= date('H:i', strtotime($r['waktu_mulai'])) ?> - <?= date('H:i', strtotime($r['waktu_selesai'])) ?></div>
                        </td>
                        <td>
                            <?php
                            $_st  = $r['status'];
                            $_lbl = $_st === 'Selesai' ? 'Finished' : $_st;
                            $_cls = match($_st) {
                                'Pending'           => 'rb-pending',
                                'Approved'          => 'rb-approved',
                                'Rejected'          => 'rb-rejected',
                                'Cancelled'         => 'rb-cancelled',
                                'Selesai','Finished' => 'rb-finished',
                                default             => 'rb-cancelled',
                            };
                            $_ico = match($_st) {
                                'Pending'           => 'bi-hourglass-split',
                                'Approved'          => 'bi-check-circle',
                                'Rejected'          => 'bi-x-circle',
                                'Cancelled'         => 'bi-slash-circle',
                                'Selesai','Finished' => 'bi-check2-all',
                                default             => 'bi-circle',
                            };
                            ?>
                            <span class="res-badge <?= $_cls ?>"><i class="bi <?= $_ico ?> res-badge-icon"></i><?= $_lbl ?></span>
                        </td>
                        <td class="text-center pe-3">
                            <div class="rv-actions">
                                <?php if ($r['status'] === 'Pending'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/admin/proses_reservasi.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="Approved">
                                    <button type="submit" class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Approve reservasi ini?')"><i class="bi bi-check-lg"></i></button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" title="Reject" data-bs-toggle="modal" data-bs-target="#rejectModal" data-id="<?= $r['id'] ?>"><i class="bi bi-x-lg"></i></button>
                                <?php elseif ($r['status'] === 'Approved'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/admin/proses_reservasi.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="Finished">
                                    <button type="submit" class="btn btn-sm btn-outline-info" title="Mark Finished" onclick="return confirm('Tandai reservasi ini sebagai Finished?')"><i class="bi bi-check2-all"></i></button>
                                </form>
                                <?php endif; ?>
                                <a href="?detail=<?= $r['id'] ?><?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $filterResource ? '&resource_id='.$filterResource : '' ?>&page=<?= $page ?><?= $perPage !== 6 ? '&per_page='.$perPage : '' ?>" class="btn btn-sm btn-outline-primary" title="Detail"><i class="bi bi-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php for ($ef = 0; $ef < $emptyRows; $ef++): ?>
                    <tr><td colspan="7" style="height:67px;border-bottom:1px solid #f9fafb;pointer-events:none"></td></tr>
                    <?php endfor; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/admin/proses_reservasi.php">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="reservation_id" id="rejectReservationId">
                <input type="hidden" name="action" value="Rejected">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-x-circle text-danger me-2"></i>Tolak Reservasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="catatan" class="form-label fw-medium">Alasan Penolakan</label>
                        <textarea class="form-control" name="catatan" id="catatan" rows="3" placeholder="Berikan alasan penolakan..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Reservasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php
$qBase     = '?';
if ($filterStatus)   $qBase .= 'status='      . urlencode($filterStatus)   . '&';
if ($filterResource) $qBase .= 'resource_id=' . urlencode($filterResource) . '&';
$qPageBase = $qBase . ($perPage !== 6 ? 'per_page=' . $perPage . '&' : '');
?>
<div class="d-flex flex-column align-items-center gap-2 mt-4">
    <?php if ($totalPages > 1): ?>
    <div class="d-flex align-items-center gap-2 flex-wrap justify-content-center">
        <a href="<?= $qPageBase ?>page=<?= max(1, $page - 1) ?>"
           class="kr-page-nav<?= $page <= 1 ? ' disabled' : '' ?>">
            <i class="bi bi-chevron-left" style="font-size:0.7rem"></i> Prev
        </a>
        <div class="kr-page-pills">
            <?php
            if ($totalPages <= 7) {
                $pageRange = range(1, $totalPages);
            } elseif ($page <= 4) {
                $pageRange = array_merge(range(1, 5), ['...', $totalPages]);
            } elseif ($page >= $totalPages - 3) {
                $pageRange = array_merge([1, '...'], range($totalPages - 4, $totalPages));
            } else {
                $pageRange = [1, '...', $page - 1, $page, $page + 1, '...', $totalPages];
            }
            foreach ($pageRange as $p):
                if ($p === '...'):
            ?><span class="kr-page-ellipsis">···</span><?php
                else:
            ?><a href="<?= $qPageBase ?>page=<?= $p ?>"
               class="kr-page-num<?= $p === $page ? ' active' : '' ?>"><?= $p ?></a><?php
                endif;
            endforeach;
            ?>
        </div>
        <a href="<?= $qPageBase ?>page=<?= min($totalPages, $page + 1) ?>"
           class="kr-page-nav<?= $page >= $totalPages ? ' disabled' : '' ?>">
            Next <i class="bi bi-chevron-right" style="font-size:0.7rem"></i>
        </a>
    </div>
    <?php endif; ?>
    <div class="kr-rows-selector">
        <?php foreach ([6, 10, 20] as $rpp): ?>
        <a href="<?= $qBase ?>page=1&per_page=<?= $rpp ?>"
           class="kr-rows-opt<?= $perPage === $rpp ? ' active' : '' ?>"><?= $rpp ?> rows</a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Detail Modal -->
<?php if ($detailData): ?>
<div class="modal fade show" id="detailModal" tabindex="-1" style="display:block;background:rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle text-primary me-2"></i>Detail Reservasi #<?= $detailData['id'] ?></h5>
                <a href="<?= BASE_URL ?>/admin/kelola_reservasi.php" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Peminjam</label>
                        <p class="fw-semibold mb-1"><?= htmlspecialchars($detailData['peminjam']) ?></p>
                        <small class="text-muted"><?= htmlspecialchars($detailData['jabatan'] ?? '-') ?> &bull; @<?= htmlspecialchars($detailData['peminjam_username']) ?></small>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Resource</label>
                        <p class="fw-semibold mb-1">
                            <span class="badge <?= $detailData['resource_tipe'] === 'Studio' ? 'bg-primary' : 'bg-info' ?>"><?= $detailData['resource_tipe'] ?></span>
                            <?= htmlspecialchars($detailData['resource_nama']) ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Waktu Mulai</label>
                        <p class="fw-semibold"><?= date('d F Y, H:i', strtotime($detailData['waktu_mulai'])) ?> WIB</p>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Waktu Selesai</label>
                        <p class="fw-semibold"><?= date('d F Y, H:i', strtotime($detailData['waktu_selesai'])) ?> WIB</p>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Keperluan</label>
                        <p class="fw-semibold"><?= htmlspecialchars($detailData['keperluan']) ?></p>
                    </div>
                    <?php if ($detailData['keterangan']): ?>
                    <div class="col-12">
                        <label class="text-muted small">Keterangan Tambahan</label>
                        <p><?= nl2br(htmlspecialchars($detailData['keterangan'])) ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label class="text-muted small">Status</label>
                        <p>
                                <?php
                                $_dst  = $detailData['status'];
                                $_dlbl = $_dst === 'Selesai' ? 'Finished' : $_dst;
                                $_dcls = match($_dst) {
                                    'Pending'           => 'rb-pending',
                                    'Approved'          => 'rb-approved',
                                    'Rejected'          => 'rb-rejected',
                                    'Cancelled'         => 'rb-cancelled',
                                    'Selesai','Finished' => 'rb-finished',
                                    default             => 'rb-cancelled',
                                };
                                $_dico = match($_dst) {
                                    'Pending'           => 'bi-hourglass-split',
                                    'Approved'          => 'bi-check-circle',
                                    'Rejected'          => 'bi-x-circle',
                                    'Cancelled'         => 'bi-slash-circle',
                                    'Selesai','Finished' => 'bi-check2-all',
                                    default             => 'bi-circle',
                                };
                                ?>
                                <span class="res-badge <?= $_dcls ?>" style="font-size:0.92rem;padding:0.5em 1.1em">
                                    <i class="bi <?= $_dico ?> res-badge-icon"></i><?= $_dlbl ?>
                                </span>
                        </p>
                    </div>
                    <?php if ($detailData['admin_nama']): ?>
                    <div class="col-md-6">
                        <label class="text-muted small">Diproses oleh</label>
                        <p class="fw-semibold"><?= htmlspecialchars($detailData['admin_nama']) ?> <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($detailData['approved_at'])) ?></small></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($detailData['catatan_admin']): ?>
                    <div class="col-12">
                        <label class="text-muted small">Catatan Admin</label>
                        <p class="fst-italic"><?= nl2br(htmlspecialchars($detailData['catatan_admin'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= BASE_URL ?>/admin/kelola_reservasi.php" class="btn btn-secondary">Tutup</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rejectModal = document.getElementById('rejectModal');
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', function(e) {
            const id = e.relatedTarget.getAttribute('data-id');
            document.getElementById('rejectReservationId').value = id;
        });
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
