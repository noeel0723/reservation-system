<?php
/**
 * Admin — Aturan Reservasi (Feature 6)
 * Manages booking rules: max duration, advance notice, active count, and allowed hours.
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/log_helper.php';

requireAdmin();

$pageTitle = 'Aturan Reservasi';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $allowedKeys  = ['max_duration_hours', 'max_advance_days', 'min_advance_hours',
                     'max_active_per_user', 'booking_start_hour', 'booking_end_hour'];
    $timeKeys     = ['booking_start_hour', 'booking_end_hour'];
    $numberKeys   = ['max_duration_hours', 'max_advance_days', 'min_advance_hours', 'max_active_per_user'];

    $errors = [];
    $toSave = [];

    foreach ($allowedKeys as $k) {
        $val = trim($_POST[$k] ?? '');
        if (in_array($k, $timeKeys)) {
            if (!preg_match('/^\d{2}:\d{2}$/', $val)) {
                $errors[] = "Format waktu tidak valid untuk '{$k}'.";
                continue;
            }
        } elseif (in_array($k, $numberKeys)) {
            if (!ctype_digit($val) || (int)$val < 0) {
                $errors[] = "Nilai tidak valid untuk '{$k}'. Masukkan angka >= 0.";
                continue;
            }
        }
        $toSave[$k] = $val;
    }

    // Validate: start_hour < end_hour
    if (isset($toSave['booking_start_hour'], $toSave['booking_end_hour'])) {
        if ($toSave['booking_start_hour'] >= $toSave['booking_end_hour']) {
            $errors[] = 'Jam mulai pemesanan harus lebih awal dari jam selesai.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE settings SET value = :v WHERE `key` = :k");
        foreach ($toSave as $k => $v) {
            $stmt->execute([':v' => $v, ':k' => $k]);
        }
        logActivity($pdo, 'update', 'settings', null, 'Admin memperbarui aturan reservasi.');
        setFlash('success', 'Aturan reservasi berhasil disimpan.');
    } else {
        setFlash('error', implode(' ', $errors));
    }

    header('Location: ' . BASE_URL . '/admin/aturan_reservasi.php');
    exit;
}

$settings     = getAllSettings($pdo);
$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

// Helper to get current value conveniently
$s = fn(string $key, $default = '') => htmlspecialchars($settings[$key]['value'] ?? $default);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar_admin.php';
?>

<?php if ($flashSuccess): ?>
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flashSuccess) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($flashError) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Info banner -->
<div class="alert border-0 mb-4 d-flex align-items-start gap-3"
     style="background:#eff6ff;border-left:4px solid #3b82f6!important;border-radius:10px">
    <i class="bi bi-info-circle-fill mt-1" style="color:#3b82f6;font-size:1.1rem;flex-shrink:0"></i>
    <div>
        <div class="fw-semibold" style="color:#1d4ed8">Tentang Aturan Reservasi</div>
        <div class="text-muted small mt-1">
            Pengaturan ini berlaku untuk semua pengguna Staff saat mengajukan reservasi baru.
            Nilai <strong>0</strong> berarti tidak dibatasi (kecuali untuk jam pemesanan).
        </div>
    </div>
</div>

<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

<div class="row g-4">

    <!-- Card: Durasi & Jam -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
            <div class="card-header bg-white border-bottom d-flex align-items-center gap-2" style="border-radius:14px 14px 0 0">
                <span class="d-flex align-items-center justify-content-center rounded-2"
                      style="width:32px;height:32px;background:#eff6ff;color:#3b82f6;font-size:1rem;flex-shrink:0">
                    <i class="bi bi-clock"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-semibold">Batas Waktu Reservasi</h6>
                    <p class="mb-0 text-muted" style="font-size:0.75rem">Durasi dan jam operasional</p>
                </div>
            </div>
            <div class="card-body">
                <!-- max_duration_hours -->
                <div class="mb-4">
                    <label class="form-label fw-medium">
                        Maksimal Durasi per Sesi (Jam)
                        <span class="badge bg-secondary ms-1" style="font-size:0.65rem">0 = tidak dibatasi</span>
                    </label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="max_duration_hours"
                               min="0" max="24" value="<?= $s('max_duration_hours', '8') ?>">
                        <span class="input-group-text">jam</span>
                    </div>
                    <div class="form-text"><?= htmlspecialchars($settings['max_duration_hours']['description'] ?? '') ?></div>
                </div>
                <!-- booking_start_hour / booking_end_hour -->
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label fw-medium">Jam Mulai</label>
                        <input type="time" class="form-control" name="booking_start_hour"
                               value="<?= $s('booking_start_hour', '06:00') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">Jam Selesai</label>
                        <input type="time" class="form-control" name="booking_end_hour"
                               value="<?= $s('booking_end_hour', '22:00') ?>">
                    </div>
                </div>
                <div class="form-text mt-1">Jam operasional pemesanan. Reservasi di luar jam ini akan ditolak.</div>
            </div>
        </div>
    </div>

    <!-- Card: Pengajuan & Kuota -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:14px">
            <div class="card-header bg-white border-bottom d-flex align-items-center gap-2" style="border-radius:14px 14px 0 0">
                <span class="d-flex align-items-center justify-content-center rounded-2"
                      style="width:32px;height:32px;background:#f0fdf4;color:#16a34a;font-size:1rem;flex-shrink:0">
                    <i class="bi bi-calendar-range"></i>
                </span>
                <div>
                    <h6 class="mb-0 fw-semibold">Batas Pengajuan & Kuota</h6>
                    <p class="mb-0 text-muted" style="font-size:0.75rem">Kapan dan berapa banyak boleh reservasi</p>
                </div>
            </div>
            <div class="card-body">
                <!-- max_advance_days -->
                <div class="mb-4">
                    <label class="form-label fw-medium">
                        Maks. Reservasi di Muka (Hari)
                        <span class="badge bg-secondary ms-1" style="font-size:0.65rem">0 = tidak dibatasi</span>
                    </label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="max_advance_days"
                               min="0" max="365" value="<?= $s('max_advance_days', '30') ?>">
                        <span class="input-group-text">hari</span>
                    </div>
                    <div class="form-text"><?= htmlspecialchars($settings['max_advance_days']['description'] ?? '') ?></div>
                </div>
                <!-- min_advance_hours -->
                <div class="mb-4">
                    <label class="form-label fw-medium">
                        Min. Pengajuan Sebelum Mulai (Jam)
                        <span class="badge bg-secondary ms-1" style="font-size:0.65rem">0 = tidak dibatasi</span>
                    </label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="min_advance_hours"
                               min="0" max="168" value="<?= $s('min_advance_hours', '1') ?>">
                        <span class="input-group-text">jam</span>
                    </div>
                    <div class="form-text"><?= htmlspecialchars($settings['min_advance_hours']['description'] ?? '') ?></div>
                </div>
                <!-- max_active_per_user -->
                <div class="mb-0">
                    <label class="form-label fw-medium">
                        Maks. Reservasi Aktif per User
                        <span class="badge bg-secondary ms-1" style="font-size:0.65rem">0 = tidak dibatasi</span>
                    </label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="max_active_per_user"
                               min="0" max="100" value="<?= $s('max_active_per_user', '5') ?>">
                        <span class="input-group-text">reservasi</span>
                    </div>
                    <div class="form-text"><?= htmlspecialchars($settings['max_active_per_user']['description'] ?? '') ?></div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Save -->
<div class="d-flex justify-content-end mt-4 gap-2">
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
    <button type="submit" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-check-lg me-1"></i>Simpan Aturan
    </button>
</div>

</form>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
