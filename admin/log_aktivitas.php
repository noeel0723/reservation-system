<?php
/**
 * Admin — Riwayat Log Aktivitas (Feature 5)
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/log_helper.php';

requireAdmin();

$pageTitle = 'Log Aktivitas';

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
$perPage      = 30;
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
        'create'   => ['bg:#dcfce7;color:#15803d;border:#bbf7d0', 'bi-plus-circle'],
        'update'   => ['bg:#eff6ff;color:#1d4ed8;border:#bfdbfe', 'bi-pencil'],
        'delete'   => ['bg:#fef2f2;color:#b91c1c;border:#fecaca', 'bi-trash'],
        'approve'  => ['bg:#f0fdf4;color:#15803d;border:#bbf7d0', 'bi-check-circle'],
        'reject'   => ['bg:#fef2f2;color:#b91c1c;border:#fecaca', 'bi-x-circle'],
        'cancel'   => ['bg:#f9fafb;color:#374151;border:#e5e7eb', 'bi-slash-circle'],
        'selesai'  => ['bg:#eff6ff;color:#1d4ed8;border:#bfdbfe', 'bi-check2-all'],
        'login'    => ['bg:#faf5ff;color:#7c3aed;border:#e9d5ff', 'bi-box-arrow-in-right'],
        'logout'   => ['bg:#fff7ed;color:#b45309;border:#fed7aa', 'bi-box-arrow-right'],
    ];
    $key   = strtolower($action);
    $found = null;
    foreach ($map as $k => $v) { if (str_contains($key, $k)) { $found = $v; break; } }
    if (!$found) $found = ['bg:#f3f4f6;color:#6b7280;border:#e5e7eb', 'bi-activity'];
    [$style, $icon] = $found;
    $style = str_replace(['bg:','color:','border:'], ['background:', 'color:', 'border-color:'], $style);
    $parts = explode(';', $style);
    $css   = implode(';', $parts);
    return "<span style=\"display:inline-flex;align-items:center;gap:0.28em;font-size:0.7rem;font-weight:700;
                          text-transform:uppercase;letter-spacing:0.05em;padding:0.25em 0.65em;
                          border-radius:6px;border:1.5px solid;white-space:nowrap;{$css}\">
              <i class=\"bi {$icon}\"></i>" . htmlspecialchars($action) . "</span>";
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

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
    <?= htmlspecialchars($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Top toolbar -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="badge bg-secondary rounded-pill"><?= number_format($totalRows) ?> entri</span>
    </div>
    <form method="POST" action="" class="d-inline"
          onsubmit="return confirm('Hapus semua log lebih dari 90 hari? Tindakan ini tidak bisa dibatalkan.')">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="action" value="clear_old">
        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
            <i class="bi bi-trash me-1"></i><span class="d-none d-sm-inline">Hapus Log >90 Hari</span>
        </button>
    </form>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px">
    <div class="card-body py-2 px-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-sm-4">
                <label class="form-label small mb-1 fw-medium">Cari User / Deskripsi</label>
                <input type="text" name="user" class="form-control form-control-sm"
                       placeholder="Nama atau kata kunci..." value="<?= htmlspecialchars($filterUser) ?>">
            </div>
            <div class="col-6 col-sm-3">
                <label class="form-label small mb-1 fw-medium">Tipe</label>
                <select name="entity" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <?php foreach ($entityTypes as $et): ?>
                    <option value="<?= htmlspecialchars($et) ?>" <?= $filterEntity === $et ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($et)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <label class="form-label small mb-1 fw-medium">Periode</label>
                <select name="date" class="form-select form-select-sm">
                    <option value="">Semua Waktu</option>
                    <option value="today" <?= $filterDate === 'today' ? 'selected' : '' ?>>Hari Ini</option>
                    <option value="week"  <?= $filterDate === 'week'  ? 'selected' : '' ?>>7 Hari Terakhir</option>
                    <option value="month" <?= $filterDate === 'month' ? 'selected' : '' ?>>30 Hari Terakhir</option>
                </select>
            </div>
            <div class="col-12 col-sm-2">
                <div class="d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
                    <a href="<?= BASE_URL ?>/admin/log_aktivitas.php" class="btn btn-sm btn-outline-secondary">×</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Log Table -->
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:0.825rem">
            <thead class="table-light">
                <tr>
                    <th style="width:140px">Waktu</th>
                    <th style="width:130px">User</th>
                    <th style="width:100px">Aksi</th>
                    <th style="width:90px">Tipe</th>
                    <th>Deskripsi</th>
                    <th class="d-none d-md-table-cell" style="width:110px">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-journal-x d-block mb-2" style="font-size:2rem;opacity:0.3"></i>
                        Tidak ada log yang cocok dengan filter.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="text-muted" style="font-size:0.76rem;white-space:nowrap">
                        <?= date('d/m/Y', strtotime($log['created_at'])) ?><br>
                        <span style="font-size:0.72rem"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                    </td>
                    <td>
                        <?php if ($log['user_nama']): ?>
                        <span class="fw-medium" style="font-size:0.8rem"><?= htmlspecialchars($log['user_nama']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= actionBadge($log['action']) ?></td>
                    <td>
                        <?php if ($log['entity_type']): ?>
                        <span class="d-inline-flex align-items-center gap-1 text-muted" style="font-size:0.76rem">
                            <i class="bi <?= $entityIcon($log['entity_type']) ?>"></i>
                            <?= htmlspecialchars(ucfirst($log['entity_type'])) ?>
                            <?php if ($log['entity_id']): ?>
                            <span style="opacity:0.6">#<?= $log['entity_id'] ?></span>
                            <?php endif; ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($log['description']) ?></td>
                    <td class="d-none d-md-table-cell text-muted" style="font-size:0.75rem"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-4 d-flex justify-content-center">
    <ul class="pagination pagination-sm mb-0">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page - 1])) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['p' => $page + 1])) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
