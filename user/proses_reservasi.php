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
$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

$respond = function (bool $success, string $message, string $redirect) use ($isAjax): void {
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'redirect' => $redirect,
        ]);
        exit;
    }

    setFlash($success ? 'success' : 'error', $message);
    header('Location: ' . $redirect);
    exit;
};

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
            logActivity($pdo, 'create', 'reservation', (int)($result['id'] ?? 0),
                "User #$userId mengajukan reservasi baru (resource #{$data['resource_id']}).");
            setFlash('success', $result['message']);
            header('Location: ' . BASE_URL . '/user/riwayat.php');
        } else {
            setFlash('error', $result['message']);
            if (!empty($result['conflicts'])) {
                $_SESSION['reservation_conflicts'] = $result['conflicts'];
                // Save form data so waitlist form can pre-fill
                $_SESSION['waitlist_candidate'] = [
                    'resource_id'   => $data['resource_id'],
                    'keperluan'     => $data['keperluan'],
                    'keterangan'    => $data['keterangan'],
                    'waktu_mulai'   => $_POST['waktu_mulai'],
                    'waktu_selesai' => $_POST['waktu_selesai'],
                ];
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
        // Fetch resource info before cancelling (for waitlist check)
        $resRow = $pdo->prepare("SELECT resource_id, waktu_mulai, waktu_selesai FROM reservations WHERE id = :id AND user_id = :uid");
        $resRow->execute([':id' => $reservationId, ':uid' => $userId]);
        $resData = $resRow->fetch();

        $result = cancelReservation($pdo, $reservationId, $userId);
        if ($result['success']) {
            logActivity($pdo, 'cancel', 'reservation', $reservationId,
                "User #$userId membatalkan reservasi #$reservationId.");
            // Notify waitlist
            if ($resData) {
                checkAndNotifyWaitlist($pdo, (int)$resData['resource_id'],
                    $resData['waktu_mulai'], $resData['waktu_selesai']);
            }
        }
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
        header('Location: ' . BASE_URL . '/user/riwayat.php');
        exit;

    // ---- Feature 10: Waitlist actions ----
    case 'join_waitlist':
        $resourceId   = (int)($_POST['resource_id']   ?? 0);
        $keperluan    = trim($_POST['keperluan']    ?? '');
        $keterangan   = trim($_POST['keterangan']   ?? '');
        $waktuMulai   = trim($_POST['waktu_mulai']   ?? '');
        $waktuSelesai = trim($_POST['waktu_selesai'] ?? '');

        if ($resourceId <= 0 || empty($keperluan) || empty($waktuMulai) || empty($waktuSelesai)) {
            setFlash('error', 'Data antrian tidak lengkap.');
            header('Location: ' . BASE_URL . '/user/reservasi_baru.php');
            exit;
        }

        $waktuMulai   = date('Y-m-d H:i:s', strtotime($waktuMulai));
        $waktuSelesai = date('Y-m-d H:i:s', strtotime($waktuSelesai));

        $stmt = $pdo->prepare(
            "INSERT INTO waitlist (user_id, resource_id, keperluan, keterangan, waktu_mulai, waktu_selesai)
             VALUES (:uid, :rid, :kep, :ket, :wm, :ws)"
        );
        $stmt->execute([
            ':uid' => $userId,     ':rid' => $resourceId,
            ':kep' => $keperluan,  ':ket' => $keterangan ?: null,
            ':wm'  => $waktuMulai, ':ws'  => $waktuSelesai,
        ]);
        $wid = (int)$pdo->lastInsertId();
        logActivity($pdo, 'create', 'waitlist', $wid,
            "User #$userId mendaftar antrian untuk resource #$resourceId.");
        unset($_SESSION['waitlist_candidate'], $_SESSION['reservation_conflicts']);
        setFlash('success', 'Anda telah terdaftar dalam antrian. Kami akan memberi tahu jika slot tersedia.');
        header('Location: ' . BASE_URL . '/user/waitlist.php');
        exit;

    case 'cancel_waitlist':
        $wid = (int)($_POST['waitlist_id'] ?? 0);
        if ($wid <= 0) { $respond(false, 'ID antrian tidak valid.', BASE_URL . '/user/waitlist.php'); }
        // Security: only allow cancelling own entry
        $own = $pdo->prepare("SELECT id FROM waitlist WHERE id = :id AND user_id = :uid");
        $own->execute([':id' => $wid, ':uid' => $userId]);
        if (!$own->fetch()) { $respond(false, 'Antrian tidak ditemukan.', BASE_URL . '/user/waitlist.php'); }

        $pdo->prepare("UPDATE waitlist SET status = 'Cancelled' WHERE id = :id")->execute([':id' => $wid]);
        logActivity($pdo, 'cancel', 'waitlist', $wid, "User #$userId membatalkan antrian #$wid.");
        $respond(true, 'Antrian berhasil dibatalkan.', BASE_URL . '/user/waitlist.php');

    case 'convert_waitlist':
        $wid = (int)($_POST['waitlist_id'] ?? 0);
        if ($wid <= 0) { $respond(false, 'ID antrian tidak valid.', BASE_URL . '/user/waitlist.php'); }

        $entry = $pdo->prepare(
            "SELECT * FROM waitlist WHERE id = :id AND user_id = :uid AND status = 'Notified'"
        );
        $entry->execute([':id' => $wid, ':uid' => $userId]);
        $w = $entry->fetch();
        if (!$w) { $respond(false, 'Antrian tidak ditemukan atau belum siap dikonversi.', BASE_URL . '/user/waitlist.php'); }

        $data = [
            'user_id'       => $userId,
            'resource_id'   => $w['resource_id'],
            'keperluan'     => $w['keperluan'],
            'keterangan'    => $w['keterangan'],
            'waktu_mulai'   => $w['waktu_mulai'],
            'waktu_selesai' => $w['waktu_selesai'],
        ];
        $result = createReservation($pdo, $data);
        if ($result['success']) {
            $pdo->prepare("UPDATE waitlist SET status = 'Converted' WHERE id = :id")->execute([':id' => $wid]);
            logActivity($pdo, 'create', 'reservation', (int)($result['id'] ?? 0),
                "User #$userId mengonversi antrian #$wid menjadi reservasi.");
            $respond(true, 'Reservasi berhasil dibuat dari antrian!', BASE_URL . '/user/riwayat.php');
        } else {
            $respond(false, $result['message'], BASE_URL . '/user/waitlist.php');
        }

    default:
        setFlash('error', 'Aksi tidak valid.');
        header('Location: ' . BASE_URL . '/user/dashboard.php');
        exit;
}
