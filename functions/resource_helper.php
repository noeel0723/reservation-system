<?php
/**
 * Resource Helper Functions
 * CRUD untuk Studio & Alat
 */

/**
 * Ambil semua resources
 */
function getResources(PDO $pdo, ?string $tipe = null, bool $onlyAvailable = false): array
{
    $sql = "SELECT * FROM resources WHERE 1=1";
    $params = [];

    if ($tipe !== null) {
        $sql .= " AND tipe = :tipe";
        $params[':tipe'] = $tipe;
    }
    if ($onlyAvailable) {
        $sql .= " AND is_available = 1";
    }

    $sql .= " ORDER BY tipe ASC, nama ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Ambil satu resource by ID
 */
function getResourceById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM resources WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Tambah resource baru
 */
function createResource(PDO $pdo, array $data): array
{
    $sql = "INSERT INTO resources (nama, tipe, deskripsi, lokasi, kapasitas, spesifikasi, foto, is_available)
            VALUES (:nama, :tipe, :deskripsi, :lokasi, :kapasitas, :spesifikasi, :foto, :is_available)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nama'         => $data['nama'],
        ':tipe'         => $data['tipe'],
        ':deskripsi'    => $data['deskripsi']   ?? null,
        ':lokasi'       => $data['lokasi']      ?? null,
        ':kapasitas'    => $data['kapasitas']   ?? null,
        ':spesifikasi'  => $data['spesifikasi'] ?? null,
        ':foto'         => $data['foto']        ?? null,
        ':is_available' => $data['is_available'] ?? 1,
    ]);

    return ['success' => true, 'message' => 'Resource berhasil ditambahkan.', 'id' => $pdo->lastInsertId()];
}

/**
 * Update resource
 */
function updateResource(PDO $pdo, int $id, array $data): array
{
    $params = [
        ':nama'          => $data['nama'],
        ':tipe'          => $data['tipe'],
        ':deskripsi'     => $data['deskripsi']    ?? null,
        ':lokasi'        => $data['lokasi']       ?? null,
        ':kapasitas'     => $data['kapasitas']    ?? null,
        ':spesifikasi'   => $data['spesifikasi']  ?? null,
        ':is_available'  => $data['is_available'] ?? 1,
        ':id'            => $id,
    ];

    $fotoClause = !empty($data['foto']) ? ', foto = :foto' : '';
    if (!empty($data['foto'])) { $params[':foto'] = $data['foto']; }

    $sql = "UPDATE resources SET
                nama = :nama, tipe = :tipe, deskripsi = :deskripsi,
                lokasi = :lokasi, kapasitas = :kapasitas,
                spesifikasi = :spesifikasi {$fotoClause},
                is_available = :is_available
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return ['success' => true, 'message' => 'Resource berhasil diperbarui.'];
}

/**
 * Hapus resource
 */
function deleteResource(PDO $pdo, int $id): array
{
    // Cek apakah ada reservasi aktif
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM reservations WHERE resource_id = :id AND status IN ('Approved', 'Pending')");
    $stmt->execute([':id' => $id]);
    $count = $stmt->fetch()['cnt'];

    if ($count > 0) {
        return ['success' => false, 'message' => 'Tidak bisa menghapus. Masih ada ' . $count . ' reservasi aktif untuk resource ini.'];
    }

    $stmt = $pdo->prepare("DELETE FROM resources WHERE id = :id");
    $stmt->execute([':id' => $id]);

    return ['success' => true, 'message' => 'Resource berhasil dihapus.'];
}
