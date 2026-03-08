<?php
/**
 * Staff Dashboard
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireStaff();

// AJAX: calendar events API (embedded in dashboard)
if (isset($_GET['api']) && $_GET['api'] === 'calendar_events') {
    header('Content-Type: application/json');
    $uid = (int)$_SESSION['user_id'];
    $stmt = $pdo->query(
        "SELECT r.id, r.waktu_mulai, r.waktu_selesai, r.keperluan, r.status, r.user_id,
                res.nama AS resource_nama, res.tipe AS resource_tipe,
                u.nama_lengkap AS peminjam
         FROM reservations r
         JOIN resources res ON r.resource_id = res.id
         JOIN users u ON r.user_id = u.id
         WHERE r.status IN ('Approved', 'Pending', 'Selesai')
         ORDER BY r.waktu_mulai"
    );
    $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $events = [];
    foreach ($rows as $r) {
        $isOwn   = (int)$r['user_id'] === $uid;
        $display = ($r['status'] === 'Selesai') ? 'Finished' : $r['status'];
        $color   = match($r['status']) {
            'Approved' => '#22c55e',
            'Pending'  => '#f59e0b',
            'Selesai'  => '#3b82f6',
            default    => '#9ca3af',
        };
        $events[] = [
            'id'          => $r['id'],
            'title'       => $r['resource_nama'] . ' — ' . $r['keperluan'],
            'start'       => $r['waktu_mulai'],
            'end'         => $r['waktu_selesai'],
            'color'       => $isOwn ? $color : $color . 'CC',
            'borderColor' => $color,
            'textColor'   => '#fff',
            'extendedProps' => [
                'status'    => $display,
                'resource'  => $r['resource_nama'],
                'tipe'      => $r['resource_tipe'],
                'peminjam'  => $r['peminjam'],
                'keperluan' => $r['keperluan'],
                'isOwn'     => $isOwn,
            ],
        ];
    }
    echo json_encode($events);
    exit;
}

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

<!-- FullCalendar CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.css">

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

<!-- Main Content Row: Recent Reservations + Calendar -->
<div class="row g-4">
    <!-- Recent Reservations -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2"></i>Reservasi Terbaru</h6>
                <a href="<?= BASE_URL ?>/user/riwayat.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
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
                                        <span class="d-block text-truncate" style="max-width:120px;font-size:0.82rem"><?= htmlspecialchars($r['resource_nama']) ?></span>
                                        <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($r['keperluan'], 0, 22, '…')) ?></small>
                                    </td>
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
    </div>

    <!-- Embedded Calendar -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden">
            <div class="card-body p-0">
                <!-- Calendar Toolbar -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-3 py-2 border-bottom">
                    <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-calendar3" style="color:var(--color-moonstone)"></i>
                        Kalender Reservasi
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= BASE_URL ?>/user/reservasi_baru.php" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">Reservasi Baru</span><span class="d-sm-none">+</span>
                        </a>
                    </div>
                </div>
                <!-- Legend -->
                <div class="d-flex align-items-center gap-3 flex-wrap px-3 py-2 border-bottom" style="background:#fafbfc;font-size:0.76rem">
                    <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:#22c55e;display:inline-block"></span>Approved</span>
                    <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:#f59e0b;display:inline-block"></span>Pending</span>
                    <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;border-radius:2px;background:#3b82f6;display:inline-block"></span>Finished</span>
                    <span class="text-muted ms-auto d-none d-sm-block"><i class="bi bi-cursor me-1"></i>Klik event untuk detail</span>
                </div>
                <!-- Calendar -->
                <div class="p-2 p-md-3">
                    <div id="dashboardCalendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="dashCalEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h6 class="modal-title fw-bold" id="dashCalModalTitle">Detail Reservasi</h6>
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted" style="width:90px;font-size:0.78rem;flex-shrink:0">Status</span>
                        <span id="dashCalModalStatus" class="badge rounded-pill px-3 py-1" style="font-size:0.8rem"></span>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <span class="text-muted" style="width:90px;font-size:0.78rem;flex-shrink:0">Resource</span>
                        <span id="dashCalModalResource" class="fw-medium" style="font-size:0.88rem"></span>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <span class="text-muted" style="width:90px;font-size:0.78rem;flex-shrink:0">Waktu</span>
                        <span id="dashCalModalTime" style="font-size:0.85rem"></span>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <span class="text-muted" style="width:90px;font-size:0.78rem;flex-shrink:0">Keperluan</span>
                        <span id="dashCalModalKeperluan" style="font-size:0.85rem"></span>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <span class="text-muted" style="width:90px;font-size:0.78rem;flex-shrink:0">Peminjam</span>
                        <span id="dashCalModalPeminjam" style="font-size:0.85rem"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var calEl = document.getElementById('dashboardCalendar');
    var cal   = new FullCalendar.Calendar(calEl, {
        initialView: window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: { today: 'Hari Ini', month: 'Bulan', week: 'Minggu', list: 'Daftar' },
        locale:    'id',
        height:    'auto',
        firstDay:  1,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        events: '<?= BASE_URL ?>/user/dashboard.php?api=calendar_events',
        eventDidMount: function (info) {
            if (info.event.extendedProps.isOwn) {
                info.el.style.fontWeight = '600';
                info.el.style.boxShadow  = '0 1px 3px rgba(0,0,0,0.2)';
            }
        },
        eventClick: function (info) {
            var p    = info.event.extendedProps;
            var st   = info.event.start;
            var en   = info.event.end;
            var opts = { day: '2-digit', month: 'short', year: 'numeric' };
            var tOpts = { hour: '2-digit', minute: '2-digit', hour12: false };

            document.getElementById('dashCalModalTitle').textContent      = 'Reservasi #' + info.event.id;
            document.getElementById('dashCalModalResource').textContent   = p.tipe + ' — ' + p.resource;
            document.getElementById('dashCalModalKeperluan').textContent  = p.keperluan;
            document.getElementById('dashCalModalPeminjam').textContent   = p.peminjam + (p.isOwn ? ' (Anda)' : '');
            document.getElementById('dashCalModalTime').textContent =
                st.toLocaleDateString('id-ID', opts) + ' ' +
                st.toLocaleTimeString('id-ID', tOpts) +
                (en ? ' – ' + en.toLocaleTimeString('id-ID', tOpts) : '');

            var badge    = document.getElementById('dashCalModalStatus');
            var colorMap = { 'Approved': '#22c55e', 'Pending': '#f59e0b', 'Finished': '#3b82f6', 'Rejected': '#ef4444', 'Cancelled': '#9ca3af' };
            var c        = colorMap[p.status] || '#9ca3af';
            badge.textContent      = p.status;
            badge.style.background = c;
            badge.style.color      = '#fff';

            new bootstrap.Modal(document.getElementById('dashCalEventModal')).show();
        }
    });
    cal.render();

    window.addEventListener('resize', function () {
        var newView = window.innerWidth < 768 ? 'listWeek' : 'dayGridMonth';
        if (cal.view.type !== newView) cal.changeView(newView);
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
