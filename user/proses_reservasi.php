<?php
/**
 * Proses Reservasi User (Create / Cancel)
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/reservation_helper.php';

requireStaff();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/user/dashboard.php');
    exit;
}

requireCsrf();

$action = $_POST['action'] ?? '';
$userId = (int)$_SESSION['user_id'];

switch ($action) {
    case 'create':
        $data = [
            'user_id'       => $userId,
            'resource_id'   => (int)($_POST['resource_id'] ?? 0),
            'keperluan'     => trim($_POST['keperluan'] ?? ''),
            'keterangan'    => trim($_POST['keterangan'] ?? ''),
            'waktu_mulai'   => $_POST['waktu_mulai'] ?? '',
            'waktu_selesai' => $_POST['waktu_selesai'] ?? '',
        ];

        // Validasi dasar
        if (empty($data['resource_id']) || empty($data['keperluan']) || empty($data['waktu_mulai']) || empty($data['waktu_selesai'])) {
            setFlash('error', 'Semua field wajib harus diisi.');
            // Simpan form data untuk repopulate
            $_SESSION['form_data'] = $data;
            header('Location: ' . BASE_URL . '/user/reservasi_baru.php');
            exit;
        }

        // Format datetime
        $data['waktu_mulai'] = date('Y-m-d H:i:s', strtotime($data['waktu_mulai']));
        $data['waktu_selesai'] = date('Y-m-d H:i:s', strtotime($data['waktu_selesai']));

        $result = null;
        $isRecurring   = !empty($_POST['is_recurring']);
        $recurringType = in_array($_POST['recurring_type'] ?? '', ['weekly', 'monthly']) ? $_POST['recurring_type'] : 'weekly';
        $recurringCount = max(2, min(12, (int)($_POST['recurring_count'] ?? 2)));

        if ($isRecurring) {
            $result = createRecurringReservation($pdo, $data, $recurringType, $recurringCount);
        } else {
            $result = createReservation($pdo, $data);
        }

        if ($result['success']) {
            setFlash('success', $result['message']);
            header('Location: ' . BASE_URL . '/user/riwayat.php');
        } else {
            setFlash('error', $result['message']);
            if (!empty($result['conflicts'])) {
                $_SESSION['reservation_conflicts'] = $result['conflicts'];
            }
            $_SESSION['form_data'] = [
                'resource_id'   => $data['resource_id'],
                'keperluan'     => $data['keperluan'],
                'keterangan'    => $data['keterangan'],
                'waktu_mulai'   => $_POST['waktu_mulai'],
                'waktu_selesai' => $_POST['waktu_selesai'],
            ];
            header('Location: ' . BASE_URL . '/user/reservasi_baru.php');
        }
        exit;

    case 'cancel':
        $reservationId = (int)($_POST['reservation_id'] ?? 0);
        $result = cancelReservation($pdo, $reservationId, $userId);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
        header('Location: ' . BASE_URL . '/user/riwayat.php');
        exit;

    default:
        setFlash('error', 'Aksi tidak valid.');
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit;
}
