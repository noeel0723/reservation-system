<?php
/**
 * Admin - Kelola Resource (Studio & Alat)
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/resource_helper.php';

requireAdmin();

$pageTitle = 'Resource Management';
$flashSuccess = getFlash('success');
$flashError = getFlash('error');

$filterTipe = $_GET['tipe'] ?? '';
$resources = getResources($pdo, $filterTipe ?: null);

$editData = null;
if (!empty($_GET['edit'])) {
    $editData = getResourceById($pdo, (int)$_GET['edit']);
}

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
.rs-board { border:1px solid #e5e7eb; border-radius:18px; background:#fff; overflow:hidden; }
.rs-head { padding:14px 16px; border-bottom:1px solid #f1f3f5; background:#fcfcfd; }
.rs-toolbar { padding:12px 16px; border-bottom:1px solid #f1f3f5; }
.rs-filter-chip { border:1px solid #e5e7eb; border-radius:999px; padding:6px 12px; font-size:.76rem; font-weight:600; color:#4b5563; text-decoration:none; background:#fff; }
.rs-filter-chip.active { background:#06b6d4; border-color:#06b6d4; color:#fff; }
.rs-count-pill { border:1px solid #dbeafe; background:#eff6ff; color:#1d4ed8; font-size:.72rem; padding:4px 10px; border-radius:999px; font-weight:700; }
.resource-card { border:1px solid #e5e7eb; border-radius:16px; background:#fff; height:100%; transition:.18s; }
.resource-card:hover { transform:translateY(-2px); box-shadow:0 10px 26px rgba(15,23,42,.08); }
.rs-title { font-size:.95rem; font-weight:700; color:#1f2937; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; }
.rs-sub { font-size:.74rem; color:#6b7280; }
.rs-meta { display:inline-flex; align-items:center; gap:4px; padding:4px 9px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:999px; font-size:.71rem; color:#4b5563; }
.rs-actions .btn { border-radius:10px; font-size:.76rem; font-weight:600; }
@media (max-width: 575px) {
    .rs-head, .rs-toolbar { padding:12px; }
}
</style>

<div class="rs-board mb-3">
    <div class="rs-head d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div>
            <h5 class="mb-0 fw-bold">Resources</h5>
            <div class="text-muted" style="font-size:.76rem">Manage studio and equipment availability in one board.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="rs-count-pill" id="resourceCount"><?= count($resources) ?> items</span>
            <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#resourceModal">
                <i class="bi bi-plus-lg me-1"></i>Add Resource
            </button>
        </div>
    </div>

    <div class="rs-toolbar d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <a href="?tipe=" class="rs-filter-chip <?= !$filterTipe ? 'active' : '' ?>">All</a>
            <a href="?tipe=Studio" class="rs-filter-chip <?= $filterTipe === 'Studio' ? 'active' : '' ?>">Studio</a>
            <a href="?tipe=Alat" class="rs-filter-chip <?= $filterTipe === 'Alat' ? 'active' : '' ?>">Equipment</a>
        </div>
        <div class="input-group input-group-sm search-pill-group" style="max-width:320px">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="resourceSearch" class="form-control search-pill-input" placeholder="Search resource...">
        </div>
    </div>

    <div class="p-3 p-md-4">
        <div class="row g-3" id="resourceGrid">
            <?php if (empty($resources)): ?>
                <div class="col-12" id="resourceEmpty">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-hdd-stack d-block mb-2" style="font-size:2.3rem;opacity:.25"></i>
                        <p class="mb-0 fw-medium">No resources yet</p>
                        <p class="small mb-0">Add your first studio or equipment to get started.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php
                $typeColors  = ['Studio' => '#14b8a6', 'Alat' => '#f97316'];
                $typeBgLight = ['Studio' => '#ccfbf1', 'Alat' => '#ffedd5'];
                foreach ($resources as $res):
                    $color    = $typeColors[$res['tipe']]  ?? '#6c757d';
                    $bgLight  = $typeBgLight[$res['tipe']] ?? '#f8f9fa';
                ?>
                <div class="col-12 col-sm-6 col-xl-4 col-xxl-3 resource-card-item" data-name="<?= strtolower(htmlspecialchars($res['nama'])) ?>">
                    <div class="resource-card p-3">
                        <?php if (!empty($res['foto'])): ?>
                        <div class="rounded-3 overflow-hidden mb-3" style="height:110px;background:#f3f4f6">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($res['foto']) ?>" alt="<?= htmlspecialchars($res['nama']) ?>" style="width:100%;height:100%;object-fit:cover">
                        </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <div class="min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <div class="rs-title text-truncate"><?= htmlspecialchars($res['nama']) ?></div>
                                    <span class="badge rounded-pill" style="background:<?= $bgLight ?>;color:<?= $color ?>;font-size:.68rem;border:1px solid <?= $color ?>33"><?= $res['tipe'] ?></span>
                                </div>
                                <div class="rs-sub" style="color:<?= $res['is_available'] ? '#16a34a' : '#9ca3af' ?>">
                                    <i class="bi bi-dot"></i><?= $res['is_available'] ? 'Available' : 'Maintenance' ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($res['deskripsi'])): ?>
                        <p class="text-muted mb-2" style="font-size:.77rem;min-height:38px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                            <?= htmlspecialchars($res['deskripsi']) ?>
                        </p>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php if ($res['lokasi']): ?>
                            <span class="rs-meta"><i class="bi bi-geo-alt"></i><?= htmlspecialchars($res['lokasi']) ?></span>
                            <?php endif; ?>
                            <?php if ($res['kapasitas']): ?>
                            <span class="rs-meta"><i class="bi bi-people"></i><?= $res['kapasitas'] ?> pax</span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="<?= BASE_URL ?>/admin/proses_resource.php" class="d-none" id="toggleAvailForm<?= $res['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="toggle_availability">
                            <input type="hidden" name="id" value="<?= $res['id'] ?>">
                        </form>

                        <div class="rs-actions d-flex gap-2">
                            <a href="?edit=<?= $res['id'] ?>" class="btn btn-sm flex-grow-1" style="background:#ecfeff;color:#0e7490;border:1px solid #a5f3fc">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <button type="button" class="btn btn-sm flex-grow-1"
                                    style="background:<?= $res['is_available'] ? '#fffbeb' : '#ecfdf3' ?>;color:<?= $res['is_available'] ? '#b45309' : '#047857' ?>;border:1px solid <?= $res['is_available'] ? '#fde68a' : '#a7f3d0' ?>"
                                    onclick="if(confirm('<?= $res['is_available'] ? 'Set resource ke Maintenance?' : 'Aktifkan kembali resource ini?' ?>')) document.getElementById('toggleAvailForm<?= $res['id'] ?>').submit()">
                                <i class="bi bi-<?= $res['is_available'] ? 'pause-circle' : 'check-circle' ?> me-1"></i><?= $res['is_available'] ? 'Maintenance' : 'Activate' ?>
                            </button>
                            <form method="POST" action="<?= BASE_URL ?>/admin/proses_resource.php" class="d-inline"
                                  onsubmit="return confirm('Yakin ingin menghapus resource ini? Semua data reservasi terkait akan terpengaruh.')">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $res['id'] ?>">
                                <button type="submit" class="btn btn-sm" title="Delete" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;width:38px">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="col-12 d-none" id="resourceSearchEmpty">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-search d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                        <p class="mb-0 fw-medium">No matching resource</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('resourceSearch').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    var visible = 0;

    document.querySelectorAll('.resource-card-item').forEach(function(card) {
        var match = (!q || card.dataset.name.includes(q));
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    var countEl = document.getElementById('resourceCount');
    if (countEl) countEl.textContent = visible + ' items';

    var emptyState = document.getElementById('resourceSearchEmpty');
    if (emptyState) emptyState.classList.toggle('d-none', visible > 0);
});
</script>

<!-- Add / Edit Modal -->
<div class="modal fade <?= $editData ? 'show' : '' ?>" id="resourceModal" tabindex="-1"
     <?= $editData ? 'style="display:block;background:rgba(0,0,0,0.5);"' : '' ?>>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/admin/proses_resource.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="<?= $editData ? 'update' : 'create' ?>">
                <?php if ($editData): ?>
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php endif; ?>

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-<?= $editData ? 'pencil' : 'plus-circle' ?> me-2"></i>
                        <?= $editData ? 'Edit Resource' : 'Tambah Resource Baru' ?>
                    </h5>
                    <a href="<?= BASE_URL ?>/admin/kelola_resource.php" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama" required value="<?= htmlspecialchars($editData['nama'] ?? '') ?>">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-medium">Tipe <span class="text-danger">*</span></label>
                                    <select name="tipe" class="form-select" required>
                                        <option value="Studio" <?= ($editData['tipe'] ?? '') === 'Studio' ? 'selected' : '' ?>>Studio</option>
                                        <option value="Alat" <?= ($editData['tipe'] ?? '') === 'Alat' ? 'selected' : '' ?>>Alat</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Kapasitas</label>
                                    <input type="number" class="form-control" name="kapasitas" min="0" value="<?= htmlspecialchars($editData['kapasitas'] ?? '') ?>" placeholder="Untuk studio">
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-medium">Lokasi</label>
                                <input type="text" class="form-control" name="lokasi" value="<?= htmlspecialchars($editData['lokasi'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" rows="2"><?= htmlspecialchars($editData['deskripsi'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Spesifikasi Teknis</label>
                                <textarea class="form-control" name="spesifikasi" rows="3" placeholder="Contoh: Resolusi 4K, bitrate 200 Mbps, format MXF..."><?= htmlspecialchars($editData['spesifikasi'] ?? '') ?></textarea>
                                <div class="form-text">Detail teknis yang ditampilkan ke user saat memilih resource.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-medium">Status Ketersediaan</label>
                                <select name="is_available" class="form-select">
                                    <option value="1" <?= ($editData['is_available'] ?? 1) == 1 ? 'selected' : '' ?>>Tersedia</option>
                                    <option value="0" <?= ($editData['is_available'] ?? 1) == 0 ? 'selected' : '' ?>>Maintenance</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-medium">Foto Resource</label>
                            <?php if (!empty($editData['foto'])): ?>
                            <div class="mb-2">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($editData['foto']) ?>" alt="Foto" class="img-fluid rounded-2" style="max-height:150px;width:100%;object-fit:cover">
                                <div class="form-text">Foto saat ini. Upload baru untuk mengganti.</div>
                            </div>
                            <?php endif; ?>
                            <div class="upload-area border rounded-2 d-flex flex-column align-items-center justify-content-center p-3" style="border-style:dashed!important;border-color:#d1d5db!important;cursor:pointer;min-height:120px;background:#fafbfc" onclick="document.getElementById('fotoInput').click()">
                                <i class="bi bi-cloud-upload mb-2" style="font-size:1.5rem;color:#9ca3af"></i>
                                <span class="text-muted" style="font-size:0.78rem" id="fotoLabel">Klik untuk pilih foto</span>
                                <span class="text-muted" style="font-size:0.7rem">JPG, PNG, WebP · Maks 2 MB</span>
                            </div>
                            <input type="file" id="fotoInput" name="foto" accept="image/*" class="d-none" onchange="document.getElementById('fotoLabel').textContent = this.files[0]?.name || 'Klik untuk pilih foto'">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?= BASE_URL ?>/admin/kelola_resource.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i><?= $editData ? 'Simpan Perubahan' : 'Tambah Resource' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>