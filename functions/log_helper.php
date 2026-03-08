<?php
/**
 * Log Helper — Activity Logging & Application Settings
 */

/**
 * Log an activity to activity_logs.
 * Silently fails so it never breaks the main request flow.
 */
function logActivity(PDO $pdo, string $action, string $entityType, ?int $entityId, string $description): void
{
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO activity_logs (user_id, user_nama, action, entity_type, entity_id, description, ip_address)
             VALUES (:uid, :uname, :action, :etype, :eid, :desc, :ip)"
        );
        $stmt->execute([
            ':uid'    => $_SESSION['user_id']     ?? null,
            ':uname'  => $_SESSION['nama_lengkap'] ?? null,
            ':action' => $action,
            ':etype'  => $entityType,
            ':eid'    => $entityId,
            ':desc'   => $description,
            ':ip'     => $_SERVER['REMOTE_ADDR']  ?? null,
        ]);
    } catch (Throwable $e) {
        // Silent — logging must never interrupt the main flow
    }
}

/**
 * Get a single setting value (with static in-request cache).
 */
function getSetting(PDO $pdo, string $key, $default = null)
{
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
        try {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = :key");
            $stmt->execute([':key' => $key]);
            $row = $stmt->fetch();
            $cache[$key] = $row ? $row['value'] : $default;
        } catch (Throwable $e) {
            $cache[$key] = $default;
        }
    }
    return $cache[$key];
}

/**
 * Get all settings as key => full-row array.
 */
function getAllSettings(PDO $pdo): array
{
    try {
        $rows = $pdo->query(
            "SELECT * FROM settings
             ORDER BY FIELD(`key`,
                'max_duration_hours','max_advance_days','min_advance_hours',
                'max_active_per_user','booking_start_hour','booking_end_hour')"
        )->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row;
        }
        return $result;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Expire waitlist entries whose scheduled time has already passed.
 */
function expireOldWaitlist(PDO $pdo): void
{
    try {
        $pdo->exec(
            "UPDATE waitlist SET status = 'Expired'
             WHERE status IN ('Waiting', 'Notified')
               AND waktu_mulai < NOW()"
        );
    } catch (Throwable $e) {}
}

/**
 * After a reservation is freed (Rejected/Cancelled/Selesai), check if any
 * waitlist entry for the same resource + overlapping time can be notified.
 * Returns count of entries newly notified.
 */
function checkAndNotifyWaitlist(PDO $pdo, int $resourceId, string $waktuMulai, string $waktuSelesai): int
{
    try {
        expireOldWaitlist($pdo);

        // Find 'Waiting' entries that overlap with the freed slot
        $stmt = $pdo->prepare(
            "SELECT * FROM waitlist
             WHERE resource_id = :rid
               AND status = 'Waiting'
               AND waktu_mulai  < :wend
               AND waktu_selesai > :wstart
             ORDER BY created_at ASC"
        );
        $stmt->execute([':rid' => $resourceId, ':wend' => $waktuSelesai, ':wstart' => $waktuMulai]);
        $entries = $stmt->fetchAll();

        $notified = 0;
        foreach ($entries as $entry) {
            // Only notify if the specific slot they want is now conflict-free
            $collision = checkCollision($pdo, $resourceId, $entry['waktu_mulai'], $entry['waktu_selesai']);
            if (!$collision['is_collision']) {
                $pdo->prepare(
                    "UPDATE waitlist SET status = 'Notified', notified_at = NOW() WHERE id = :id"
                )->execute([':id' => $entry['id']]);
                $notified++;
            }
        }
        return $notified;
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Get waitlist entries with resource + user name.
 * Pass $userId to filter for one user; leave null for all (admin).
 */
function getWaitlistEntries(PDO $pdo, ?int $userId = null, string $status = ''): array
{
    $sql   = "SELECT w.*, r.nama AS resource_nama, r.tipe AS resource_tipe,
                     u.nama_lengkap AS user_nama, u.jabatan
              FROM waitlist w
              JOIN resources r ON w.resource_id = r.id
              JOIN users u     ON w.user_id = u.id
              WHERE 1=1";
    $params = [];

    if ($userId !== null) {
        $sql .= " AND w.user_id = :uid";
        $params[':uid'] = $userId;
    }
    if ($status !== '') {
        $sql .= " AND w.status = :status";
        $params[':status'] = $status;
    }

    $sql .= " ORDER BY w.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
