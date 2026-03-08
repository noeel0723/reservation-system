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

// Edit mode
$editData = null;
if (!empty($_GET['edit'])) {
    $editData = getResourceById($pdo, (int)$_GET['edit']);
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';
?>

<div class="d-flex justify-content-end mb-4">
    <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#resourceModal">
        <i class="bi bi-plus-lg me-1"></i>Tambah Resource
    </button>
</div>

<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm"><?= htmlspecialchars($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm"><?= htmlspecialchars($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Filter + Search Bar -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="d-flex" style="background:#f3f4f6;border-radius:8px;padding:3px">
            <a href="?tipe=" class="btn btn-sm px-3 <?= !$filterTipe ? 'btn-dark text-white' : 'bg-transparent text-muted border-0' ?>" style="border-radius:6px;font-size:0.8rem">Semua</a>
            <a href="?tipe=Studio" class="btn btn-sm px-3 <?= $filterTipe === 'Studio' ? 'btn-dark text-white' : 'bg-transparent text-muted border-0' ?>" style="border-radius:6px;font-size:0.8rem">Studio</a>
            <a href="?tipe=Alat" class="btn btn-sm px-3 <?= $filterTipe === 'Alat' ? 'btn-dark text-white' : 'bg-transparent text-muted border-0' ?>" style="border-radius:6px;font-size:0.8rem">Alat</a>
        </div>
        <span class="text-muted small"><?= count($resources) ?> resource</span>
    </div>
    <div class="input-group input-group-sm" style="max-width:220px">
        <span class="input-group-text bg-white border-end-0 pe-1"><i class="bi bi-search text-muted" style="font-size:0.8rem"></i></span>
        <input type="text" id="resourceSearch" class="form-control border-start-0 ps-1" placeholder="Cari nama resource...">
    </div>
</div>

<!-- Resource Cards Grid -->
<div class="row g-3" id="resourceGrid">
    <?php if (empty($resources)): ?>
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="bi bi-hdd-stack d-block mb-2" style="font-size:2.5rem;opacity:0.25"></i>
                <p class="mb-0 fw-medium">Belum ada resource</p>
                <p class="small">Tambahkan studio atau peralatan menggunakan tombol di atas.</p>
            </div>
        </div>
    <?php else: ?>
        <?php
        $typeColors  = ['Studio' => '#44A6B5', 'Alat' => '#f59e0b'];
        $typeBgLight = ['Studio' => '#e9f6f9', 'Alat' => '#fef3c7'];
        foreach ($resources as $res):
            $initials = strtoupper(mb_substr($res['nama'], 0, 2));
            $color    = $typeColors[$res['tipe']]  ?? '#6c757d';
            $bgLight  = $typeBgLight[$res['tipe']] ?? '#f8f9fa';
        ?>
        <div class="col-sm-6 col-xl-4 resource-card-item" data-name="<?= strtolower(htmlspecialchars($res['nama'])) ?>">
            <div class="card border-0 shadow-sm h-100" style="border-radius:14px;overflow:hidden">
                <div class="card-body pb-2">
                    <!-- Header row -->
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <?php if (!empty($res['foto'])): ?>
                        <div class="flex-shrink-0 rounded-2 overflow-hidden" style="width:46px;height:46px">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($res['foto']) ?>" alt="<?= htmlspecialchars($res['nama']) ?>"
                                 style="width:100%;height:100%;object-fit:cover">
                        </div>
                        <?php else: ?>
                        <div class="flex-shrink-0 d-flex align-items-center justify-content-center fw-bold text-white rounded-2"
                             style="width:46px;height:46px;background:<?= $color ?>;font-size:0.9rem;letter-spacing:0.5px">
                            <?= $initials ?>
                        </div>
                        <?php endif; ?>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h6 class="fw-bold mb-0 text-truncate"><?= htmlspecialchars($res['nama']) ?></h6>
                                <span class="badge rounded-pill" style="background:<?= $bgLight ?>;color:<?= $color ?>;font-size:0.68rem;border:1px solid <?= $color ?>22"><?= $res['tipe'] ?></span>
                            </div>
                            <span class="d-inline-flex align-items-center gap-1 mt-1" style="font-size:0.72rem;color:<?= $res['is_available'] ? '#16a34a' : '#6c757d' ?>">
                                <span style="font-size:0.55rem">●</span><?= $res['is_available'] ? 'Tersedia' : 'Maintenance' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Description -->
                    <?php if (!empty($res['deskripsi'])): ?>
                    <p class="text-muted mb-2 lh-sm" style="font-size:0.8rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">
                        <?= htmlspecialchars($res['deskripsi']) ?>
                    </p>
                    <?php endif; ?>

                    <!-- Meta badges -->
                    <div class="d-flex flex-wrap gap-2 mb-1">
                        <?php if ($res['lokasi']): ?>
                        <span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#f3f4f6;font-size:0.73rem;color:#555">
                            <i class="bi bi-geo-alt" style="color:<?= $color ?>"></i><?= htmlspecialchars($res['lokasi']) ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($res['kapasitas']): ?>
                        <span class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-2" style="background:#f3f4f6;font-size:0.73rem;color:#555">
                            <i class="bi bi-people" style="color:<?= $color ?>"></i><?= $res['kapasitas'] ?> orang
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Hidden toggle availability form -->
                    <form method="POST" action="<?= BASE_URL ?>/admin/proses_resource.php" class="d-none" id="toggleAvailForm<?= $res['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="action" value="toggle_availability">
                        <input type="hidden" name="id" value="<?= $res['id'] ?>">
                    </form>
                </div>

                <!-- Action footer (2 buttons like Pay Bills / See Details) -->
                <div class="card-footer bg-transparent border-top-0 px-3 pb-3 pt-2">
                    <div class="d-flex gap-2">
                        <a href="?edit=<?= $res['id'] ?>" class="btn btn-sm flex-grow-1 fw-medium"
                           style="background:#f0f9fb;color:#44A6B5;border:1px solid #cce8ed;font-size:0.8rem;border-radius:8px">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                        <button type="button" class="btn btn-sm flex-grow-1 fw-medium"
                                style="background:<?= $res['is_available'] ? '#fffbeb' : '#f0fdf4' ?>;color:<?= $res['is_available'] ? '#b45309' : '#15803d' ?>;border:1px solid <?= $res['is_available'] ? '#fde68a' : '#bbf7d0' ?>;font-size:0.8rem;border-radius:8px"
                                onclick="if(confirm('<?= $res['is_available'] ? 'Set resource ke Maintenance?' : 'Aktifkan kembali resource ini?' ?>')) document.getElementById('toggleAvailForm<?= $res['id'] ?>').submit()">
                            <i class="bi bi-<?= $res['is_available'] ? 'wrench' : 'check-circle' ?> me-1"></i><?= $res['is_available'] ? 'Maintenance' : 'Aktifkan' ?>
                        </button>
                        <form method="POST" action="<?= BASE_URL ?>/admin/proses_resource.php" class="d-inline flex-shrink-0"
                              onsubmit="return confirm('Yakin ingin menghapus resource ini? Semua data reservasi terkait akan terpengaruh.')">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $res['id'] ?>">
                            <button type="submit" class="btn btn-sm" title="Hapus"
                                    style="background:#fff0f0;color:#dc3545;border:1px solid #f5c6cb;border-radius:8px;width:34px">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Live search filter
document.getElementById('resourceSearch').addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    document.querySelectorAll('.resource-card-item').forEach(function(card) {
        card.style.display = (!q || card.dataset.name.includes(q)) ? '' : 'none';
    });
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
                        <!-- Kiri -->
                        <div class="col-12 col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-medium">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama" required
                                       value="<?= htmlspecialchars($editData['nama'] ?? '') ?>">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label fw-medium">Tipe <span class="text-danger">*</span></label>
                                    <select name="tipe" class="form-select" required>
                                        <option value="Studio" <?= ($editData['tipe'] ?? '') === 'Studio' ? 'selected' : '' ?>>Studio</option>
                                        <option value="Alat"   <?= ($editData['tipe'] ?? '') === 'Alat'   ? 'selected' : '' ?>>Alat</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Kapasitas</label>
                                    <input type="number" class="form-control" name="kapasitas" min="0"
                                           value="<?= htmlspecialchars($editData['kapasitas'] ?? '') ?>"
                                           placeholder="Untuk studio">
                                </div>
                            </div>
                            <div class="mb-3 mt-3">
                                <label class="form-label fw-medium">Lokasi</label>
                                <input type="text" class="form-control" name="lokasi"
                                       value="<?= htmlspecialchars($editData['lokasi'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" rows="2"><?= htmlspecialchars($editData['deskripsi'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Spesifikasi Teknis</label>
                                <textarea class="form-control" name="spesifikasi" rows="3"
                                          placeholder="Contoh: Resolusi 4K, bitrate 200 Mbps, format MXF..."><?= htmlspecialchars($editData['spesifikasi'] ?? '') ?></textarea>
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

                        <!-- Kanan: Foto -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-medium">Foto Resource</label>
                            <?php if (!empty($editData['foto'])): ?>
                            <div class="mb-2">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($editData['foto']) ?>"
                                     alt="Foto" class="img-fluid rounded-2" style="max-height:150px;width:100%;object-fit:cover">
                                <div class="form-text">Foto saat ini. Upload baru untuk mengganti.</div>
                            </div>
                            <?php endif; ?>
                            <div class="upload-area border rounded-2 d-flex flex-column align-items-center justify-content-center p-3"
                                 style="border-style:dashed!important;border-color:#d1d5db!important;cursor:pointer;min-height:120px;background:#fafbfc"
                                 onclick="document.getElementById('fotoInput').click()">
                                <i class="bi bi-cloud-upload mb-2" style="font-size:1.5rem;color:#9ca3af"></i>
                                <span class="text-muted" style="font-size:0.78rem" id="fotoLabel">Klik untuk pilih foto</span>
                                <span class="text-muted" style="font-size:0.7rem">JPG, PNG, WebP · Maks 2 MB</span>
                            </div>
                            <input type="file" id="fotoInput" name="foto" accept="image/*" class="d-none"
                                   onchange="document.getElementById('fotoLabel').textContent = this.files[0]?.name || 'Klik untuk pilih foto'">
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
