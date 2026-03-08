<?php
/**
 * Kalender Reservasi User — Redesigned
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireStaff();

// AJAX: return calendar events as JSON
if (isset($_GET['api']) && $_GET['api'] === 'events') {
    header('Content-Type: application/json');
    $userId = (int)$_SESSION['user_id'];
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
        $isOwn   = (int)$r['user_id'] === $userId;
        $display = ($r['status'] === 'Selesai') ? 'Finished' : $r['status'];
        // Soft pastel bg colors matching res-badge palette
        $bgColor = match($r['status']) {
            'Approved' => $isOwn ? '#bbf7d0' : '#dcfce7',
            'Pending'  => $isOwn ? '#fed7aa' : '#ffedd5',
            'Selesai'  => $isOwn ? '#bfdbfe' : '#dbeafe',
            default    => '#e5e7eb',
        };
        $textColor = match($r['status']) {
            'Approved' => '#166534',
            'Pending'  => '#92400e',
            'Selesai'  => '#1e40af',
            default    => '#374151',
        };
        $borderColor = match($r['status']) {
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
            'backgroundColor' => $bgColor,
            'borderColor'     => $borderColor,
            'textColor'       => $textColor,
            'extendedProps'   => [
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

$pageTitle = 'Kalender Reservasi';
include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_user.php';
?>


<!-- FullCalendar CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.css">

<style>
/* ---- Calendar Page Custom Styles ---- */
.cal-page-wrap {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 20px rgba(0,0,0,0.06);
}

/* Top bar — breadcrumb style */
.cal-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    gap: 1rem;
    flex-wrap: wrap;
}
.cal-title-group h5 {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--color-midnight-green);
    margin: 0;
    line-height: 1.2;
}
.cal-title-group p {
    font-size: 0.78rem;
    color: #6b7280;
    margin: 0;
    margin-top: 2px;
}

/* View toggle — Day / Week / Month pills */
.cal-view-toggle {
    display: flex;
    background: #f3f4f6;
    border-radius: 8px;
    padding: 3px;
    gap: 0;
}
.cal-view-btn {
    border: none;
    background: transparent;
    border-radius: 6px;
    padding: 0.3rem 0.85rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
    white-space: nowrap;
}
.cal-view-btn.active {
    background: #fff;
    color: var(--color-midnight-green);
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Nav row — prev/next + today + date range */
.cal-nav-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.6rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.cal-nav-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.cal-nav-btn {
    width: 32px;
    height: 32px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    color: #374151;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.85rem;
    transition: background 0.15s;
}
.cal-nav-btn:hover { background: #f9fafb; }
.cal-today-btn {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
    color: var(--color-midnight-green);
    padding: 0.2rem 0.8rem;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s;
}
.cal-today-btn:hover { background: #f3f4f6; }
.cal-date-label {
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
    white-space: nowrap;
}

/* Legend row */
.cal-legend-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
    background: #fafbfc;
    flex-wrap: wrap;
    font-size: 0.76rem;
    color: #6b7280;
}
.cal-legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
    display: inline-block;
    flex-shrink: 0;
}

/* FullCalendar overrides for clean look */
#mainCalendar .fc-header-toolbar { display: none !important; }
#mainCalendar .fc-view-harness { border: none; }
#mainCalendar .fc-scrollgrid { border: none !important; }
#mainCalendar .fc-scrollgrid td, #mainCalendar .fc-scrollgrid th { border-color: #f3f4f6 !important; }
#mainCalendar .fc-col-header-cell { background: #fafbfc; }
#mainCalendar .fc-col-header-cell-cushion {
    font-size: 0.78rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 0.5rem 0.4rem;
    text-decoration: none;
}
#mainCalendar .fc-timegrid-slot-label-cushion {
    font-size: 0.72rem;
    color: #9ca3af;
    font-weight: 500;
}
#mainCalendar .fc-daygrid-day-number {
    font-size: 0.8rem;
    color: #374151;
    font-weight: 500;
    text-decoration: none;
}
#mainCalendar .fc-day-today .fc-daygrid-day-number,
#mainCalendar .fc-day-today .fc-col-header-cell-cushion {
    color: var(--color-moonstone);
    font-weight: 700;
}
#mainCalendar .fc-day-today { background: rgba(68,166,181,0.04) !important; }
#mainCalendar .fc-timegrid-now-indicator-line { border-color: var(--color-moonstone); }
#mainCalendar .fc-timegrid-now-indicator-arrow { border-top-color: var(--color-moonstone); border-bottom-color: var(--color-moonstone); }
#mainCalendar .fc-event {
    border-radius: 8px;
    border-width: 1.5px;
    font-size: 0.74rem;
    font-weight: 600;
    padding: 2px 5px;
    cursor: pointer;
    overflow: hidden;
}
#mainCalendar .fc-timegrid-event .fc-event-main {
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
#mainCalendar .fc-event-title {
    font-weight: 600;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
}
#mainCalendar .fc-event-time {
    font-size: 0.68rem;
    opacity: 0.8;
    overflow: hidden;
    white-space: nowrap;
}
/* Ensure stacked events keep a readable minimum width */
#mainCalendar .fc-timegrid-event-harness {
    min-width: 0;
}
#mainCalendar .fc-list-event:hover td { background: rgba(68,166,181,0.04); }
#mainCalendar .fc-list-day-cushion { background: #f9fafb; font-size: 0.8rem; }
#mainCalendar .fc-list-event-time { font-size: 0.75rem; color: #6b7280; }

/* Modal event detail */
.cal-event-detail-row {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}
.cal-event-detail-label {
    width: 80px;
    flex-shrink: 0;
    font-size: 0.76rem;
    color: #9ca3af;
    font-weight: 500;
    padding-top: 2px;
}
.cal-event-detail-val {
    font-size: 0.875rem;
    color: #1f2937;
    font-weight: 500;
    flex: 1;
}
</style>

<div class="cal-page-wrap">
    <!-- Top Bar -->
    <div class="cal-topbar">
        <div class="cal-title-group">
            <h5><i class="bi bi-calendar3 me-2" style="color:var(--color-moonstone)"></i>Kalender Reservasi</h5>
            <p>Semua jadwal Approved &amp; Pending di seluruh resource</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- View Toggle -->
            <div class="cal-view-toggle d-none d-sm-flex" id="calViewToggle">
                <button class="cal-view-btn" data-view="timeGridDay" onclick="switchView('timeGridDay',this)">Hari</button>
                <button class="cal-view-btn active" data-view="timeGridWeek" onclick="switchView('timeGridWeek',this)">Minggu</button>
                <button class="cal-view-btn" data-view="dayGridMonth" onclick="switchView('dayGridMonth',this)">Bulan</button>
            </div>
            <a href="<?= BASE_URL ?>/user/reservasi_baru.php" class="btn btn-sm btn-primary rounded-pill px-3">
                <i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">Reservasi Baru</span><span class="d-sm-none">+</span>
            </a>
        </div>
    </div>

    <!-- Nav Row -->
    <div class="cal-nav-row">
        <div class="cal-nav-left">
            <button class="cal-nav-btn" id="calPrev" title="Sebelumnya"><i class="bi bi-chevron-left"></i></button>
            <button class="cal-nav-btn" id="calNext" title="Berikutnya"><i class="bi bi-chevron-right"></i></button>
            <button class="cal-today-btn" id="calToday">Hari Ini</button>
        </div>
        <div class="cal-date-label" id="calRangeLabel"></div>
    </div>

    <!-- Legend -->
    <div class="cal-legend-row">
        <span class="d-flex align-items-center gap-1">
            <span class="cal-legend-dot" style="background:#bbf7d0;border:1.5px solid #22c55e"></span>Approved
        </span>
        <span class="d-flex align-items-center gap-1">
            <span class="cal-legend-dot" style="background:#fed7aa;border:1.5px solid #f59e0b"></span>Pending
        </span>
        <span class="d-flex align-items-center gap-1">
            <span class="cal-legend-dot" style="background:#bfdbfe;border:1.5px solid #3b82f6"></span>Finished
        </span>
        <span class="d-flex align-items-center gap-1 ms-2">
            <span class="cal-legend-dot" style="background:#bbf7d0;border:2px solid #22c55e;outline:2px solid #166534;outline-offset:0"></span>Reservasi Anda (lebih pekat)
        </span>
        <span class="text-muted ms-auto d-none d-md-flex align-items-center gap-1"><i class="bi bi-cursor"></i>Klik event untuk detail</span>
    </div>

    <!-- Calendar Body -->
    <div style="padding:0.75rem 0.5rem">
        <div id="mainCalendar"></div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="calEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content" style="border-radius:16px;border:none">
            <div class="modal-header" style="border-bottom:1px solid #f3f4f6;padding:1rem 1.25rem 0.75rem">
                <div>
                    <p class="text-muted mb-0" style="font-size:0.72rem;font-weight:500;letter-spacing:0.05em;text-transform:uppercase">Detail Reservasi</p>
                    <h6 class="fw-bold mb-0" id="calModalTitle" style="font-size:1rem">—</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" style="margin-top:-1.5rem"></button>
            </div>
            <div class="modal-body" style="padding:1rem 1.25rem">
                <div class="d-flex flex-column gap-3">
                    <div class="cal-event-detail-row">
                        <span class="cal-event-detail-label">Status</span>
                        <span id="calModalStatus"></span>
                    </div>
                    <div class="cal-event-detail-row">
                        <span class="cal-event-detail-label">Resource</span>
                        <span class="cal-event-detail-val" id="calModalResource"></span>
                    </div>
                    <div class="cal-event-detail-row">
                        <span class="cal-event-detail-label">Waktu</span>
                        <span class="cal-event-detail-val" id="calModalTime"></span>
                    </div>
                    <div class="cal-event-detail-row">
                        <span class="cal-event-detail-label">Keperluan</span>
                        <span class="cal-event-detail-val" id="calModalKeperluan"></span>
                    </div>
                    <div class="cal-event-detail-row">
                        <span class="cal-event-detail-label">Peminjam</span>
                        <span class="cal-event-detail-val" id="calModalPeminjam"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #f3f4f6;padding:0.75rem 1.25rem">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var isMobile = window.innerWidth < 768;
    var calEl    = document.getElementById('mainCalendar');
    var cal      = new FullCalendar.Calendar(calEl, {
        initialView: isMobile ? 'listWeek' : 'timeGridWeek',
        headerToolbar: false,        // we use our own nav
        locale:    'id',
        height:    isMobile ? 'auto' : 680,
        firstDay:  1,
        slotMinTime: '06:00:00',
        slotMaxTime: '22:00:00',
        allDaySlot: false,
        nowIndicator: true,
        eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        eventMaxStack:  3,          // cap horizontal stacking; excess shows "+N more"
        eventMinHeight: 44,         // min px height so short bookings stay readable
        expandRows:     true,       // fill available vertical space evenly
        events: '<?= BASE_URL ?>/user/calendar.php?api=events',
        eventDidMount: function (info) {
            if (info.event.extendedProps.isOwn) {
                info.el.style.fontWeight = '700';
                info.el.style.filter     = 'brightness(0.88)';
            }
        },
        datesSet: function (info) {
            updateRangeLabel(info.start, info.end, info.view.type);
            syncViewToggle(info.view.type);
        },
        eventClick: function (info) {
            var p     = info.event.extendedProps;
            var start = info.event.start;
            var end   = info.event.end;
            var dOpts = { weekday:'long', day:'numeric', month:'long', year:'numeric' };
            var tOpts = { hour:'2-digit', minute:'2-digit', hour12:false };

            document.getElementById('calModalTitle').textContent    = 'Reservasi #' + info.event.id;
            document.getElementById('calModalResource').textContent = p.tipe + ' — ' + p.resource;
            document.getElementById('calModalKeperluan').textContent = p.keperluan;
            document.getElementById('calModalPeminjam').textContent  = p.peminjam + (p.isOwn ? ' (Anda)' : '');
            document.getElementById('calModalTime').textContent =
                start.toLocaleDateString('id-ID', dOpts) + '\n' +
                start.toLocaleTimeString('id-ID', tOpts) +
                (end ? ' – ' + end.toLocaleTimeString('id-ID', tOpts) : '');

            // Status badge using res-badge classes
            var badgeMap = {
                'Approved': ['rb-approved','bi-check-circle'],
                'Pending':  ['rb-pending', 'bi-hourglass-split'],
                'Finished': ['rb-finished','bi-check2-all'],
                'Rejected': ['rb-rejected','bi-x-circle'],
                'Cancelled':['rb-cancelled','bi-slash-circle'],
            };
            var bm  = badgeMap[p.status] || ['rb-cancelled','bi-circle'];
            var sEl = document.getElementById('calModalStatus');
            sEl.className   = 'res-badge ' + bm[0];
            sEl.innerHTML   = '<i class="bi ' + bm[1] + ' res-badge-icon"></i>' + p.status;

            new bootstrap.Modal(document.getElementById('calEventModal')).show();
        }
    });
    cal.render();

    // Custom nav buttons
    document.getElementById('calPrev').addEventListener('click', function () { cal.prev(); });
    document.getElementById('calNext').addEventListener('click', function () { cal.next(); });
    document.getElementById('calToday').addEventListener('click', function () { cal.today(); });

    function switchView(view, btn) {
        cal.changeView(view);
        syncViewToggle(view);
    }
    window.switchView = switchView;

    function syncViewToggle(viewType) {
        document.querySelectorAll('.cal-view-btn').forEach(function (b) {
            b.classList.toggle('active', b.dataset.view === viewType);
        });
    }

    function updateRangeLabel(start, end, viewType) {
        var opts = { day:'numeric', month:'short', year:'numeric' };
        var mOpts = { month:'long', year:'numeric' };
        var label = '';
        if (viewType === 'dayGridMonth') {
            label = start.toLocaleDateString('id-ID', mOpts);
        } else if (viewType === 'timeGridDay') {
            label = start.toLocaleDateString('id-ID', opts);
        } else {
            // week / list
            var endAdj = new Date(end); endAdj.setDate(endAdj.getDate() - 1);
            label = start.toLocaleDateString('id-ID', opts) + ' – ' + endAdj.toLocaleDateString('id-ID', opts);
        }
        document.getElementById('calRangeLabel').textContent = label;
    }

    // Responsive: switch to listWeek on mobile
    window.addEventListener('resize', function () {
        var mobile = window.innerWidth < 768;
        var cur    = cal.view.type;
        if (mobile && cur !== 'listWeek') {
            cal.changeView('listWeek');
            cal.setOption('height', 'auto');
        } else if (!mobile && cur === 'listWeek') {
            cal.changeView('timeGridWeek');
            cal.setOption('height', 680);
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
