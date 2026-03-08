<?php
/**
 * Form Reservasi Baru
 * Collision detection terjadi saat submit
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';
require_once __DIR__ . '/../functions/resource_helper.php';

requireStaff();

$pageTitle = 'Reservasi Baru';
$flashError = getFlash('error');
$flashSuccess = getFlash('success');

// Ambil resource yang tersedia
$resources = getResources($pdo, null, true);
$studios = array_filter($resources, fn($r) => $r['tipe'] === 'Studio');
$alats = array_filter($resources, fn($r) => $r['tipe'] === 'Alat');

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_user.php';
?>

<?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($flashError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($flashSuccess) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php
$conflicts = $_SESSION['reservation_conflicts'] ?? null;
$waitlistCandidate = $_SESSION['waitlist_candidate'] ?? null;
if ($conflicts) {
    unset($_SESSION['reservation_conflicts']);
    echo '<div class="alert alert-warning border-0 shadow-sm"><strong><i class="bi bi-exclamation-triangle me-1"></i>Jadwal Bentrok!</strong><ul class="mb-0 mt-2">';
    foreach ($conflicts as $c) {
        echo '<li>' . htmlspecialchars($c) . '</li>';
    }
    echo '</ul></div>';
}
if ($waitlistCandidate) {
    unset($_SESSION['waitlist_candidate']);
?>
<div class="alert border-0 shadow-sm" style="background:#eff6ff;border-left:4px solid #3b82f6!important;border-left-style:solid!important">
    <div class="d-flex align-items-start gap-3">
        <i class="bi bi-hourglass-split mt-1 flex-shrink-0" style="color:#3b82f6;font-size:1.1rem"></i>
        <div class="flex-grow-1">
            <div class="fw-bold mb-1" style="color:#1d4ed8">Ingin Masuk Antrian?</div>
            <p class="mb-3 small text-muted">Slot yang Anda inginkan sedang terisi. Daftarkan diri ke antrian &mdash; Anda akan diberitahu saat slot tersedia.</p>
            <form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="hidden" name="action" value="join_waitlist">
                <input type="hidden" name="resource_id" value="<?= htmlspecialchars($waitlistCandidate['resource_id']) ?>">
                <input type="hidden" name="waktu_mulai" value="<?= htmlspecialchars($waitlistCandidate['waktu_mulai']) ?>">
                <input type="hidden" name="waktu_selesai" value="<?= htmlspecialchars($waitlistCandidate['waktu_selesai']) ?>">
                <input type="hidden" name="keperluan" value="<?= htmlspecialchars($waitlistCandidate['keperluan']) ?>">
                <input type="hidden" name="keterangan" value="<?= htmlspecialchars($waitlistCandidate['keterangan'] ?? '') ?>">
                <div class="rounded-2 px-3 py-2 small" style="background:#dbeafe;color:#1e40af">
                    <i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($waitlistCandidate['waktu_mulai'])) ?> &ndash; <?= date('H:i', strtotime($waitlistCandidate['waktu_selesai'])) ?>
                </div>
                <button type="submit" class="btn btn-sm btn-primary px-3">
                    <i class="bi bi-list-ol me-1"></i>Masuk Antrian
                </button>
            </form>
        </div>
    </div>
</div>
<?php
}
?>

<form method="POST" action="<?= BASE_URL ?>/user/proses_reservasi.php" id="reservasiForm">
<input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
<input type="hidden" name="action" value="create">

<div class="config-wrapper card border-0 shadow-sm overflow-hidden">

    <!-- Top Action Bar -->
    <div class="config-topbar d-flex align-items-center justify-content-between px-4 py-3 border-bottom">
        <div class="d-flex align-items-center gap-3">
            <button type="button" id="btnCancel" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Ubah Resource
            </button>
            <span class="text-muted d-none d-sm-inline" style="font-size:0.8rem">Reservasi Baru</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="btnPrev" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </button>
            <button type="button" id="btnNext" class="btn btn-sm btn-primary rounded-pill px-3">
                Lanjutkan<i class="bi bi-arrow-right ms-1"></i>
            </button>
            <button type="submit" id="btnSubmit" class="btn btn-sm btn-primary rounded-pill px-3 d-none">
                <i class="bi bi-send me-1"></i>Ajukan Reservasi
            </button>
        </div>
    </div>

    <div class="d-flex config-body">

        <!-- Left Step Sidebar -->
        <div class="config-steps-sidebar d-none d-md-flex flex-column border-end" style="width:220px;min-width:220px;background:#fafbfc">
            <div class="p-3">
                <div class="config-step active" data-step="1">
                    <div class="config-step-num">1</div>
                    <div class="config-step-info">
                        <div class="config-step-title">Resource</div>
                        <div class="config-step-desc">Pilih studio atau alat</div>
                    </div>
                </div>
                <div class="config-step" data-step="2">
                    <div class="config-step-num">2</div>
                    <div class="config-step-info">
                        <div class="config-step-title">Jadwal</div>
                        <div class="config-step-desc">Tentukan waktu reservasi</div>
                    </div>
                </div>
                <div class="config-step" data-step="3">
                    <div class="config-step-num">3</div>
                    <div class="config-step-info">
                        <div class="config-step-title">Detail</div>
                        <div class="config-step-desc">Keperluan & keterangan</div>
                    </div>
                </div>
                <div class="config-step" data-step="4">
                    <div class="config-step-num">4</div>
                    <div class="config-step-info">
                        <div class="config-step-title">Konfirmasi</div>
                        <div class="config-step-desc">Review & ajukan</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Step Indicator -->
        <div class="d-md-none config-mobile-steps d-flex align-items-center gap-2 px-3 py-2 border-bottom w-100">
            <span class="config-mobile-step active" data-step="1">1</span>
            <span class="config-mobile-divider"></span>
            <span class="config-mobile-step" data-step="2">2</span>
            <span class="config-mobile-divider"></span>
            <span class="config-mobile-step" data-step="3">3</span>
            <span class="config-mobile-divider"></span>
            <span class="config-mobile-step" data-step="4">4</span>
            <span class="ms-2 small text-muted" id="mobileStepLabel">Resource</span>
        </div>

        <!-- Main Content Area -->
        <div class="config-main flex-grow-1">

            <!-- Step 1: Pilih Resource -->
            <div class="config-panel active" id="step1">
                <div class="p-4">
                    <h5 class="fw-bold mb-1">Pilih Resource</h5>
                    <p class="text-muted small mb-4">Pilih satu studio atau alat yang ingin Anda reservasi.</p>

                    <?php if (!empty($studios)): ?>
                    <div class="mb-4">
                        <p class="section-label mb-3"><i class="bi bi-camera-reels me-1"></i>Studio</p>
                        <div class="row g-3">
                            <?php foreach ($studios as $s):
                                $isChecked = isset($_SESSION['form_data']['resource_id']) && $_SESSION['form_data']['resource_id'] == $s['id'];
                            ?>
                            <div class="col-sm-6 col-lg-4">
                                <label class="config-card<?= $isChecked ? ' selected' : '' ?>" for="resource_<?= $s['id'] ?>">
                                    <input class="d-none" type="radio" name="resource_id"
                                           id="resource_<?= $s['id'] ?>" value="<?= $s['id'] ?>"
                                           data-name="<?= htmlspecialchars($s['nama']) ?>"
                                           data-tipe="Studio"
                                           data-lokasi="<?= htmlspecialchars($s['lokasi'] ?? '') ?>"
                                           data-foto="<?= htmlspecialchars($s['foto'] ?? '') ?>"
                                           data-deskripsi="<?= htmlspecialchars($s['deskripsi'] ?? '') ?>"
                                           data-kapasitas="<?= htmlspecialchars($s['kapasitas'] ?? '') ?>"
                                           data-spesifikasi="<?= htmlspecialchars($s['spesifikasi'] ?? '') ?>"
                                           required <?= $isChecked ? 'checked' : '' ?>>
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="config-card-icon">
                                            <i class="bi bi-camera-reels"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold" style="font-size:0.88rem"><?= htmlspecialchars($s['nama']) ?></div>
                                            <?php if ($s['lokasi']): ?>
                                            <div class="text-muted" style="font-size:0.75rem"><?= htmlspecialchars($s['lokasi']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="config-card-check">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($alats)): ?>
                    <div>
                        <p class="section-label mb-3"><i class="bi bi-camera-video me-1"></i>Peralatan</p>
                        <div class="row g-3">
                            <?php foreach ($alats as $a):
                                $isChecked = isset($_SESSION['form_data']['resource_id']) && $_SESSION['form_data']['resource_id'] == $a['id'];
                            ?>
                            <div class="col-sm-6 col-lg-4">
                                <label class="config-card<?= $isChecked ? ' selected' : '' ?>" for="resource_<?= $a['id'] ?>">
                                    <input class="d-none" type="radio" name="resource_id"
                                           id="resource_<?= $a['id'] ?>" value="<?= $a['id'] ?>"
                                           data-name="<?= htmlspecialchars($a['nama']) ?>"
                                           data-tipe="Alat"
                                           data-lokasi="<?= htmlspecialchars($a['lokasi'] ?? '') ?>"
                                           data-foto="<?= htmlspecialchars($a['foto'] ?? '') ?>"
                                           data-deskripsi="<?= htmlspecialchars($a['deskripsi'] ?? '') ?>"
                                           data-kapasitas="<?= htmlspecialchars($a['kapasitas'] ?? '') ?>"
                                           data-spesifikasi="<?= htmlspecialchars($a['spesifikasi'] ?? '') ?>"
                                           required <?= $isChecked ? 'checked' : '' ?>>
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="config-card-icon">
                                            <i class="bi bi-camera-video"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold" style="font-size:0.88rem"><?= htmlspecialchars($a['nama']) ?></div>
                                            <?php if ($a['lokasi']): ?>
                                            <div class="text-muted" style="font-size:0.75rem"><?= htmlspecialchars($a['lokasi']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="config-card-check">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Resource Detail Panel -->
                    <div id="resourceDetail" class="d-none mt-4 rounded-3 overflow-hidden" style="border:1.5px solid #e0e5ea">
                        <div class="d-flex align-items-center gap-3 px-4 py-3" style="background:#f9fafb;border-bottom:1px solid #e0e5ea">
                            <div id="resDetailThumb" class="flex-shrink-0 rounded-2 overflow-hidden d-flex align-items-center justify-content-center"
                                 style="width:52px;height:52px;background:#e0e5ea">
                                <i class="bi bi-camera-reels" id="resDetailIcon" style="font-size:1.4rem;color:#9ca3af"></i>
                            </div>
                            <div>
                                <div class="fw-bold" id="resDetailName" style="font-size:0.95rem"></div>
                                <div class="d-flex align-items-center gap-3 mt-1" style="font-size:0.78rem;color:#6b7280">
                                    <span id="resDetailTipe"></span>
                                    <span id="resDetailLokasi" class="d-none"><i class="bi bi-geo-alt me-1"></i><span></span></span>
                                    <span id="resDetailKapasitas" class="d-none"><i class="bi bi-people me-1"></i><span></span> orang</span>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-3">
                            <div id="resDetailDeskripsi" class="d-none mb-3">
                                <p class="small text-muted mb-0" id="resDetailDeskripsiText"></p>
                            </div>
                            <div id="resDetailSpesifikasi" class="d-none">
                                <p class="fw-semibold mb-1" style="font-size:0.78rem;color:#374151;text-transform:uppercase;letter-spacing:0.05em">Spesifikasi Teknis</p>
                                <p class="small text-muted mb-0" style="white-space:pre-wrap" id="resDetailSpesText"></p>
                            </div>
                            <div id="resDetailEmpty" class="text-muted small">Tidak ada informasi tambahan.</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Step 2: Jadwal -->
            <div class="config-panel" id="step2">
                <div class="p-4">
                    <h5 class="fw-bold mb-1">Tentukan Jadwal</h5>
                    <p class="text-muted small mb-4">Atur waktu mulai dan selesai reservasi Anda.</p>

                    <div class="row g-4" style="max-width:560px">
                        <div class="col-sm-6">
                            <label class="form-label fw-medium small">Waktu Mulai <span class="text-danger">*</span></label>
                            <div class="booking-datetime-wrap">
                                <i class="bi bi-calendar3 text-muted"></i>
                                <input type="datetime-local" id="waktu_mulai" name="waktu_mulai" required
                                       class="booking-datetime-input"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['waktu_mulai'] ?? '') ?>"
                                       min="<?= date('Y-m-d\TH:i') ?>">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-medium small">Waktu Selesai <span class="text-danger">*</span></label>
                            <div class="booking-datetime-wrap">
                                <i class="bi bi-calendar3-range text-muted"></i>
                                <input type="datetime-local" id="waktu_selesai" name="waktu_selesai" required
                                       class="booking-datetime-input"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['waktu_selesai'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 align-items-start p-3 rounded-3 mt-4" style="background:rgba(68,166,181,0.06);border:1px solid rgba(68,166,181,0.15);max-width:560px">
                        <i class="bi bi-info-circle mt-1 flex-shrink-0" style="color:var(--color-moonstone);font-size:0.9rem"></i>
                        <p class="small mb-0 text-muted">Sistem akan otomatis memeriksa ketersediaan resource pada waktu yang dipilih.</p>
                    </div>

                    <!-- Resource Availability Preview -->
                    <div id="availabilitySection" class="mt-4 d-none" style="max-width:560px">
                        <h6 class="fw-semibold mb-2" style="font-size:0.83rem">
                            <i class="bi bi-calendar-check me-1" style="color:var(--color-moonstone)"></i>Ketersediaan Resource Pada Tanggal Ini
                        </h6>
                        <div id="availabilityContent" class="p-3 rounded-3" style="background:#f9fafb;border:1px solid #e0e5ea;min-height:52px;font-size:0.83rem">
                            <span class="text-muted">Pilih resource dan tanggal untuk melihat jadwal.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Detail -->
            <div class="config-panel" id="step3">
                <div class="p-4">
                    <h5 class="fw-bold mb-1">Detail Keperluan</h5>
                    <p class="text-muted small mb-4">Jelaskan keperluan reservasi Anda.</p>

                    <div style="max-width:560px">
                        <div class="mb-4">
                            <label class="form-label fw-medium small">Keperluan <span class="text-danger">*</span></label>
                            <div class="booking-input-wrap">
                                <i class="bi bi-pencil-square text-muted"></i>
                                <input type="text" id="keperluan" name="keperluan" required
                                       class="booking-text-input"
                                       placeholder="cth: Siaran Langsung Berita Siang"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['keperluan'] ?? '') ?>">
                            </div>
                        </div>
                        <div>
                            <label class="form-label fw-medium small">Keterangan Tambahan <span class="text-muted fw-normal">(opsional)</span></label>
                            <textarea class="form-control" name="keterangan" id="keterangan" rows="4"
                                      placeholder="Informasi tambahan untuk admin..."
                                      style="border-radius:10px;border:1.5px solid #e0e5ea"><?= htmlspecialchars($_SESSION['form_data']['keterangan'] ?? '') ?></textarea>
                        </div>

                        <!-- Recurring option -->
                        <div class="mt-4 p-3 rounded-3" style="background:#f9fafb;border:1.5px solid #e0e5ea">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" value="1">
                                <label class="form-check-label fw-semibold small" for="is_recurring">
                                    <i class="bi bi-arrow-repeat me-1" style="color:var(--color-moonstone)"></i>Buat Reservasi Berulang
                                </label>
                            </div>
                            <div id="recurringOptions" class="d-none mt-3">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-medium">Pengulangan</label>
                                        <select class="form-select form-select-sm" name="recurring_type" id="recurringType">
                                            <option value="weekly">Mingguan (setiap 7 hari)</option>
                                            <option value="monthly">Bulanan (setiap 1 bulan)</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-medium">Jumlah Sesi</label>
                                        <select class="form-select form-select-sm" name="recurring_count" id="recurringCount">
                                            <?php for ($rc = 2; $rc <= 12; $rc++): ?>
                                            <option value="<?= $rc ?>"><?= $rc ?>x</option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <p class="mb-0 mt-2 text-muted" style="font-size:0.76rem">
                                    <i class="bi bi-info-circle me-1"></i>Setiap sesi diajukan sebagai reservasi terpisah dan memerlukan persetujuan admin.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Konfirmasi -->
            <div class="config-panel" id="step4">
                <div class="p-4">
                    <h5 class="fw-bold mb-1">Konfirmasi Reservasi</h5>
                    <p class="text-muted small mb-4">Periksa kembali detail reservasi sebelum mengajukan.</p>

                    <div class="config-summary" style="max-width:560px">
                        <!-- Resource -->
                        <div class="config-summary-row">
                            <div class="config-summary-label">Resource</div>
                            <div class="config-summary-value" id="summary-resource">
                                <span class="text-muted fst-italic">Belum dipilih</span>
                            </div>
                        </div>
                        <!-- Jadwal -->
                        <div class="config-summary-row">
                            <div class="config-summary-label">Jadwal</div>
                            <div class="config-summary-value" id="summary-schedule">
                                <span class="text-muted fst-italic">Belum ditentukan</span>
                            </div>
                        </div>
                        <!-- Keperluan -->
                        <div class="config-summary-row">
                            <div class="config-summary-label">Keperluan</div>
                            <div class="config-summary-value" id="summary-keperluan">
                                <span class="text-muted fst-italic">Belum diisi</span>
                            </div>
                        </div>
                        <!-- Keterangan -->
                        <div class="config-summary-row">
                            <div class="config-summary-label">Keterangan</div>
                            <div class="config-summary-value" id="summary-keterangan">
                                <span class="text-muted fst-italic">-</span>
                            </div>
                        </div>
                        <!-- Recurring -->
                        <div class="config-summary-row" id="summary-recurring-row" style="display:none">
                            <div class="config-summary-label">Pengulangan</div>
                            <div class="config-summary-value" id="summary-recurring">—</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 align-items-start p-3 rounded-3 mt-4" style="background:rgba(255,193,7,0.08);border:1px solid rgba(255,193,7,0.25);max-width:560px">
                        <i class="bi bi-shield-check mt-1 flex-shrink-0" style="color:#f59e0b;font-size:0.95rem"></i>
                        <p class="small mb-0 text-muted">Sistem mengecek bentrok jadwal otomatis. Admin akan mereview pengajuan Anda.</p>
                    </div>
                </div>
            </div>

        </div><!-- /.config-main -->

    </div><!-- /.config-body -->

</div><!-- /.config-wrapper -->
</form>

<?php unset($_SESSION['form_data']); ?>

<script>
var BASE_URL = '<?= BASE_URL ?>';
(function(){
    var currentStep = 1;
    var totalSteps = 4;
    var stepLabels = ['Resource', 'Jadwal', 'Detail', 'Konfirmasi'];

    var btnPrev = document.getElementById('btnPrev');
    var btnNext = document.getElementById('btnNext');
    var btnSubmit = document.getElementById('btnSubmit');
    var btnCancel = document.getElementById('btnCancel');
    var panels = document.querySelectorAll('.config-panel');
    var sideSteps = document.querySelectorAll('.config-step');
    var mobileSteps = document.querySelectorAll('.config-mobile-step');
    var mobileLabel = document.getElementById('mobileStepLabel');

    function goToStep(n) {
        currentStep = n;
        panels.forEach(function(p, i) { p.classList.toggle('active', i === n - 1); });
        sideSteps.forEach(function(s) {
            var sn = parseInt(s.dataset.step);
            s.classList.remove('active', 'completed');
            if (sn < n) s.classList.add('completed');
            else if (sn === n) s.classList.add('active');
        });
        mobileSteps.forEach(function(ms) {
            var sn = parseInt(ms.dataset.step);
            ms.classList.remove('active', 'completed');
            if (sn < n) ms.classList.add('completed');
            else if (sn === n) ms.classList.add('active');
        });
        if (mobileLabel) mobileLabel.textContent = stepLabels[n - 1];
        btnCancel.classList.toggle('d-none', n === 1);
        btnPrev.classList.toggle('d-none', n === 1);
        btnNext.classList.toggle('d-none', n === totalSteps);
        btnSubmit.classList.toggle('d-none', n !== totalSteps);
        if (n === totalSteps) updateSummary();
        document.querySelector('.config-main').scrollTop = 0;
        // Load availability when entering step 2
        if (n === 2) loadAvailability();
    }

    function validateStep(n) {
        if (n === 1) {
            var checked = document.querySelector('input[name="resource_id"]:checked');
            if (!checked) { alert('Silakan pilih resource terlebih dahulu.'); return false; }
        } else if (n === 2) {
            var mulai = document.getElementById('waktu_mulai').value;
            var selesai = document.getElementById('waktu_selesai').value;
            if (!mulai || !selesai) { alert('Silakan tentukan waktu mulai dan selesai.'); return false; }
            if (selesai <= mulai) { alert('Waktu selesai harus lebih besar dari waktu mulai.'); return false; }
        } else if (n === 3) {
            var kep = document.getElementById('keperluan').value.trim();
            if (!kep) { alert('Silakan isi keperluan reservasi.'); return false; }
        }
        return true;
    }

    btnNext.addEventListener('click', function() {
        if (validateStep(currentStep)) goToStep(currentStep + 1);
    });
    btnPrev.addEventListener('click', function() { if (currentStep > 1) goToStep(currentStep - 1); });
    btnCancel.addEventListener('click', function() { goToStep(1); });

    // Resource card selection + detail panel
    document.querySelectorAll('.config-card input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.config-card').forEach(function(c) { c.classList.remove('selected'); });
            this.closest('.config-card').classList.add('selected');
            // Reset availability when resource changes
            var avSection = document.getElementById('availabilitySection');
            if (avSection) avSection.classList.add('d-none');
            // Populate resource detail panel
            var detail = document.getElementById('resourceDetail');
            if (!detail) return;
            var d = this.dataset;
            document.getElementById('resDetailName').textContent = d.name || '';
            document.getElementById('resDetailTipe').textContent = d.tipe || '';
            var iconEl = document.getElementById('resDetailIcon');
            if (iconEl) iconEl.className = 'bi bi-' + (d.tipe === 'Studio' ? 'camera-reels' : 'camera-video') + ' ' + iconEl.className.replace(/bi-\S+/g,'');
            // Thumbnail
            var thumb = document.getElementById('resDetailThumb');
            if (thumb) {
                if (d.foto) {
                    thumb.innerHTML = '<img src="' + BASE_URL + '/' + d.foto + '" style="width:100%;height:100%;object-fit:cover" alt="">';
                } else {
                    thumb.innerHTML = '<i class="bi bi-' + (d.tipe === 'Studio' ? 'camera-reels' : 'camera-video') + '" style="font-size:1.4rem;color:#9ca3af"></i>';
                }
            }
            // Lokasi
            var lokEl = document.getElementById('resDetailLokasi');
            if (lokEl) {
                if (d.lokasi) { lokEl.classList.remove('d-none'); lokEl.querySelector('span').textContent = d.lokasi; }
                else lokEl.classList.add('d-none');
            }
            // Kapasitas
            var kapEl = document.getElementById('resDetailKapasitas');
            if (kapEl) {
                if (d.kapasitas) { kapEl.classList.remove('d-none'); kapEl.querySelector('span').textContent = d.kapasitas; }
                else kapEl.classList.add('d-none');
            }
            // Deskripsi
            var deskEl = document.getElementById('resDetailDeskripsi');
            var deskText = document.getElementById('resDetailDeskripsiText');
            if (deskEl && deskText) {
                if (d.deskripsi) { deskEl.classList.remove('d-none'); deskText.textContent = d.deskripsi; }
                else deskEl.classList.add('d-none');
            }
            // Spesifikasi
            var spesEl = document.getElementById('resDetailSpesifikasi');
            var spesText = document.getElementById('resDetailSpesText');
            if (spesEl && spesText) {
                if (d.spesifikasi) { spesEl.classList.remove('d-none'); spesText.textContent = d.spesifikasi; }
                else spesEl.classList.add('d-none');
            }
            // Empty state
            var emptyEl = document.getElementById('resDetailEmpty');
            if (emptyEl) emptyEl.classList.toggle('d-none', !!(d.deskripsi || d.spesifikasi));
            detail.classList.remove('d-none');
        });
    });

    // Waktu selesai min = waktu mulai
    document.getElementById('waktu_mulai').addEventListener('change', function() {
        var selesai = document.getElementById('waktu_selesai');
        selesai.min = this.value;
        if (selesai.value && selesai.value < this.value) selesai.value = '';
        loadAvailability();
    });
    document.getElementById('waktu_selesai').addEventListener('change', function() {
        loadAvailability();
    });

    // --- Resource Availability Preview ---
    function loadAvailability() {
        var checkedRes = document.querySelector('input[name="resource_id"]:checked');
        var mulaiVal = document.getElementById('waktu_mulai').value;
        var section = document.getElementById('availabilitySection');
        var content = document.getElementById('availabilityContent');
        if (!section || !content) return;
        if (!checkedRes || !mulaiVal) { section.classList.add('d-none'); return; }

        var date = mulaiVal.split('T')[0];
        section.classList.remove('d-none');
        content.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Memuat jadwal...</span>';

        fetch(BASE_URL + '/user/api_availability.php?resource_id=' + encodeURIComponent(checkedRes.value) + '&date=' + encodeURIComponent(date))
            .then(function(r) { return r.json(); })
            .then(function(bookings) {
                if (!bookings.length) {
                    content.innerHTML = '<span style="color:#22c55e"><i class="bi bi-check-circle me-1"></i>Tidak ada reservasi pada tanggal ini. Resource tersedia!</span>';
                    return;
                }
                var html = '<div style="font-size:0.8rem"><p class="fw-semibold mb-2" style="color:#f59e0b"><i class="bi bi-exclamation-triangle me-1"></i>' + bookings.length + ' reservasi sudah ada pada hari ini:</p>';
                bookings.forEach(function(b) {
                    var st = new Date(b.waktu_mulai).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',hour12:false});
                    var en = new Date(b.waktu_selesai).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',hour12:false});
                    var bc = b.status === 'Approved' ? '#22c55e' : '#f59e0b';
                    html += '<div class="d-flex align-items-center gap-2 py-1 border-bottom" style="border-color:#eee!important">'
                        + '<span style="background:'+bc+'20;color:'+bc+';border-radius:4px;padding:1px 6px;font-size:0.7rem;font-weight:600;white-space:nowrap">'+b.status+'</span>'
                        + '<span class="fw-medium">'+st+' &ndash; '+en+'</span>'
                        + '<span class="text-muted text-truncate">'+b.keperluan+'</span>'
                        + '</div>';
                });
                html += '</div>';
                content.innerHTML = html;
            })
            .catch(function() {
                content.innerHTML = '<span class="text-muted small">Gagal memuat jadwal ketersediaan.</span>';
            });
    }

    // --- Recurring toggle ---
    var recurringCheck = document.getElementById('is_recurring');
    if (recurringCheck) {
        recurringCheck.addEventListener('change', function() {
            document.getElementById('recurringOptions').classList.toggle('d-none', !this.checked);
        });
    }

    function updateSummary() {
        // Resource
        var checkedRes = document.querySelector('input[name="resource_id"]:checked');
        var resEl = document.getElementById('summary-resource');
        if (checkedRes) {
            var tipe = checkedRes.dataset.tipe;
            var lokasi = checkedRes.dataset.lokasi;
            resEl.innerHTML = '<div class="d-flex align-items-center gap-2">'
                + '<div class="resource-thumb flex-shrink-0"><i class="bi bi-' + (tipe === 'Studio' ? 'camera-reels' : 'camera-video') + '"></i></div>'
                + '<div><div class="fw-semibold">' + checkedRes.dataset.name + '</div>'
                + '<div class="text-muted" style="font-size:0.78rem">' + tipe + (lokasi ? ' &bull; ' + lokasi : '') + '</div></div></div>';
        } else {
            resEl.innerHTML = '<span class="text-muted fst-italic">Belum dipilih</span>';
        }

        // Schedule
        var mulai = document.getElementById('waktu_mulai').value;
        var selesai = document.getElementById('waktu_selesai').value;
        var schedEl = document.getElementById('summary-schedule');
        if (mulai) {
            var m = new Date(mulai), s = selesai ? new Date(selesai) : null;
            var opts = {weekday:'short',day:'2-digit',month:'short',year:'numeric'};
            var timeOpts = {hour:'2-digit',minute:'2-digit',hour12:false};
            var txt = m.toLocaleDateString('id-ID',opts) + ' &bull; '
                + m.toLocaleTimeString('id-ID',timeOpts)
                + (s ? ' &ndash; ' + s.toLocaleTimeString('id-ID',timeOpts) : '');
            schedEl.innerHTML = '<i class="bi bi-clock me-2" style="color:var(--color-moonstone)"></i>' + txt;
        } else {
            schedEl.innerHTML = '<span class="text-muted fst-italic">Belum ditentukan</span>';
        }

        // Keperluan
        var kep = document.getElementById('keperluan').value.trim();
        var kepEl = document.getElementById('summary-keperluan');
        kepEl.textContent = kep || 'Belum diisi';
        kepEl.className = kep ? 'config-summary-value fw-medium' : 'config-summary-value text-muted fst-italic';

        // Keterangan
        var ket = document.getElementById('keterangan').value.trim();
        var ketEl = document.getElementById('summary-keterangan');
        ketEl.textContent = ket || '-';
        ketEl.className = ket ? 'config-summary-value' : 'config-summary-value text-muted fst-italic';

        // Recurring
        var recurringRow = document.getElementById('summary-recurring-row');
        var recurringEl  = document.getElementById('summary-recurring');
        var isRec = recurringCheck && recurringCheck.checked;
        if (recurringRow) recurringRow.style.display = isRec ? '' : 'none';
        if (isRec && recurringEl) {
            var typeLabel = document.getElementById('recurringType').options[document.getElementById('recurringType').selectedIndex].text;
            var cnt = document.getElementById('recurringCount').value;
            recurringEl.innerHTML = '<span style="color:var(--color-moonstone);font-weight:600">'
                + cnt + 'x</span> &bull; ' + typeLabel;
        }
    }

    goToStep(1);
})();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
