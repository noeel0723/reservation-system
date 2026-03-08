<?php
/**
 * Admin — Aturan Reservasi (Feature 6)
 * Manages booking rules: max duration, advance notice, active count, and allowed hours.
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/log_helper.php';

requireAdmin();

$pageTitle = 'Reservation Rules';

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

// Build duration preset list
$durationPresets  = [2, 4, 6, 8, 12, 0];
$currentDuration  = (int)($settings['max_duration_hours']['value'] ?? 8);
$isCustomDuration = !in_array($currentDuration, $durationPresets);
?>

<style>
/* ── Aturan Reservasi – config-wizard style ── */
.ar-config-card {
    background: #fff;
    border: 1.5px solid #e5e7eb;
    border-radius: 16px;
    overflow: hidden;
}
.ar-section { padding: 1.75rem 2rem; }
.ar-section + .ar-section { border-top: 1px solid #f1f3f5; }
.ar-section-title  { font-size: 0.95rem; font-weight: 700; color: #111827; margin-bottom: 0.2rem; }
.ar-section-desc   { font-size: 0.8rem;  color: #6b7280; margin-bottom: 1.1rem; }

/* Preset chips (Object-style row) */
.ar-preset-row { display: flex; flex-wrap: wrap; gap: 10px; }
.ar-preset-chip {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 4px; padding: 10px 18px; border-radius: 10px; cursor: pointer;
    border: 1.5px solid #e5e7eb; background: #fff;
    transition: border-color .15s, background .15s, color .15s;
    min-width: 72px; user-select: none;
}
.ar-preset-chip i  { font-size: 1.1rem; color: #9ca3af; transition: color .15s; }
.ar-preset-chip .ar-preset-label { font-size: 0.75rem; font-weight: 600; color: #374151; white-space: nowrap; transition: color .15s; }
.ar-preset-chip:hover { border-color: #c4b5fd; background: #faf5ff; }
.ar-preset-chip:hover i, .ar-preset-chip:hover .ar-preset-label { color: #7c3aed; }
.ar-preset-chip.selected { border-color: #7c3aed; background: #7c3aed; }
.ar-preset-chip.selected i, .ar-preset-chip.selected .ar-preset-label { color: #fff; }

/* Time cards */
.ar-time-card {
    border: 1.5px solid #e5e7eb; border-radius: 10px; padding: 14px 16px;
    background: #fff; transition: border-color .15s;
}
.ar-time-card:focus-within { border-color: #7c3aed; }
.ar-time-label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase;
                 letter-spacing: .05em; color: #9ca3af; margin-bottom: 6px; }
.ar-time-input { width: 100%; border: none; outline: none; font-size: 1rem;
                 font-weight: 600; color: #111827; background: transparent; }

/* Rule cards (Type-style 3-col) */
.ar-rule-card {
    border: 1.5px solid #e5e7eb; border-radius: 12px; padding: 1.1rem 1.1rem 1rem;
    background: #fff; height: 100%; transition: border-color .15s, box-shadow .15s;
}
.ar-rule-card:hover { border-color: #c4b5fd; box-shadow: 0 0 0 3px #f5f3ff; }
.ar-rule-icon {
    width: 36px; height: 36px; border-radius: 8px; display: flex;
    align-items: center; justify-content: center; font-size: 1rem; margin-bottom: .75rem;
}
.ar-rule-title { font-size: 0.88rem; font-weight: 700; color: #111827; margin-bottom: .25rem; }
.ar-rule-desc  { font-size: 0.75rem; color: #6b7280; line-height: 1.45; }
.ar-rule-input .input-group-text { font-size: 0.8rem; }
.ar-rule-input .form-control { font-size: 0.88rem; font-weight: 600; text-align: center; }

/* Action bar */
.ar-action-bar {
    padding: 1rem 2rem; background: #fafafa;
    border-top: 1px solid #f1f3f5;
    display: flex; align-items: center; justify-content: flex-end; gap: .75rem;
}

@media (max-width: 767px) {
    .ar-section { padding: 1.25rem 1rem; }
    .ar-action-bar { padding: 1rem; }
}
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

<form method="POST" action="">
<input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
<input type="hidden" name="max_duration_hours" id="maxDurationHidden" value="<?= $s('max_duration_hours', '8') ?>">

<div class="ar-config-card">

    <!-- ── Section 1: Judul ── -->
    <div class="ar-section pb-3">
        <h4 class="fw-bold mb-1" style="font-size:1.35rem">Reservation Rules</h4>
        <p class="text-muted mb-0" style="font-size:0.85rem">
            Configure rules applied to all staff reservation requests.
            A value of <strong>0</strong> means no limit.
        </p>
    </div>

    <!-- ── Section 2: Durasi Sesi ── -->
    <div class="ar-section">
        <div class="ar-section-title">Durasi Sesi</div>
        <div class="ar-section-desc">Pilih maksimal durasi yang diizinkan untuk satu sesi reservasi.</div>

        <div class="ar-preset-row" id="durationPresets">
            <?php
            $presetLabels = [2 => '2 Jam', 4 => '4 Jam', 6 => '6 Jam',
                             8 => '8 Jam', 12 => '12 Jam', 0 => 'Tak Terbatas'];
            $presetIcons  = [2 => 'bi-clock',   4 => 'bi-clock',   6 => 'bi-clock',
                             8 => 'bi-clock',   12 => 'bi-clock-history', 0 => 'bi-infinity'];
            foreach ($durationPresets as $dp):
                $sel = (!$isCustomDuration && $currentDuration === $dp) ? 'selected' : '';
            ?>
            <div class="ar-preset-chip <?= $sel ?>" data-value="<?= $dp ?>">
                <i class="bi <?= $presetIcons[$dp] ?>"></i>
                <div class="ar-preset-label"><?= $presetLabels[$dp] ?></div>
            </div>
            <?php endforeach; ?>
            <div class="ar-preset-chip <?= $isCustomDuration ? 'selected' : '' ?>" data-value="custom">
                <i class="bi bi-pencil-square"></i>
                <div class="ar-preset-label">Custom</div>
            </div>
        </div>

        <div id="customDurWrap" class="mt-3 <?= $isCustomDuration ? '' : 'd-none' ?>" style="max-width:220px">
            <label class="form-label small fw-medium mb-1">Masukkan nilai custom</label>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control" id="customDurNum" min="1" max="48"
                       value="<?= $isCustomDuration ? $currentDuration : '' ?>" placeholder="contoh: 10">
                <span class="input-group-text">jam</span>
            </div>
        </div>
    </div>

    <!-- ── Section 3: Jam Operasional ── -->
    <div class="ar-section">
        <div class="ar-section-title">Jam Operasional</div>
        <div class="ar-section-desc">Reservasi hanya dapat diajukan dalam rentang jam ini setiap harinya.</div>

        <div class="row g-3" style="max-width:380px">
            <div class="col-6">
                <div class="ar-time-card">
                    <div class="ar-time-label"><i class="bi bi-sunrise me-1"></i>Jam Mulai</div>
                    <input type="time" class="ar-time-input" name="booking_start_hour"
                           value="<?= $s('booking_start_hour', '06:00') ?>">
                </div>
            </div>
            <div class="col-6">
                <div class="ar-time-card">
                    <div class="ar-time-label"><i class="bi bi-sunset me-1"></i>Jam Selesai</div>
                    <input type="time" class="ar-time-input" name="booking_end_hour"
                           value="<?= $s('booking_end_hour', '22:00') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── Section 4: Batas Pengajuan & Kuota ── -->
    <div class="ar-section">
        <div class="ar-section-title">Batas Pengajuan &amp; Kuota</div>
        <div class="ar-section-desc">Tentukan seberapa jauh ke depan reservasi bisa dibuat dan batasan per pengguna.</div>

        <div class="row g-3">
            <!-- max_advance_days -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="ar-rule-card">
                    <div class="ar-rule-icon" style="background:#eff6ff;color:#3b82f6">
                        <i class="bi bi-calendar-range"></i>
                    </div>
                    <div class="ar-rule-title">Pengajuan Maks. H-?</div>
                    <div class="ar-rule-desc">Berapa hari ke depan reservasi bisa diajukan sebelum hari-H.</div>
                    <div class="ar-rule-input mt-3">
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" name="max_advance_days"
                                   min="0" max="365" value="<?= $s('max_advance_days', '30') ?>">
                            <span class="input-group-text">hari</span>
                        </div>
                        <div class="mt-1 text-muted" style="font-size:0.7rem">0 = tidak dibatasi</div>
                    </div>
                </div>
            </div>

            <!-- min_advance_hours -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="ar-rule-card">
                    <div class="ar-rule-icon" style="background:#fef3c7;color:#d97706">
                        <i class="bi bi-alarm"></i>
                    </div>
                    <div class="ar-rule-title">Min. Persiapan</div>
                    <div class="ar-rule-desc">Minimum jam sebelum acara dimulai agar reservasi bisa dikirim.</div>
                    <div class="ar-rule-input mt-3">
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" name="min_advance_hours"
                                   min="0" max="168" value="<?= $s('min_advance_hours', '1') ?>">
                            <span class="input-group-text">jam</span>
                        </div>
                        <div class="mt-1 text-muted" style="font-size:0.7rem">0 = tidak dibatasi</div>
                    </div>
                </div>
            </div>

            <!-- max_active_per_user -->
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="ar-rule-card">
                    <div class="ar-rule-icon" style="background:#f0fdf4;color:#16a34a">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="ar-rule-title">Kuota per Pengguna</div>
                    <div class="ar-rule-desc">Batas reservasi berstatus Pending/Approved per pengguna secara bersamaan.</div>
                    <div class="ar-rule-input mt-3">
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control" name="max_active_per_user"
                                   min="0" max="100" value="<?= $s('max_active_per_user', '5') ?>">
                            <span class="input-group-text">res.</span>
                        </div>
                        <div class="mt-1 text-muted" style="font-size:0.7rem">0 = tidak dibatasi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Action Bar ── -->
    <div class="ar-action-bar">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">Batal</a>
        <button type="submit" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-check-lg me-1"></i>Simpan Aturan
        </button>
    </div>

</div><!-- /.ar-config-card -->
</form>

<script>
(function () {
    var chips      = document.querySelectorAll('#durationPresets .ar-preset-chip');
    var hidden     = document.getElementById('maxDurationHidden');
    var customWrap = document.getElementById('customDurWrap');
    var customNum  = document.getElementById('customDurNum');

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            chips.forEach(function (c) { c.classList.remove('selected'); });
            chip.classList.add('selected');
            var val = chip.dataset.value;
            if (val === 'custom') {
                customWrap.classList.remove('d-none');
                hidden.value = customNum.value || '';
            } else {
                customWrap.classList.add('d-none');
                hidden.value = val;
            }
        });
    });

    if (customNum) {
        customNum.addEventListener('input', function () {
            var activeChip = document.querySelector('#durationPresets .ar-preset-chip.selected');
            if (activeChip && activeChip.dataset.value === 'custom') {
                hidden.value = this.value;
            }
        });
    }
})();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
