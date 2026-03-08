<?php
/**
 * Admin — Riwayat Log Aktivitas (Feature 5)
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/log_helper.php';

requireAdmin();

$pageTitle = 'Activity Log';

// Handle clear-old POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_old') {
    requireCsrf();
    $pdo->exec("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    setFlash('success', 'Log lebih dari 90 hari berhasil dihapus.');
    header('Location: ' . BASE_URL . '/admin/log_aktivitas.php');
    exit;
}

// Filters
$filterUser   = trim($_GET['user']   ?? '');
$filterEntity = trim($_GET['entity'] ?? '');
$filterDate   = in_array($_GET['date'] ?? '', ['today','week','month']) ? $_GET['date'] : '';
$page         = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

// Build WHERE
$where  = ['1=1'];
$params = [];

if ($filterUser !== '') {
    $where[]          = '(al.user_nama LIKE :uname OR al.description LIKE :uname2)';
    $params[':uname']  = '%' . $filterUser . '%';
    $params[':uname2'] = '%' . $filterUser . '%';
}
if ($filterEntity !== '') {
    $where[]          = 'al.entity_type = :etype';
    $params[':etype'] = $filterEntity;
}
switch ($filterDate) {
    case 'today': $where[] = 'DATE(al.created_at) = CURDATE()'; break;
    case 'week':  $where[] = 'al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'; break;
    case 'month': $where[] = 'al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'; break;
}

$whereClause = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs al WHERE $whereClause");
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$stmt = $pdo->prepare(
    "SELECT al.* FROM activity_logs al
     WHERE $whereClause
     ORDER BY al.created_at DESC
     LIMIT :lim OFFSET :off"
);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset,  PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Distinct entity types for filter dropdown
$entityTypes = $pdo->query("SELECT DISTINCT entity_type FROM activity_logs WHERE entity_type IS NOT NULL ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN);

$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';

// Helper: action badge
function actionBadge(string $action): string {
    $map = [
        'create'   => ['background:#ecfdf3;color:#047857;border-color:#a7f3d0', 'bi-plus-circle'],
        'update'   => ['background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe', 'bi-pencil'],
        'delete'   => ['background:#fef2f2;color:#b91c1c;border-color:#fecaca', 'bi-trash'],
        'approve'  => ['background:#ecfdf3;color:#047857;border-color:#a7f3d0', 'bi-check-circle'],
        'reject'   => ['background:#fef2f2;color:#b91c1c;border-color:#fecaca', 'bi-x-circle'],
        'cancel'   => ['background:#fff7ed;color:#b45309;border-color:#fed7aa', 'bi-slash-circle'],
        'selesai'  => ['background:#ecfeff;color:#0e7490;border-color:#a5f3fc', 'bi-check2-all'],
        'login'    => ['background:#faf5ff;color:#7c3aed;border-color:#e9d5ff', 'bi-box-arrow-in-right'],
        'logout'   => ['background:#f9fafb;color:#4b5563;border-color:#d1d5db', 'bi-box-arrow-right'],
    ];
    $key   = strtolower($action);
    $found = null;
    foreach ($map as $k => $v) {
        if (str_contains($key, $k)) {
            $found = $v;
            break;
        }
    }
    if (!$found) {
        $found = ['background:#f3f4f6;color:#6b7280;border-color:#e5e7eb', 'bi-activity'];
    }
    [$style, $icon] = $found;
    return "<span class=\"act-chip\" style=\"{$style}\"><i class=\"bi {$icon}\"></i>" . htmlspecialchars($action) . "</span>";
}

function statusBadge(string $action): string {
    $a = strtolower($action);
    $isFail = str_contains($a, 'reject') || str_contains($a, 'delete') || str_contains($a, 'cancel') || str_contains($a, 'fail');
    if ($isFail) {
        return '<span class="status-chip fail"><i class="bi bi-x-circle"></i>Failed</span>';
    }
    return '<span class="status-chip ok"><i class="bi bi-check-circle"></i>Success</span>';
}

function nameInitial(string $name): string {
    $name = trim($name);
    if ($name === '') {
        return 'A';
    }
    $parts = preg_split('/\s+/', $name);
    $first = strtoupper(substr($parts[0] ?? 'A', 0, 1));
    $second = '';
    if (count($parts) > 1) {
        $second = strtoupper(substr($parts[1], 0, 1));
    }
    return $first . $second;
}

$entityIcon = fn($t) => match($t) {
    'reservation' => 'bi-calendar-check',
    'resource'    => 'bi-hdd-stack',
    'user'        => 'bi-person',
    'settings'    => 'bi-gear',
    'waitlist'    => 'bi-clock-history',
    default       => 'bi-tag',
};
?>

<style>
.activity-shell {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    background: #ffffff;
    overflow: hidden;
}
.activity-top {
    padding: 14px 16px;
    border-bottom: 1px solid #f0f2f4;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.activity-metrics {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.metric-pill {
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    color: #374151;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 600;
    padding: 5px 10px;
}
.activity-filters {
    padding: 12px 16px;
    border-bottom: 1px solid #f3f4f6;
    background: #fcfcfd;
}
.filter-grid {
    display: grid;
    grid-template-columns: 140px 1fr 150px 170px 180px;
    gap: 8px;
}
.filter-grid .form-control,
.filter-grid .form-select,
.filter-grid .btn {
    height: 36px;
    font-size: 0.8rem;
}
.activity-table {
    margin: 0;
    font-size: 0.8rem;
}
.activity-table thead th {
    background: #f8fafc;
    color: #6b7280;
    font-size: 0.68rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #e5e7eb;
    padding: 10px 12px;
    white-space: nowrap;
}
.activity-table tbody td {
    padding: 11px 12px;
    vertical-align: middle;
    border-color: #f3f4f6;
}
.entity-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    padding: 4px 9px;
    color: #374151;
    font-size: 0.72rem;
    font-weight: 600;
}
.act-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border: 1px solid;
    border-radius: 999px;
    padding: 3px 9px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
}
.status-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 999px;
    padding: 3px 9px;
    font-size: 0.68rem;
    font-weight: 700;
    white-space: nowrap;
}
.status-chip.ok {
    background: #ecfdf3;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.status-chip.fail {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
.user-mini {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.user-dot {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e0f2fe;
    color: #0369a1;
    font-size: 0.62rem;
    font-weight: 700;
    flex-shrink: 0;
}
.user-name {
    max-width: 160px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.date-cell {
    color: #6b7280;
    font-size: 0.74rem;
    white-space: nowrap;
}

@media (max-width: 991px) {
    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }
    .filter-grid .filter-search,
    .filter-grid .filter-actions {
        grid-column: span 2;
    }
}
@media (max-width: 575px) {
    .activity-top,
    .activity-filters {
        padding: 12px;
    }
    .filter-grid {
        grid-template-columns: 1fr;
    }
    .filter-grid .filter-search,
    .filter-grid .filter-actions {
        grid-column: span 1;
    }
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

<div class="activity-shell">
    <div class="activity-top">
        <div>
            <h5 class="mb-0 fw-bold" style="font-size:1.15rem">Activities</h5>
            <div class="text-muted" style="font-size:0.76rem">Monitor all system changes, approvals, and user events.</div>
        </div>
        <div class="activity-metrics">
            <span class="metric-pill"><i class="bi bi-database me-1"></i><?= number_format($totalRows) ?> entries</span>
            <span class="metric-pill">Page <?= $page ?> / <?= $totalPages ?></span>
        </div>
    </div>

    <div class="activity-filters">
        <form method="POST" action="" id="clearOldForm"
              onsubmit="return confirm('Delete all logs older than 90 days? This action cannot be undone.')">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="action" value="clear_old">
        </form>

        <form method="GET" class="filter-grid" id="activityFilterForm">
            <select name="date" class="form-select">
                <option value="">All Time</option>
                <option value="today" <?= $filterDate === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="week"  <?= $filterDate === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="month" <?= $filterDate === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
            </select>

            <div class="filter-search">
                <div class="input-group input-group-sm search-pill-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="user" class="form-control search-pill-input js-auto-filter" placeholder="Search user or activity..."
                           value="<?= htmlspecialchars($filterUser) ?>">
                </div>
            </div>

            <select name="entity" class="form-select js-auto-filter-select">
                <option value="">All Types</option>
                    <?php foreach ($entityTypes as $et): ?>
                    <option value="<?= htmlspecialchars($et) ?>" <?= $filterEntity === $et ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($et)) ?>
                    </option>
                    <?php endforeach; ?>
            </select>

            <button type="submit" form="clearOldForm" class="btn btn-outline-danger w-100">
                <i class="bi bi-trash me-1"></i>Clear > 90 Days
            </button>

            <div class="filter-actions d-flex gap-2">
                <div class="btn btn-light border flex-grow-1 text-muted d-flex align-items-center justify-content-center" style="cursor:default">
                    <i class="bi bi-lightning-charge me-1"></i>Auto Filter
                </div>
                <a href="<?= BASE_URL ?>/admin/log_aktivitas.php" class="btn btn-outline-secondary" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table activity-table table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:120px">Type</th>
                    <th style="width:130px">Action</th>
                    <th style="width:110px">Status</th>
                    <th>Activity</th>
                    <th style="width:210px">User</th>
                    <th style="width:135px">Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-journal-x d-block mb-2" style="font-size:2rem;opacity:0.3"></i>
                        No activity log matches your current filters.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <?php if ($log['entity_type']): ?>
                        <span class="entity-chip">
                            <i class="bi <?= $entityIcon($log['entity_type']) ?>"></i>
                            <?= htmlspecialchars(ucfirst($log['entity_type'])) ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">System</span>
                        <?php endif; ?>
                    </td>
                    <td><?= actionBadge($log['action']) ?></td>
                    <td><?= statusBadge($log['action']) ?></td>
                    <td class="text-dark">
                        <?= htmlspecialchars($log['description']) ?>
                        <?php if (!empty($log['entity_id'])): ?>
                        <span class="text-muted" style="font-size:0.72rem">#<?= (int)$log['entity_id'] ?></span>
                        <?php endif; ?>
                        <?php if (!empty($log['ip_address'])): ?>
                        <div class="text-muted" style="font-size:0.68rem">IP: <?= htmlspecialchars($log['ip_address']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $name = $log['user_nama'] ?: 'System'; ?>
                        <span class="user-mini">
                            <span class="user-dot"><?= htmlspecialchars(nameInitial($name)) ?></span>
                            <span class="user-name" title="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></span>
                        </span>
                    </td>
                    <td class="date-cell">
                        <?= date('M d, Y', strtotime($log['created_at'])) ?><br>
                        <?= date('H:i', strtotime($log['created_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <?php
    $qBase = '?';
    if ($filterUser !== '')   $qBase .= 'user=' . urlencode($filterUser) . '&';
    if ($filterEntity !== '') $qBase .= 'entity=' . urlencode($filterEntity) . '&';
    if ($filterDate !== '')   $qBase .= 'date=' . urlencode($filterDate) . '&';
    ?>
    <div class="px-3 py-3 border-top" style="background:#fcfcfd">
        <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
            <a href="<?= $qBase ?>p=<?= max(1, $page - 1) ?>" class="kr-page-nav<?= $page <= 1 ? ' disabled' : '' ?>">
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
                foreach ($pageRange as $pItem):
                    if ($pItem === '...'):
                ?>
                    <span class="kr-page-ellipsis">...</span>
                <?php else: ?>
                    <a href="<?= $qBase ?>p=<?= $pItem ?>" class="kr-page-num<?= $pItem === $page ? ' active' : '' ?>"><?= $pItem ?></a>
                <?php
                    endif;
                endforeach;
                ?>
            </div>

            <a href="<?= $qBase ?>p=<?= min($totalPages, $page + 1) ?>" class="kr-page-nav<?= $page >= $totalPages ? ' disabled' : '' ?>">
                Next <i class="bi bi-chevron-right" style="font-size:0.7rem"></i>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var form = document.getElementById('activityFilterForm');
    if (!form) return;

    var typingTimer = null;
    var searchInput = form.querySelector('.js-auto-filter');
    var selectInputs = form.querySelectorAll('.js-auto-filter-select, select[name="date"]');

    function submitFilters() {
        var pageField = form.querySelector('input[name="p"]');
        if (pageField) pageField.remove();
        form.submit();
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(submitFilters, 450);
        });
    }

    selectInputs.forEach(function (el) {
        el.addEventListener('change', submitFilters);
    });
})();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
