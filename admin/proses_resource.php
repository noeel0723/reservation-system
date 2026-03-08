<?php
/**
 * Proses CRUD Resource
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../functions/resource_helper.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/kelola_resource.php');
    exit;
}

requireCsrf();

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        $data = [
            'nama'         => trim($_POST['nama'] ?? ''),
            'tipe'         => $_POST['tipe'] ?? 'Studio',
            'deskripsi'    => trim($_POST['deskripsi'] ?? ''),
            'lokasi'       => trim($_POST['lokasi'] ?? ''),
            'kapasitas'    => !empty($_POST['kapasitas']) ? (int)$_POST['kapasitas'] : null,
            'is_available' => (int)($_POST['is_available'] ?? 1),
        ];

        if (empty($data['nama'])) {
            setFlash('error', 'Nama resource wajib diisi.');
            break;
        }

        $result = createResource($pdo, $data);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'ID resource tidak valid.');
            break;
        }

        $data = [
            'nama'         => trim($_POST['nama'] ?? ''),
            'tipe'         => $_POST['tipe'] ?? 'Studio',
            'deskripsi'    => trim($_POST['deskripsi'] ?? ''),
            'lokasi'       => trim($_POST['lokasi'] ?? ''),
            'kapasitas'    => !empty($_POST['kapasitas']) ? (int)$_POST['kapasitas'] : null,
            'is_available' => (int)($_POST['is_available'] ?? 1),
        ];

        $result = updateResource($pdo, $id, $data);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            setFlash('error', 'ID resource tidak valid.');
            break;
        }

        $result = deleteResource($pdo, $id);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
        break;

    case 'toggle_availability':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { setFlash('error', 'ID tidak valid.'); break; }
        $stmt = $pdo->prepare("UPDATE resources SET is_available = NOT is_available WHERE id = :id");
        $stmt->execute([':id' => $id]);
        setFlash('success', 'Status ketersediaan resource berhasil diubah.');
        break;

    default:
        setFlash('error', 'Aksi tidak valid.');
}

header('Location: ' . BASE_URL . '/admin/kelola_resource.php');
exit;
