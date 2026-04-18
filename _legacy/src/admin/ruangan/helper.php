<?php
// File helper untuk fungsi-fungsi yang digunakan bersama di CRUD ruangan

function generateFileName($namaFile, $ruanganId)
{
    $ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
    $token = bin2hex(random_bytes(6));
    return 'ruangan_' . $ruanganId . '_' . time() . '_' . $token . '.' . $ext;
}

function getUploadDir()
{
    $dir = __DIR__ . "/../../uploads/ruangan";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function deletePhotoFile($fileName)
{
    if (empty($fileName)) {
        return;
    }
    $uploadDir = getUploadDir();
    $path = $uploadDir . '/' . $fileName;
    if (is_file($path)) {
        unlink($path);
    }
}

function validateImageFile(array $file)
{
    $maxSize = 2 * 1024 * 1024; // 2MB
    if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload foto gagal: " . uploadErrorMessage((int)$file['error']));
    }
    if (!empty($file['size']) && $file['size'] > $maxSize) {
        throw new Exception("Ukuran foto terlalu besar (max 2MB)");
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime, $allowedTypes, true)) {
        throw new Exception("Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF. Terdeteksi: " . $mime);
    }
}

function uploadImageFile(array $file, $ruanganId)
{
    validateImageFile($file);
    $uploadDir = getUploadDir();
    $fileName = generateFileName($file['name'], $ruanganId);
    $uploadPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception("Gagal mengupload foto: tidak dapat memindahkan file ke folder upload");
    }
    return $fileName;
}

function uploadErrorMessage(int $code)
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return "Ukuran file melebihi batas upload di server (php.ini)";
        case UPLOAD_ERR_FORM_SIZE:
            return "Ukuran file melebihi batas yang ditentukan pada form";
        case UPLOAD_ERR_PARTIAL:
            return "File hanya ter-upload sebagian";
        case UPLOAD_ERR_NO_FILE:
            return "Tidak ada file yang diupload";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Folder temporary tidak ditemukan";
        case UPLOAD_ERR_CANT_WRITE:
            return "Gagal menulis file ke disk";
        case UPLOAD_ERR_EXTENSION:
            return "Upload dihentikan oleh ekstensi PHP";
        case UPLOAD_ERR_OK:
            return "OK";
        default:
            return "Error tidak dikenal (kode: " . $code . ")";
    }
}

function normalizeFiles(array $files)
{
    $normalized = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $normalized;
    }
    foreach ($files['name'] as $i => $name) {
        if (empty($name)) {
            continue;
        }
        $normalized[] = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $normalized;
}

function sanitizeIdList($rawIds)
{
    if (!is_array($rawIds)) {
        return [];
    }

    $result = [];
    foreach ($rawIds as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $result[] = $id;
        }
    }

    return array_values(array_unique($result));
}

function getValidFasilitasIds(array $ids)
{
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $rows = query("SELECT id FROM fasilitas WHERE id IN ($placeholders)", $ids)->fetchAll();

    $validIds = [];
    foreach ($rows as $row) {
        $validIds[] = (int)$row['id'];
    }

    return array_values(array_unique($validIds));
}

function syncRuanganFasilitas(int $ruanganId, array $fasilitasIds)
{
    query("DELETE FROM ruangan_fasilitas WHERE ruangan_id = ?", [$ruanganId]);

    foreach ($fasilitasIds as $fasilitasId) {
        query(
            "INSERT INTO ruangan_fasilitas (ruangan_id, fasilitas_id) VALUES (?, ?)",
            [$ruanganId, (int)$fasilitasId]
        );
    }
}

function getValidLantaiId($lantaiId)
{
    $lantaiId = (int)$lantaiId;
    if ($lantaiId <= 0) {
        return null;
    }

    $row = query(
        "SELECT l.id, l.gedung_id, l.nomor, g.nama_gedung
         FROM lantai l
         INNER JOIN gedung g ON g.id = l.gedung_id
         WHERE l.id = ?
         LIMIT 1",
        [$lantaiId]
    )->fetch();

    return $row ?: null;
}
