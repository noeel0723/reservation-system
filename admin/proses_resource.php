<?php
/**
 * Proses CRUD Resource — with foto upload + spesifikasi (Feature 9)
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

// ---- File upload helper ----
function handleFotoUpload(): ?string
{
    if (empty($_FILES['foto']['name'])) {
        return null;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/resources/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $tmpPath  = $_FILES['foto']['tmp_name'];
    $origName = $_FILES['foto']['name'];
    $size     = $_FILES['foto']['size'];
    $err      = $_FILES['foto']['error'];

    if ($err !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($size > 2 * 1024 * 1024) {
        setFlash('error', 'Ukuran foto maksimal 2 MB.');
        return 'ERROR';
    }

    // Validate MIME type (don't trust extension alone)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime     = finfo_file($finfo, $tmpPath);
    finfo_close($finfo);
    $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed, true)) {
        setFlash('error', 'Format foto tidak didukung. Gunakan JPG, PNG, atau WebP.');
        return 'ERROR';
    }

    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $extMap   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $ext      = $extMap[$mime] ?? 'jpg';
    $filename = 'resource_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (!move_uploaded_file($tmpPath, $uploadDir . $filename)) {
        setFlash('error', 'Gagal menyimpan foto. Periksa permission folder uploads.');
        return 'ERROR';
    }

    return 'assets/uploads/resources/' . $filename;
}

switch ($action) {
    case 'create':
        $fotoPath = handleFotoUpload();
        if ($fotoPath === 'ERROR') { break; }

        $data = [
            'nama'         => trim($_POST['nama'] ?? ''),
            'tipe'         => $_POST['tipe'] ?? 'Studio',
            'deskripsi'    => trim($_POST['deskripsi'] ?? ''),
            'lokasi'       => trim($_POST['lokasi'] ?? ''),
            'kapasitas'    => !empty($_POST['kapasitas']) ? (int)$_POST['kapasitas'] : null,
            'spesifikasi'  => trim($_POST['spesifikasi'] ?? ''),
            'foto'         => $fotoPath,
            'is_available' => (int)($_POST['is_available'] ?? 1),
        ];

        if (empty($data['nama'])) {
            setFlash('error', 'Nama resource wajib diisi.');
            break;
        }

        $result = createResource($pdo, $data);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
        if ($result['success']) {
            logActivity($pdo, 'create', 'resource', (int)$result['id'],
                "Admin menambahkan resource '{$data['nama']}' ({$data['tipe']}).");
        }
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { setFlash('error', 'ID resource tidak valid.'); break; }

        $fotoPath  = handleFotoUpload();
        if ($fotoPath === 'ERROR') { break; }

        $data = [
            'nama'         => trim($_POST['nama'] ?? ''),
            'tipe'         => $_POST['tipe'] ?? 'Studio',
            'deskripsi'    => trim($_POST['deskripsi'] ?? ''),
            'lokasi'       => trim($_POST['lokasi'] ?? ''),
            'kapasitas'    => !empty($_POST['kapasitas']) ? (int)$_POST['kapasitas'] : null,
            'spesifikasi'  => trim($_POST['spesifikasi'] ?? ''),
            'is_available' => (int)($_POST['is_available'] ?? 1),
        ];
        if ($fotoPath !== null) { $data['foto'] = $fotoPath; }

        $result = updateResource($pdo, $id, $data);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
        if ($result['success']) {
            logActivity($pdo, 'update', 'resource', $id,
                "Admin memperbarui resource '{$data['nama']}' (#$id).");
        }
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { setFlash('error', 'ID resource tidak valid.'); break; }

        // Fetch name before deleting for log
        $resName = $pdo->prepare("SELECT nama FROM resources WHERE id = :id");
        $resName->execute([':id' => $id]);
        $rn = $resName->fetchColumn();

        $result = deleteResource($pdo, $id);
        setFlash($result['success'] ? 'success' : 'error', $result['message']);
        if ($result['success']) {
            logActivity($pdo, 'delete', 'resource', $id,
                "Admin menghapus resource '$rn' (#$id).");
        }
        break;

    case 'toggle_availability':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { setFlash('error', 'ID tidak valid.'); break; }

        $pdo->prepare("UPDATE resources SET is_available = NOT is_available WHERE id = :id")
            ->execute([':id' => $id]);
        setFlash('success', 'Status ketersediaan resource berhasil diubah.');
        logActivity($pdo, 'update', 'resource', $id,
            "Admin mengubah status ketersediaan resource #$id.");
        break;

    default:
        setFlash('error', 'Aksi tidak valid.');
}

header('Location: ' . BASE_URL . '/admin/kelola_resource.php');
exit;


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
