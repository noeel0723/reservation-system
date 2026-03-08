<?php
/**
 * Proses Approve/Reject Reservasi
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/kelola_reservasi.php');
    exit;
}

requireCsrf();

$reservationId = (int)($_POST['reservation_id'] ?? 0);
$action = $_POST['action'] ?? '';
// Accept 'Finished' from UI but store as 'Selesai' in DB (enum values)
if ($action === 'Finished') {
    $action = 'Selesai';
}
$catatan = trim($_POST['catatan'] ?? '');
$adminId = (int)$_SESSION['user_id'];

if ($reservationId <= 0 || !in_array($action, ['Approved', 'Rejected', 'Selesai', 'Finished'], true)) {
    setFlash('error', 'Parameter tidak valid.');
    header('Location: ' . BASE_URL . '/admin/kelola_reservasi.php');
    exit;
}

// Fetch reservation before updating (for waitlist check)
$resRow = $pdo->prepare("SELECT resource_id, waktu_mulai, waktu_selesai FROM reservations WHERE id = :id");
$resRow->execute([':id' => $reservationId]);
$resData = $resRow->fetch();

$result = updateReservationStatus($pdo, $reservationId, $action, $adminId, $catatan ?: null);

if ($result['success']) {
    // Log activity
    logActivity($pdo, strtolower($action), 'reservation', $reservationId,
        "Admin mengubah status reservasi #$reservationId menjadi $action."
        . ($catatan ? " Catatan: $catatan" : ''));

    // Waitlist: notify when a slot is freed
    if (in_array($action, ['Rejected', 'Selesai']) && $resData) {
        checkAndNotifyWaitlist($pdo, (int)$resData['resource_id'],
            $resData['waktu_mulai'], $resData['waktu_selesai']);
    }

    setFlash('success', $result['message']);
} else {
    setFlash('error', $result['message']);
}

$redirectTo = ($_POST['redirect'] ?? '') === 'dashboard'
    ? BASE_URL . '/admin/dashboard.php'
    : BASE_URL . '/admin/kelola_reservasi.php';
header('Location: ' . $redirectTo);
exit;
