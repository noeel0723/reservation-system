<?php
/**
 * User - My Waitlist
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/log_helper.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireStaff();

$pageTitle = 'My Waitlist';
$userId = (int)$_SESSION['user_id'];

expireOldWaitlist($pdo);

$flashSuccess = getFlash('success');
$flashError = getFlash('error');
$entries = getWaitlistEntries($pdo, $userId);

$timelineByEntry = [];
$timelineStmt = $pdo->prepare(
    "SELECT action, description, user_nama, created_at
     FROM activity_logs
     WHERE (entity_type = 'waitlist' AND entity_id = :wid)
        OR (entity_type = 'reservation' AND description LIKE :hint)
     ORDER BY created_at ASC
     LIMIT 25"
);

foreach ($entries as $entry) {
    $wid = (int)$entry['id'];
    $timelineStmt->execute([
        ':wid' => $wid,
        ':hint' => '%antrian #' . $wid . '%',
    ]);
    $events = $timelineStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (empty($events)) {
        $events[] = [
            'action' => 'create',
            'description' => 'Anda mendaftar antrian.',
            'user_nama' => $entry['user_nama'] ?? '-',
            'created_at' => $entry['created_at'] ?? date('Y-m-d H:i:s'),
        ];
    }

    $timelineByEntry[$wid] = $events;
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_user.php';

$wBadge = static function (string $s): string {
    $map = [
        'Waiting' => 'rb-pending',
        'Notified' => 'rb-approved',
        'Converted' => 'rb-finished',
        'Expired' => 'rb-cancelled',
        'Cancelled' => 'rb-cancelled',
    ];
    $icon = [
        'Waiting' => 'bi-hourglass-split',
        'Notified' => 'bi-bell-fill',
        'Converted' => 'bi-check2-all',
        'Expired' => 'bi-clock-history',
        'Cancelled' => 'bi-slash-circle',
    ];
    $cls = $map[$s] ?? 'rb-cancelled';
    $ic = $icon[$s] ?? 'bi-circle';
    return "<span class=\"res-badge {$cls}\"><i class=\"bi {$ic} res-badge-icon\"></i>{$s}</span>";
};

$notifiedCount = count(array_filter($entries, static fn($e) => $e['status'] === 'Notified'));
?>

<style>
.mw-headline { font-size: 1.16rem; font-weight: 700; color: #172230; }
.mw-subline { font-size: .8rem; color: #6b7d8d; }
.mw-shell { border: 1px solid #d7e1ea; border-radius: 14px; background: #ffffff; }
.mw-toolbar { border: 1px solid #e1e8ef; border-radius: 12px; background: #f8fbfe; padding: .55rem; }
.mw-tool-input { border: 1px solid #dce6ef; border-radius: 10px; background: #fff; font-size: .82rem; }
.mw-tool-select { border: 1px solid #dce6ef; border-radius: 10px; font-size: .82rem; }
.mw-card { border: 1px solid #dce7f0; border-radius: 14px; background: #fff; }
.mw-meta { font-size: .76rem; color: #6c8091; }
.mw-status { font-size: .72rem; color: #8ca0af; }
.mw-notify { background: #ecfdf3; border: 1px solid #c7f0d9; border-radius: 12px; }
.mw-empty { border: 1px dashed #c4d3e0; border-radius: 14px; background: #f7fbff; }
.qx-status-pending { background: #fff8e8; color: #915d00; border: 1px solid #f8d17a; }
</style>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($flashError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="wl-board">
    <div class="mw-shell p-3">
    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-3">
        <div>
            <div class="mw-headline">My Waitlist</div>
            <div class="mw-subline">Tampilan ringkas antrian Anda dengan quick detail drawer.</div>
        </div>
        <div class="btn-group btn-group-sm wl-view-toggle" role="group" aria-label="View mode">
            <button type="button" class="btn btn-outline-secondary" data-view="table"><i class="bi bi-table me-1"></i>Table</button>
            <button type="button" class="btn btn-outline-secondary" data-view="cards"><i class="bi bi-grid me-1"></i>Cards</button>
        </div>
    </div>

    <div class="mw-toolbar d-flex align-items-center gap-2 flex-wrap mb-3">
        <div class="input-group input-group-sm" style="max-width:300px">
            <span class="input-group-text mw-tool-input"><i class="bi bi-search"></i></span>
            <input type="text" id="myWaitQuickSearch" class="form-control mw-tool-input" placeholder="Search resource, need, status...">
        </div>
        <select id="myWaitSortSelect" class="form-select form-select-sm mw-tool-select" style="max-width:180px">
            <option value="newest">Newest</option>
            <option value="oldest">Oldest</option>
        </select>
        <div class="ms-auto text-muted" style="font-size:.75rem"><i class="bi bi-funnel me-1"></i>Quick filter lokal</div>
    </div>

    <?php if ($notifiedCount > 0): ?>
    <div class="mw-notify p-3 mb-3 d-flex align-items-start gap-3">
        <i class="bi bi-bell-fill mt-1" style="color:#15803d"></i>
        <div>
            <div class="fw-semibold" style="color:#166534">Ada <?= $notifiedCount ?> antrian siap dikonversi.</div>
            <div class="small text-muted">Klik tombol "Buat Reservasi" agar slot tidak diambil pengguna lain.</div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($entries)): ?>
        <div class="mw-empty p-4 text-center">
            <i class="bi bi-inbox d-block mb-2" style="font-size:2.2rem;color:#96abc0"></i>
            <div class="fw-semibold text-muted mb-1">Belum ada antrian</div>
            <div class="small text-muted mb-3">Jika jadwal penuh, Anda bisa menambahkan antrian dari halaman New Reservation.</div>
            <a href="<?= BASE_URL ?>/user/reservasi_baru.php" class="btn btn-primary rounded-pill px-4">
                <i class="bi bi-plus-lg me-1"></i>New Reservation
            </a>
        </div>
    <?php else: ?>
        <div id="myWaitTableWrap" class="wl-surface overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size:.83rem">
                    <thead class="wl-soft-header">
                        <tr>
                            <th>Resource</th>
                            <th>Jadwal</th>
                            <th>Keperluan</th>
                            <th>Status</th>
                            <th>Didaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($entries as $e): ?>
                        <?php
                        $eid = (int)$e['id'];
                        $resource = htmlspecialchars($e['resource_nama']);
                        $need = htmlspecialchars($e['keperluan']);
                        $scheduleText = date('d M Y H:i', strtotime($e['waktu_mulai'])) . ' - ' . date('H:i', strtotime($e['waktu_selesai']));
                        ?>
                        <tr class="wl-row-click" data-open-drawer="1" data-entry-id="<?= $eid ?>" data-entry-resource="<?= $resource ?>" data-entry-type="<?= htmlspecialchars($e['resource_tipe']) ?>" data-entry-schedule="<?= htmlspecialchars($scheduleText) ?>" data-entry-need="<?= $need ?>" data-entry-status="<?= htmlspecialchars($e['status']) ?>" data-entry-created="<?= htmlspecialchars(date('d M Y H:i', strtotime($e['created_at']))) ?>" data-search-text="<?= strtolower($resource . ' ' . $need . ' ' . $e['status']) ?>" data-created-ts="<?= strtotime($e['created_at']) ?>">
                            <td>
                                <span class="badge rounded-pill me-1" style="font-size:.65rem;background:<?= $e['resource_tipe'] === 'Studio' ? '#e0f2fe;color:#0369a1' : '#fef9c3;color:#854d0e' ?>">
                                    <?= htmlspecialchars($e['resource_tipe']) ?>
                                </span>
                                <?= $resource ?>
                            </td>
                            <td style="white-space:nowrap">
                                <?= date('d/m/Y', strtotime($e['waktu_mulai'])) ?><br>
                                <span class="mw-meta"><?= date('H:i', strtotime($e['waktu_mulai'])) ?> - <?= date('H:i', strtotime($e['waktu_selesai'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars(mb_strimwidth($e['keperluan'], 0, 42, '...')) ?></td>
                            <td class="js-status-cell" data-entry-id="<?= $eid ?>"><?= $wBadge($e['status']) ?></td>
                            <td class="mw-meta" style="white-space:nowrap"><?= date('d/m/Y H:i', strtotime($e['created_at'])) ?></td>
                            <td class="text-end" onclick="event.stopPropagation()">
                                <div class="d-flex justify-content-end gap-1 flex-wrap">
                                    <?php if ($e['status'] === 'Notified'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="js-optimistic-form" data-entry-id="<?= $eid ?>">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="convert_waitlist">
                                        <input type="hidden" name="waitlist_id" value="<?= $eid ?>">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-check-circle me-1"></i>Buat Reservasi
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (in_array($e['status'], ['Waiting', 'Notified'], true)): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="js-optimistic-form" data-entry-id="<?= $eid ?>">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="cancel_waitlist">
                                        <input type="hidden" name="waitlist_id" value="<?= $eid ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
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

        <div id="myWaitCardWrap" class="d-none">
            <div class="row g-3">
                <?php foreach ($entries as $e): ?>
                    <?php
                    $eid = (int)$e['id'];
                    $resource = htmlspecialchars($e['resource_nama']);
                    $need = htmlspecialchars($e['keperluan']);
                    $scheduleText = date('d M Y H:i', strtotime($e['waktu_mulai'])) . ' - ' . date('H:i', strtotime($e['waktu_selesai']));
                    ?>
                    <div class="col-12 col-md-6">
                        <div class="mw-card p-3 wl-row-click" data-open-drawer="1" data-entry-id="<?= $eid ?>" data-entry-resource="<?= $resource ?>" data-entry-type="<?= htmlspecialchars($e['resource_tipe']) ?>" data-entry-schedule="<?= htmlspecialchars($scheduleText) ?>" data-entry-need="<?= $need ?>" data-entry-status="<?= htmlspecialchars($e['status']) ?>" data-entry-created="<?= htmlspecialchars(date('d M Y H:i', strtotime($e['created_at']))) ?>" data-search-text="<?= strtolower($resource . ' ' . $need . ' ' . $e['status']) ?>" data-created-ts="<?= strtotime($e['created_at']) ?>">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <div class="fw-semibold">#<?= $eid ?> - <?= $resource ?></div>
                                <div class="js-status-cell" data-entry-id="<?= $eid ?>"><?= $wBadge($e['status']) ?></div>
                            </div>
                            <div class="mw-meta mb-1"><i class="bi bi-hdd-stack me-1"></i><?= htmlspecialchars($e['resource_tipe']) ?></div>
                            <div class="mw-meta mb-1"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($scheduleText) ?></div>
                            <div class="mw-meta mb-3"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars(mb_strimwidth($e['keperluan'], 0, 72, '...')) ?></div>
                            <div onclick="event.stopPropagation()" class="d-flex gap-2 flex-wrap">
                                <?php if ($e['status'] === 'Notified'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="js-optimistic-form flex-grow-1" data-entry-id="<?= $eid ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="action" value="convert_waitlist">
                                    <input type="hidden" name="waitlist_id" value="<?= $eid ?>">
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="bi bi-check-circle me-1"></i>Buat Reservasi
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if (in_array($e['status'], ['Waiting', 'Notified'], true)): ?>
                                <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="js-optimistic-form flex-grow-1" data-entry-id="<?= $eid ?>">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="action" value="cancel_waitlist">
                                    <input type="hidden" name="waitlist_id" value="<?= $eid ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                        <i class="bi bi-x-lg me-1"></i>Batalkan
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="myWaitDetailDrawer" aria-labelledby="myWaitDetailDrawerLabel" style="width:min(440px,92vw)">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="myWaitDetailDrawerLabel">Waitlist Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <div class="mw-status mb-1">Resource</div>
            <div class="fw-semibold" id="mwResource">-</div>
            <div class="mw-meta" id="mwType">-</div>
        </div>
        <div class="mb-3">
            <div class="mw-status mb-1">Jadwal</div>
            <div class="fw-semibold" id="mwSchedule">-</div>
        </div>
        <div class="mb-3">
            <div class="mw-status mb-1">Keperluan</div>
            <div id="mwNeed">-</div>
        </div>
        <div class="mb-3">
            <div class="mw-status mb-1">Status</div>
            <div id="mwStatus">-</div>
        </div>
        <div class="mb-4">
            <div class="mw-status mb-1">Dibuat</div>
            <div class="mw-meta" id="mwCreated">-</div>
        </div>
        <h6 class="fw-semibold mb-2">Activity Timeline</h6>
        <ul class="wl-timeline" id="mwTimeline"></ul>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="myWaitToast" class="toast align-items-center text-bg-dark border-0" role="status" aria-live="polite" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="myWaitToastBody">Memproses aksi...</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
(function () {
    var timelineData = <?= json_encode($timelineByEntry, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var drawerEl = document.getElementById('myWaitDetailDrawer');
    var drawer = drawerEl ? new bootstrap.Offcanvas(drawerEl) : null;
    var toastEl = document.getElementById('myWaitToast');
    var toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 1900 }) : null;
    var savedMode = localStorage.getItem('user_waitlist_view_mode');

    function showToast(message) {
        if (!toastEl || !toast) return;
        document.getElementById('myWaitToastBody').textContent = message;
        toast.show();
    }

    function applyView(mode, persist) {
        var tableWrap = document.getElementById('myWaitTableWrap');
        var cardWrap = document.getElementById('myWaitCardWrap');
        if (!tableWrap || !cardWrap) return;

        tableWrap.classList.toggle('d-none', mode !== 'table');
        cardWrap.classList.toggle('d-none', mode !== 'cards');

        document.querySelectorAll('.wl-view-toggle [data-view]').forEach(function (btn) {
            btn.classList.toggle('btn-dark', btn.dataset.view === mode);
            btn.classList.toggle('text-white', btn.dataset.view === mode);
            btn.classList.toggle('btn-outline-secondary', btn.dataset.view !== mode);
        });

        if (persist) {
            localStorage.setItem('user_waitlist_view_mode', mode);
            savedMode = mode;
        }
    }

    function applySearchAndSort() {
        var keyword = (document.getElementById('myWaitQuickSearch')?.value || '').toLowerCase().trim();
        var sort = document.getElementById('myWaitSortSelect')?.value || 'newest';

        var tableRows = Array.from(document.querySelectorAll('#myWaitTableWrap tbody tr.wl-row-click'));
        var cardCols = Array.from(document.querySelectorAll('#myWaitCardWrap .col-12.col-md-6'));

        tableRows.forEach(function (row) {
            var text = row.dataset.searchText || '';
            var show = !keyword || text.indexOf(keyword) !== -1;
            row.classList.toggle('d-none', !show);
        });

        cardCols.forEach(function (col) {
            var card = col.querySelector('.wl-row-click');
            var text = card ? (card.dataset.searchText || '') : '';
            var show = !keyword || text.indexOf(keyword) !== -1;
            col.classList.toggle('d-none', !show);
        });

        var order = sort === 'oldest' ? 1 : -1;
        tableRows.sort(function (a, b) {
            return ((Number(a.dataset.createdTs || 0) - Number(b.dataset.createdTs || 0)) * order);
        }).forEach(function (row) {
            row.parentNode.appendChild(row);
        });

        cardCols.sort(function (a, b) {
            var aCard = a.querySelector('.wl-row-click');
            var bCard = b.querySelector('.wl-row-click');
            var diff = Number((aCard && aCard.dataset.createdTs) || 0) - Number((bCard && bCard.dataset.createdTs) || 0);
            return diff * order;
        }).forEach(function (col) {
            col.parentNode.appendChild(col);
        });
    }

    function normalizeActionLabel(action) {
        if (action === 'create') return 'Dibuat';
        if (action === 'cancel') return 'Dibatalkan';
        if (action === 'update') return 'Diperbarui';
        if (action === 'approved') return 'Disetujui';
        if (action === 'rejected') return 'Ditolak';
        return action;
    }

    function renderTimeline(entryId) {
        var list = document.getElementById('mwTimeline');
        if (!list) return;
        var events = timelineData[String(entryId)] || [];
        if (!events.length) {
            list.innerHTML = '<li>Tidak ada riwayat aktivitas.</li>';
            return;
        }

        list.innerHTML = events.map(function (ev) {
            var title = normalizeActionLabel(String(ev.action || '-'));
            var time = String(ev.created_at || '').replace('T', ' ');
            var desc = String(ev.description || '');
            var actor = ev.user_nama ? ' - ' + ev.user_nama : '';
            return '<li><div class="fw-semibold">' + title + actor + '</div><div class="small text-muted">' + time + '</div><div>' + desc + '</div></li>';
        }).join('');
    }

    function processingBadge() {
        return '<span class="res-badge qx-status-pending"><i class="bi bi-arrow-repeat res-badge-icon"></i>Processing</span>';
    }

    document.querySelectorAll('[data-open-drawer="1"]').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('button, a, form, input')) return;
            var id = row.dataset.entryId || '';
            document.getElementById('mwResource').textContent = row.dataset.entryResource || '-';
            document.getElementById('mwType').textContent = row.dataset.entryType || '-';
            document.getElementById('mwSchedule').textContent = row.dataset.entrySchedule || '-';
            document.getElementById('mwNeed').textContent = row.dataset.entryNeed || '-';
            document.getElementById('mwStatus').innerHTML = document.querySelector('.js-status-cell[data-entry-id="' + id + '"]')?.innerHTML || row.dataset.entryStatus || '-';
            document.getElementById('mwCreated').textContent = row.dataset.entryCreated || '-';
            renderTimeline(id);
            if (drawer) drawer.show();
        });
    });

    document.querySelectorAll('.wl-view-toggle [data-view]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyView(btn.dataset.view, true);
        });
    });

    var searchInput = document.getElementById('myWaitQuickSearch');
    var sortSelect = document.getElementById('myWaitSortSelect');
    if (searchInput) searchInput.addEventListener('input', applySearchAndSort);
    if (sortSelect) sortSelect.addEventListener('change', applySearchAndSort);

    var autoMode = window.matchMedia('(max-width: 991.98px)').matches ? 'cards' : 'table';
    applyView(savedMode || autoMode, false);
    window.addEventListener('resize', function () {
        if (!savedMode) {
            var dynamic = window.matchMedia('(max-width: 991.98px)').matches ? 'cards' : 'table';
            applyView(dynamic, false);
        }
    });

    applySearchAndSort();

    document.querySelectorAll('.js-optimistic-form').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var id = form.dataset.entryId;
            var statusNodes = document.querySelectorAll('.js-status-cell[data-entry-id="' + id + '"]');
            var backup = [];

            statusNodes.forEach(function (node, i) {
                backup[i] = node.innerHTML;
                node.innerHTML = processingBadge();
            });

            var buttons = form.querySelectorAll('button');
            buttons.forEach(function (btn) { btn.disabled = true; });
            showToast('Memproses aksi...');

            fetch(form.getAttribute('action') || window.location.href, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Aksi gagal diproses.');
                }
                showToast(data.message || 'Aksi berhasil.');
                if (data.redirect) {
                    setTimeout(function () { window.location.href = data.redirect; }, 420);
                } else {
                    setTimeout(function () { window.location.reload(); }, 420);
                }
            }).catch(function (err) {
                statusNodes.forEach(function (node, i) {
                    node.innerHTML = backup[i] || node.innerHTML;
                });
                buttons.forEach(function (btn) { btn.disabled = false; });
                showToast(err.message || 'Terjadi kesalahan, perubahan dibatalkan.');
            });
        });
    });
})();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>