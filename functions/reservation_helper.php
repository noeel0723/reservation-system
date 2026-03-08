<?php
/**
 * Reservation Helper Functions
 * Core logic termasuk Collision Detection
 */

/**
 * ============================================================
 * COLLISION DETECTION - Jantung dari sistem reservasi
 * ============================================================
 *
 * Logika: Dua rentang waktu [A_start, A_end] dan [B_start, B_end]
 * dikatakan BENTROK (overlap) jika dan hanya jika:
 *
 *   A_start < B_end  AND  A_end > B_start
 *
 * Contoh visual:
 *
 *   Existing:  |=======|          (10:00 - 12:00)
 *   Case 1:        |=======|     (11:00 - 13:00) → BENTROK ✗
 *   Case 2:  |===|                (09:00 - 10:30) → BENTROK ✗
 *   Case 3:            |===|     (12:00 - 14:00) → TIDAK BENTROK ✓
 *   Case 4:  |==|                (08:00 - 10:00) → TIDAK BENTROK ✓
 *
 * Query hanya mengecek reservasi berstatus 'Approved' dan 'Pending'
 * untuk resource yang sama.
 * ============================================================
 */

/**
 * Cek apakah ada jadwal yang bentrok
 *
 * @param PDO    $pdo
 * @param int    $resourceId
 * @param string $waktuMulai   Format: Y-m-d H:i:s
 * @param string $waktuSelesai  Format: Y-m-d H:i:s
 * @param int|null $excludeId  ID reservasi yang dikecualikan (untuk edit)
 * @return array  ['is_collision' => bool, 'conflicts' => array]
 */
function checkCollision(PDO $pdo, int $resourceId, string $waktuMulai, string $waktuSelesai, ?int $excludeId = null): array
{
    $sql = "SELECT r.id, r.waktu_mulai, r.waktu_selesai, r.keperluan, r.status,
                   u.nama_lengkap AS peminjam
            FROM reservations r
            JOIN users u ON r.user_id = u.id
            WHERE r.resource_id = :resource_id
              AND r.status IN ('Approved', 'Pending')
              AND r.waktu_mulai < :waktu_selesai
              AND r.waktu_selesai > :waktu_mulai";

    $params = [
        ':resource_id'  => $resourceId,
        ':waktu_mulai'  => $waktuMulai,
        ':waktu_selesai' => $waktuSelesai,
    ];

    if ($excludeId !== null) {
        $sql .= " AND r.id != :exclude_id";
        $params[':exclude_id'] = $excludeId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $conflicts = $stmt->fetchAll();

    return [
        'is_collision' => count($conflicts) > 0,
        'conflicts'    => $conflicts,
    ];
}

/**
 * Buat reservasi baru (status default: Pending)
 */
function createReservation(PDO $pdo, array $data): array
{
    // Validasi waktu
    $mulai   = new DateTime($data['waktu_mulai']);
    $selesai = new DateTime($data['waktu_selesai']);

    if ($selesai <= $mulai) {
        return ['success' => false, 'message' => 'Waktu selesai harus setelah waktu mulai.'];
    }

    // ---- Validasi Aturan Reservasi (Feature 6) ----
    $now = new DateTime();

    $minAdvH = (int)getSetting($pdo, 'min_advance_hours', 1);
    if ($minAdvH > 0 && $mulai < (clone $now)->modify("+{$minAdvH} hours")) {
        return ['success' => false, 'message' => "Reservasi harus diajukan minimal {$minAdvH} jam sebelum waktu mulai."];
    }

    $maxAdvD = (int)getSetting($pdo, 'max_advance_days', 30);
    if ($maxAdvD > 0 && $mulai > (clone $now)->modify("+{$maxAdvD} days")) {
        return ['success' => false, 'message' => "Reservasi tidak bisa dibuat lebih dari {$maxAdvD} hari ke depan."];
    }

    $maxDurH = (int)getSetting($pdo, 'max_duration_hours', 8);
    if ($maxDurH > 0) {
        $durHours = ($selesai->getTimestamp() - $mulai->getTimestamp()) / 3600;
        if ($durHours > $maxDurH) {
            return ['success' => false, 'message' => "Durasi reservasi tidak boleh lebih dari {$maxDurH} jam."];
        }
    }

    $startHour = getSetting($pdo, 'booking_start_hour', '06:00');
    $endHour   = getSetting($pdo, 'booking_end_hour',   '22:00');
    if ($mulai->format('H:i') < $startHour) {
        return ['success' => false, 'message' => "Waktu mulai reservasi tidak boleh sebelum jam {$startHour}."];
    }
    if ($selesai->format('H:i') > $endHour) {
        return ['success' => false, 'message' => "Waktu selesai reservasi tidak boleh melewati jam {$endHour}."];
    }

    $maxActive = (int)getSetting($pdo, 'max_active_per_user', 5);
    if ($maxActive > 0) {
        $cntStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM reservations WHERE user_id = :uid AND status IN ('Pending','Approved')"
        );
        $cntStmt->execute([':uid' => $data['user_id']]);
        $activeCount = (int)$cntStmt->fetchColumn();
        if ($activeCount >= $maxActive) {
            return ['success' => false, 'message' => "Anda sudah memiliki {$activeCount} reservasi aktif. Batas maksimal adalah {$maxActive}."];
        }
    }
    // ---- End Aturan Reservasi ----

    // Cek collision
    $collision = checkCollision($pdo, (int)$data['resource_id'], $data['waktu_mulai'], $data['waktu_selesai']);
    if ($collision['is_collision']) {
        $conflictInfo = [];
        foreach ($collision['conflicts'] as $c) {
            $conflictInfo[] = sprintf(
                '%s (%s - %s) oleh %s [%s]',
                $c['keperluan'],
                date('d/m/Y H:i', strtotime($c['waktu_mulai'])),
                date('d/m/Y H:i', strtotime($c['waktu_selesai'])),
                $c['peminjam'],
                $c['status']
            );
        }
        return [
            'success'   => false,
            'message'   => 'Jadwal bentrok dengan reservasi berikut:',
            'conflicts' => $conflictInfo,
        ];
    }

    $sql = "INSERT INTO reservations (user_id, resource_id, keperluan, keterangan, waktu_mulai, waktu_selesai)
            VALUES (:user_id, :resource_id, :keperluan, :keterangan, :waktu_mulai, :waktu_selesai)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id'       => $data['user_id'],
        ':resource_id'   => $data['resource_id'],
        ':keperluan'     => $data['keperluan'],
        ':keterangan'    => $data['keterangan'] ?? null,
        ':waktu_mulai'   => $data['waktu_mulai'],
        ':waktu_selesai' => $data['waktu_selesai'],
    ]);

    return ['success' => true, 'message' => 'Reservasi berhasil diajukan. Menunggu persetujuan Admin.', 'id' => $pdo->lastInsertId()];
}

/**
 * Update status reservasi (Approve/Reject oleh Admin)
 */
function updateReservationStatus(PDO $pdo, int $reservationId, string $status, int $adminId, ?string $catatan = null): array
{
    $allowedStatuses = ['Approved', 'Rejected', 'Cancelled', 'Selesai'];
    if (!in_array($status, $allowedStatuses, true)) {
        return ['success' => false, 'message' => 'Status tidak valid.'];
    }

    // Jika approve, cek collision lagi (untuk mencegah race condition)
    if ($status === 'Approved') {
        $stmt = $pdo->prepare("SELECT resource_id, waktu_mulai, waktu_selesai FROM reservations WHERE id = :id");
        $stmt->execute([':id' => $reservationId]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            return ['success' => false, 'message' => 'Reservasi tidak ditemukan.'];
        }

        $collision = checkCollision(
            $pdo,
            (int)$reservation['resource_id'],
            $reservation['waktu_mulai'],
            $reservation['waktu_selesai'],
            $reservationId
        );

        if ($collision['is_collision']) {
            // Cek apakah ada yang sudah Approved (bukan Pending)
            $approvedConflicts = array_filter($collision['conflicts'], fn($c) => $c['status'] === 'Approved');
            if (!empty($approvedConflicts)) {
                return ['success' => false, 'message' => 'Tidak bisa approve. Jadwal bentrok dengan reservasi lain yang sudah disetujui.'];
            }
        }
    }

    $sql = "UPDATE reservations
            SET status = :status,
                catatan_admin = :catatan,
                approved_by = :admin_id,
                approved_at = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':status'   => $status,
        ':catatan'  => $catatan,
        ':admin_id' => $adminId,
        ':id'       => $reservationId,
    ]);

    return ['success' => true, 'message' => 'Status reservasi berhasil diubah menjadi ' . $status . '.'];
}

/**
 * Ambil semua reservasi dengan filter
 */
function getReservations(PDO $pdo, array $filters = []): array
{
    $sql = "SELECT r.*, res.nama AS resource_nama, res.tipe AS resource_tipe,
                   u.nama_lengkap AS peminjam, u.jabatan,
                   adm.nama_lengkap AS admin_nama
            FROM reservations r
            JOIN resources res ON r.resource_id = res.id
            JOIN users u ON r.user_id = u.id
            LEFT JOIN users adm ON r.approved_by = adm.id
            WHERE 1=1";

    $params = [];

    if (!empty($filters['user_id'])) {
        $sql .= " AND r.user_id = :user_id";
        $params[':user_id'] = $filters['user_id'];
    }
    if (!empty($filters['status'])) {
        $sql .= " AND r.status = :status";
        $params[':status'] = $filters['status'];
    }
    if (!empty($filters['resource_id'])) {
        $sql .= " AND r.resource_id = :resource_id";
        $params[':resource_id'] = $filters['resource_id'];
    }
    if (!empty($filters['date_from'])) {
        $sql .= " AND r.waktu_mulai >= :date_from";
        $params[':date_from'] = $filters['date_from'];
    }
    if (!empty($filters['date_to'])) {
        $sql .= " AND r.waktu_selesai <= :date_to";
        $params[':date_to'] = $filters['date_to'];
    }

    $sql .= " ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Ambil satu reservasi by ID
 */
function getReservationById(PDO $pdo, int $id): ?array
{
    $sql = "SELECT r.*, res.nama AS resource_nama, res.tipe AS resource_tipe,
                   u.nama_lengkap AS peminjam, u.username AS peminjam_username, u.jabatan,
                   adm.nama_lengkap AS admin_nama
            FROM reservations r
            JOIN resources res ON r.resource_id = res.id
            JOIN users u ON r.user_id = u.id
            LEFT JOIN users adm ON r.approved_by = adm.id
            WHERE r.id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Cancel reservasi oleh user (hanya jika status Pending)
 */
function cancelReservation(PDO $pdo, int $reservationId, int $userId): array
{
    $stmt = $pdo->prepare("SELECT id, status, user_id FROM reservations WHERE id = :id");
    $stmt->execute([':id' => $reservationId]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        return ['success' => false, 'message' => 'Reservasi tidak ditemukan.'];
    }
    if ((int)$reservation['user_id'] !== $userId) {
        return ['success' => false, 'message' => 'Anda tidak berhak membatalkan reservasi ini.'];
    }
    if ($reservation['status'] !== 'Pending') {
        return ['success' => false, 'message' => 'Hanya reservasi berstatus Pending yang bisa dibatalkan.'];
    }

    $stmt = $pdo->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = :id");
    $stmt->execute([':id' => $reservationId]);

    return ['success' => true, 'message' => 'Reservasi berhasil dibatalkan.'];
}

/**
 * Buat reservasi berulang (weekly/monthly) sejumlah $count kali
 * Memanggil createReservation() untuk setiap sesi
 *
 * @param PDO    $pdo
 * @param array  $data       Data reservasi dasar (seperti parameter createReservation)
 * @param string $type       'weekly' | 'monthly'
 * @param int    $count      Jumlah total sesi (termasuk sesi pertama), maks 12
 * @return array             ['success', 'created', 'failed', 'message']
 */
function createRecurringReservation(PDO $pdo, array $data, string $type, int $count): array
{
    $count   = max(2, min(12, $count));
    $interval = $type === 'monthly' ? new DateInterval('P1M') : new DateInterval('P7D');

    $mulai   = new DateTime($data['waktu_mulai']);
    $selesai = new DateTime($data['waktu_selesai']);

    $created = 0;
    $failed  = [];

    for ($i = 0; $i < $count; $i++) {
        $d                  = $data;
        $d['waktu_mulai']   = $mulai->format('Y-m-d H:i:s');
        $d['waktu_selesai'] = $selesai->format('Y-m-d H:i:s');

        $result = createReservation($pdo, $d);
        if ($result['success']) {
            $created++;
        } else {
            $failed[] = 'Sesi ' . ($i + 1) . ' (' . $mulai->format('d/m/Y') . '): ' . $result['message'];
        }

        $mulai->add($interval);
        $selesai->add($interval);
    }

    if ($created === 0) {
        return ['success' => false, 'created' => 0, 'message' => 'Semua jadwal gagal dibuat: ' . implode('; ', $failed)];
    }

    $msg = $created . ' dari ' . $count . ' reservasi berhasil diajukan.';
    if ($failed) {
        $msg .= ' ' . count($failed) . ' sesi gagal: ' . implode('; ', $failed);
    }
    return ['success' => true, 'created' => $created, 'message' => $msg];
}

/**
 * Statistik dashboard
 */
function getDashboardStats(PDO $pdo): array
{
    $stats = [];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'Pending'");
    $stats['pending'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'Approved' AND waktu_selesai > NOW()");
    $stats['active'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM resources WHERE is_available = 1");
    $stats['resources'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
    $stats['users'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
    $stats['monthly'] = $stmt->fetch()['total'];

    return $stats;
}

/**
 * Otomatis tandai reservasi yang sudah lewat waktu_selesai sebagai Selesai
 */
function autoMarkSelesai(PDO $pdo): void
{
    $pdo->exec("UPDATE reservations SET status = 'Selesai' WHERE status = 'Approved' AND waktu_selesai < NOW()");
}
