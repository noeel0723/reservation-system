<?php
/**
 * Admin - Kelola Waitlist / Queue
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/log_helper.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireAdmin();

$pageTitle = 'Waitlist Queue';

expireOldWaitlist($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $wid    = (int)($_POST['waitlist_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $ok = false;
    $msg = 'Invalid action.';

    if ($wid > 0 && $action === 'cancel_admin') {
        $pdo->prepare("UPDATE waitlist SET status = 'Cancelled' WHERE id = :id")->execute([':id' => $wid]);
        logActivity($pdo, 'cancel', 'waitlist', $wid, "Admin cancelled queue entry #$wid.");
        $ok = true;
        $msg = 'Queue entry cancelled successfully.';
    }

    $isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'message' => $msg]);
        exit;
    }

    setFlash($ok ? 'success' : 'error', $msg);
    header('Location: ' . BASE_URL . '/admin/waitlist.php');
    exit;
}

$filterStatus = in_array($_GET['status'] ?? '', ['Waiting', 'Notified', 'Converted', 'Expired', 'Cancelled'], true)
    ? $_GET['status'] : '';

$allEntries = getWaitlistEntries($pdo, null, $filterStatus);
$statusCounts = $pdo->query("SELECT status, COUNT(*) AS cnt FROM waitlist GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$timelineByEntry = [];
$timelineStmt = $pdo->prepare(
    "SELECT action, description, user_nama, created_at
     FROM activity_logs
     WHERE (entity_type = 'waitlist' AND entity_id = :wid)
        OR (entity_type = 'reservation' AND description LIKE :hint)
     ORDER BY created_at ASC
     LIMIT 25"
);

foreach ($allEntries as $entry) {
    $wid = (int)$entry['id'];
    $timelineStmt->execute([
        ':wid' => $wid,
        ':hint' => '%antrian #' . $wid . '%',
    ]);
    $events = $timelineStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    if (empty($events)) {
        $events[] = [
            'action' => 'create',
            'description' => 'Queue entry created.',
            'user_nama' => $entry['user_nama'] ?? '-',
            'created_at' => $entry['created_at'] ?? date('Y-m-d H:i:s'),
        ];
    }

    $timelineByEntry[$wid] = $events;
}

$flashSuccess = getFlash('success');
$flashError = getFlash('error');

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';

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
?>

<style>
.qx-title { font-size: 1.16rem; font-weight: 700; color: #172230; }
.qx-subtitle { font-size: .79rem; color: #6b7d8d; }
.qx-shell { border: 1px solid #d7e1ea; border-radius: 14px; background: #ffffff; }
.qx-toolbar { border: 1px solid #e1e8ef; border-radius: 12px; background: #f8fbfe; padding: .55rem; }
.qx-tool-input { border: 1px solid #dce6ef; border-radius: 10px; background: #fff; font-size: .82rem; }
.qx-tool-select { border: 1px solid #dce6ef; border-radius: 10px; font-size: .82rem; }
.qx-stat-card { border: 1px solid #dce7f0; border-radius: 14px; background: #fff; }
.qx-stat-label { font-size: .76rem; color: #6b7d8d; }
.qx-stat-value { font-size: 1.3rem; font-weight: 700; line-height: 1; color: #213344; }
.qx-card { border: 1px solid #dce7f0; border-radius: 14px; background: #fff; }
.qx-click-hint { font-size: .72rem; color: #8a9aab; }
.qx-row-muted { font-size: .76rem; color: #6f8091; }
.qx-row-actions .btn { border-radius: 8px; }
.qx-status-pending { background: #fff8e8; color: #915d00; border: 1px solid #f8d17a; }
.qx-drawer-meta { font-size: .8rem; color: #667a8c; }
@media (max-width: 767px) {
    .wl-board { padding: .75rem; border-radius: 14px; }
}
</style>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
    <?= htmlspecialchars($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
    <?= htmlspecialchars($flashError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="wl-board">
    <div class="qx-shell p-3">
    <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap mb-3">
        <div>
            <div class="qx-title">Waitlist Queue</div>
            <div class="qx-subtitle">Click any row to open quick details in the side panel.</div>
        </div>
    </div>

    <div class="qx-toolbar d-flex align-items-center gap-2 flex-wrap mb-3">
        <div class="input-group input-group-sm search-pill-group" style="max-width:330px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="queueQuickSearch" class="form-control search-pill-input" placeholder="Search user, resource, need...">
        </div>
        <select id="queueSortSelect" class="form-select form-select-sm qx-tool-select" style="max-width:180px">
            <option value="newest">Newest</option>
            <option value="oldest">Oldest</option>
        </select>
        <div class="ms-auto text-muted" style="font-size:.75rem"><i class="bi bi-funnel me-1"></i>Quick local filter</div>
    </div>

    <div class="row g-3 mb-3">
        <?php
        $summary = [
            ['Waiting', 'Waiting', 'bi-hourglass-split', '#fff8e8', '#915d00'],
            ['Notified', 'Notified', 'bi-bell-fill', '#ecfdf3', '#15803d'],
            ['Converted', 'Converted', 'bi-check2-all', '#eff6ff', '#1d4ed8'],
            ['Expired', 'Expired', 'bi-clock-history', '#f4f5f7', '#4b5563'],
        ];
        foreach ($summary as [$key, $label, $icon, $bg, $color]):
            $count = (int)($statusCounts[$key] ?? 0);
        ?>
        <div class="col-6 col-lg-3">
            <div class="qx-stat-card p-3 h-100">
                <div class="d-flex align-items-center gap-2">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-2" style="width:36px;height:36px;background:<?= $bg ?>;color:<?= $color ?>;font-size:1.05rem">
                        <i class="bi <?= $icon ?>"></i>
                    </div>
                    <div>
                        <div class="qx-stat-value"><?= $count ?></div>
                        <div class="qx-stat-label"><?= $label ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php
            $pills = [
                '' => 'All',
                'Waiting' => 'Waiting',
                'Notified' => 'Notified',
                'Converted' => 'Converted',
                'Expired' => 'Expired',
                'Cancelled' => 'Cancelled',
            ];
            foreach ($pills as $k => $label):
            ?>
            <a href="?status=<?= urlencode($k) ?>" class="wl-pill <?= $filterStatus === $k ? 'active' : '' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="qx-click-hint"><i class="bi bi-cursor me-1"></i>Quick detail drawer enabled</div>
    </div>

    <div id="queueTableWrap" class="wl-surface overflow-hidden d-none d-lg-block">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.83rem">
                <thead class="wl-soft-header">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Resource</th>
                        <th>Schedule</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($allEntries)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.35"></i>
                            No queue entries available.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($allEntries as $e): ?>
                        <?php
                        $eid = (int)$e['id'];
                        $resource = htmlspecialchars($e['resource_nama']);
                        $need = htmlspecialchars($e['keperluan']);
                        $userName = htmlspecialchars($e['user_nama']);
                        $userJob = htmlspecialchars((string)($e['jabatan'] ?? '-'));
                        $scheduleText = date('d M Y H:i', strtotime($e['waktu_mulai'])) . ' - ' . date('H:i', strtotime($e['waktu_selesai']));
                        ?>
                        <tr class="wl-row-click" data-open-drawer="1" data-entry-id="<?= $eid ?>" data-entry-user="<?= $userName ?>" data-entry-job="<?= $userJob ?>" data-entry-resource="<?= $resource ?>" data-entry-type="<?= htmlspecialchars($e['resource_tipe']) ?>" data-entry-schedule="<?= htmlspecialchars($scheduleText) ?>" data-entry-need="<?= $need ?>" data-entry-status="<?= htmlspecialchars($e['status']) ?>" data-entry-created="<?= htmlspecialchars(date('d M Y H:i', strtotime($e['created_at']))) ?>" data-search-text="<?= strtolower($userName . ' ' . $resource . ' ' . $need . ' ' . $e['status']) ?>" data-created-ts="<?= strtotime($e['created_at']) ?>">
                            <td class="text-muted">#<?= $eid ?></td>
                            <td>
                                <div class="fw-semibold"><?= $userName ?></div>
                                <div class="qx-row-muted"><?= $userJob ?></div>
                            </td>
                            <td>
                                <span class="badge rounded-pill" style="font-size:.67rem;background:<?= $e['resource_tipe'] === 'Studio' ? '#e0f2fe;color:#0369a1' : '#fef9c3;color:#854d0e' ?>">
                                    <?= htmlspecialchars($e['resource_tipe']) ?>
                                </span>
                                <div><?= $resource ?></div>
                            </td>
                            <td style="white-space:nowrap">
                                <?= date('d/m/Y', strtotime($e['waktu_mulai'])) ?><br>
                                <span class="qx-row-muted"><?= date('H:i', strtotime($e['waktu_mulai'])) ?> - <?= date('H:i', strtotime($e['waktu_selesai'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars(mb_strimwidth($e['keperluan'], 0, 42, '...')) ?></td>
                            <td class="js-status-cell" data-entry-id="<?= $eid ?>"><?= $wBadge($e['status']) ?></td>
                            <td class="qx-row-muted" style="white-space:nowrap"><?= date('d/m/Y H:i', strtotime($e['created_at'])) ?></td>
                            <td class="text-end qx-row-actions" onclick="event.stopPropagation()">
                                <?php if (in_array($e['status'], ['Waiting', 'Notified'], true)): ?>
                                <form method="POST" class="d-inline js-optimistic-form" data-entry-id="<?= $eid ?>" data-success-message="Queue entry cancelled successfully.">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="action" value="cancel_admin">
                                    <input type="hidden" name="waitlist_id" value="<?= $eid ?>">
                                    <button type="submit" class="btn btn-sm" style="background:#fff0f0;color:#dc3545;border:1px solid #f4c8cf">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="queueCardWrap" class="d-lg-none">
        <div class="row g-3">
            <?php if (empty($allEntries)): ?>
                <div class="col-12">
                    <div class="qx-card p-4 text-center text-muted">
                        <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.35"></i>
                        No queue entries available.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($allEntries as $e): ?>
                    <?php
                    $eid = (int)$e['id'];
                    $resource = htmlspecialchars($e['resource_nama']);
                    $need = htmlspecialchars($e['keperluan']);
                    $userName = htmlspecialchars($e['user_nama']);
                    $userJob = htmlspecialchars((string)($e['jabatan'] ?? '-'));
                    $scheduleText = date('d M Y H:i', strtotime($e['waktu_mulai'])) . ' - ' . date('H:i', strtotime($e['waktu_selesai']));
                    ?>
                    <div class="col-12 col-md-6">
                        <div class="qx-card p-3 wl-row-click" data-open-drawer="1" data-entry-id="<?= $eid ?>" data-entry-user="<?= $userName ?>" data-entry-job="<?= $userJob ?>" data-entry-resource="<?= $resource ?>" data-entry-type="<?= htmlspecialchars($e['resource_tipe']) ?>" data-entry-schedule="<?= htmlspecialchars($scheduleText) ?>" data-entry-need="<?= $need ?>" data-entry-status="<?= htmlspecialchars($e['status']) ?>" data-entry-created="<?= htmlspecialchars(date('d M Y H:i', strtotime($e['created_at']))) ?>" data-search-text="<?= strtolower($userName . ' ' . $resource . ' ' . $need . ' ' . $e['status']) ?>" data-created-ts="<?= strtotime($e['created_at']) ?>">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <div>
                                    <div class="fw-semibold">#<?= $eid ?> - <?= $userName ?></div>
                                    <div class="qx-row-muted"><?= $userJob ?></div>
                                </div>
                                <div class="js-status-cell" data-entry-id="<?= $eid ?>"><?= $wBadge($e['status']) ?></div>
                            </div>
                            <div class="qx-row-muted mb-1"><i class="bi bi-hdd-stack me-1"></i><?= htmlspecialchars($e['resource_tipe']) ?> - <?= $resource ?></div>
                            <div class="qx-row-muted mb-1"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($scheduleText) ?></div>
                            <div class="qx-row-muted mb-3"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars(mb_strimwidth($e['keperluan'], 0, 72, '...')) ?></div>
                            <div onclick="event.stopPropagation()">
                                <?php if (in_array($e['status'], ['Waiting', 'Notified'], true)): ?>
                                <form method="POST" class="js-optimistic-form" data-entry-id="<?= $eid ?>" data-success-message="Queue entry cancelled successfully.">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="action" value="cancel_admin">
                                    <input type="hidden" name="waitlist_id" value="<?= $eid ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                                        <i class="bi bi-x-lg me-1"></i>Cancel Queue
                                    </button>
                                </form>
                                <?php else: ?>
                                <div class="text-muted small">No actions available.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="queueDetailDrawer" aria-labelledby="queueDetailDrawerLabel" style="width:min(440px,92vw)">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="queueDetailDrawerLabel">Queue Detail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-3">
            <div class="qx-row-muted mb-1">User</div>
            <div class="fw-semibold" id="qdUser">-</div>
            <div class="qx-row-muted" id="qdJob">-</div>
        </div>
        <div class="mb-3">
            <div class="qx-row-muted mb-1">Resource</div>
            <div class="fw-semibold" id="qdResource">-</div>
            <div class="qx-row-muted" id="qdType">-</div>
        </div>
        <div class="mb-3">
            <div class="qx-row-muted mb-1">Schedule</div>
            <div class="fw-semibold" id="qdSchedule">-</div>
        </div>
        <div class="mb-3">
            <div class="qx-row-muted mb-1">Purpose</div>
            <div id="qdNeed">-</div>
        </div>
        <div class="mb-3">
            <div class="qx-row-muted mb-1">Status</div>
            <div id="qdStatus">-</div>
        </div>
        <div class="mb-4">
            <div class="qx-row-muted mb-1">Created</div>
            <div class="qx-drawer-meta" id="qdCreated">-</div>
        </div>
        <h6 class="fw-semibold mb-2">Activity Timeline</h6>
        <ul class="wl-timeline" id="qdTimeline"></ul>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
    <div id="waitlistToast" class="toast align-items-center text-bg-dark border-0" role="status" aria-live="polite" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="waitlistToastBody">Processing action...</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
(function () {
    var timelineData = <?= json_encode($timelineByEntry, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var drawerEl = document.getElementById('queueDetailDrawer');
    var drawer = drawerEl ? new bootstrap.Offcanvas(drawerEl) : null;
    var toastEl = document.getElementById('waitlistToast');
    var toast = toastEl ? new bootstrap.Toast(toastEl, { delay: 1800 }) : null;

    function showToast(message) {
        if (!toastEl || !toast) return;
        document.getElementById('waitlistToastBody').textContent = message;
        toast.show();
    }

    function statusHtmlProcessing() {
        return '<span class="res-badge qx-status-pending"><i class="bi bi-arrow-repeat res-badge-icon"></i>Processing</span>';
    }

    function applySearchAndSort() {
        var keyword = (document.getElementById('queueQuickSearch')?.value || '').toLowerCase().trim();
        var sort = document.getElementById('queueSortSelect')?.value || 'newest';

        var tableRows = Array.from(document.querySelectorAll('#queueTableWrap tbody tr.wl-row-click'));
        var cardCols = Array.from(document.querySelectorAll('#queueCardWrap .col-12.col-md-6'));

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
        if (action === 'create') return 'Created';
        if (action === 'cancel') return 'Cancelled';
        if (action === 'update') return 'Updated';
        if (action === 'approved') return 'Approved';
        if (action === 'rejected') return 'Rejected';
        return action;
    }

    function renderTimeline(entryId) {
        var list = document.getElementById('qdTimeline');
        if (!list) return;

        var events = timelineData[String(entryId)] || [];
        if (!events.length) {
            list.innerHTML = '<li>No activity timeline yet.</li>';
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

    document.querySelectorAll('[data-open-drawer="1"]').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('button, a, form, input')) return;
            var id = row.dataset.entryId || '';
            document.getElementById('qdUser').textContent = row.dataset.entryUser || '-';
            document.getElementById('qdJob').textContent = row.dataset.entryJob || '-';
            document.getElementById('qdResource').textContent = row.dataset.entryResource || '-';
            document.getElementById('qdType').textContent = row.dataset.entryType || '-';
            document.getElementById('qdSchedule').textContent = row.dataset.entrySchedule || '-';
            document.getElementById('qdNeed').textContent = row.dataset.entryNeed || '-';
            document.getElementById('qdStatus').innerHTML = document.querySelector('.js-status-cell[data-entry-id="' + id + '"]')?.innerHTML || row.dataset.entryStatus || '-';
            document.getElementById('qdCreated').textContent = row.dataset.entryCreated || '-';
            renderTimeline(id);
            if (drawer) drawer.show();
        });
    });

    var searchInput = document.getElementById('queueQuickSearch');
    var sortSelect = document.getElementById('queueSortSelect');
    if (searchInput) searchInput.addEventListener('input', applySearchAndSort);
    if (sortSelect) sortSelect.addEventListener('change', applySearchAndSort);

    applySearchAndSort();

    document.querySelectorAll('.js-optimistic-form').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var id = form.dataset.entryId;
            var statusNodes = document.querySelectorAll('.js-status-cell[data-entry-id="' + id + '"]');
            var original = [];

            statusNodes.forEach(function (node, i) {
                original[i] = node.innerHTML;
                node.innerHTML = statusHtmlProcessing();
            });

            var buttons = form.querySelectorAll('button');
            buttons.forEach(function (b) { b.disabled = true; });
            showToast('Saving changes...');

            fetch(form.getAttribute('action') || window.location.href, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (res) {
                return res.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'Failed to process action.');
                }
                showToast(data.message || form.dataset.successMessage || 'Action completed successfully.');
                setTimeout(function () { window.location.reload(); }, 420);
            }).catch(function (err) {
                statusNodes.forEach(function (node, i) {
                    node.innerHTML = original[i] || node.innerHTML;
                });
                buttons.forEach(function (b) { b.disabled = false; });
                showToast(err.message || 'An error occurred, changes were rolled back.');
            });
        });
    });
})();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>