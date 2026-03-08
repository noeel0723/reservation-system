<?php
/**
 * API: Resource Availability for a given date
 * Returns JSON array of existing bookings (Approved/Pending)
 */
require_once __DIR__ . '/../config/init.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');

$resourceId = (int)($_GET['resource_id'] ?? 0);
$date       = $_GET['date'] ?? '';

// Strict validation
if ($resourceId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode([]);
    exit;
}

// Range check: reasonable date range only (no SSRF-style abuse)
$dateObj = DateTime::createFromFormat('Y-m-d', $date);
if (!$dateObj) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT r.waktu_mulai, r.waktu_selesai, r.keperluan, r.status
     FROM reservations r
     WHERE r.resource_id = :rid
       AND DATE(r.waktu_mulai) = :date
       AND r.status IN ('Approved', 'Pending')
     ORDER BY r.waktu_mulai"
);
$stmt->execute([':rid' => $resourceId, ':date' => $date]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
