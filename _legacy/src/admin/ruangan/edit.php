<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";
require_once __DIR__ . "/helper.php";

// Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

try {
    $id = (int)($_POST['id'] ?? 0);
    $nama_ruangan = $_POST['nama_ruangan'] ?? '';
    $lantaiId = (int)($_POST['lantai_id'] ?? 0);
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);
    $deskripsi = $_POST['deskripsi'] ?? '';
    $fasilitasIds = getValidFasilitasIds(sanitizeIdList($_POST['fasilitas_ids'] ?? []));

    if (empty($id) || empty($nama_ruangan)) {
        throw new Exception("Data tidak valid");
    }
    if ($lantaiId <= 0) {
        throw new Exception("Lantai tidak boleh kosong");
    }
    if ($kapasitas <= 0) {
        throw new Exception("Kapasitas wajib diisi dan harus lebih dari 0");
    }

    $validLantai = getValidLantaiId($lantaiId);
    if (!$validLantai) {
        throw new Exception("Lantai tidak valid");
    }

    // Get ruangan saat ini
    $stmt = query("SELECT * FROM ruangan WHERE id = ?", [$id]);
    $ruangan = $stmt->fetch();

    if (!$ruangan) {
        throw new Exception("Ruangan tidak ditemukan");
    }

    // Hapus foto terpilih
    $deleteFotoIds = $_POST['delete_foto'] ?? [];
    if (!empty($deleteFotoIds) && is_array($deleteFotoIds)) {
        foreach ($deleteFotoIds as $fotoId) {
            $fotoId = (int)$fotoId;
            if ($fotoId <= 0) {
                continue;
            }
            $fotoRow = query(
                "SELECT id, nama_file FROM ruangan_foto WHERE id = ? AND ruangan_id = ?",
                [$fotoId, $id]
            )->fetch();
            if ($fotoRow) {
                deletePhotoFile($fotoRow['nama_file']);
                query("DELETE FROM ruangan_foto WHERE id = ?", [$fotoId]);
            }
        }
    }

    // Upload foto sampul baru (replace cover lama)
    if (!empty($_FILES['foto_cover']['name'])) {
        $coverRows = query(
            "SELECT id, nama_file FROM ruangan_foto WHERE ruangan_id = ? AND tipe = 'cover'",
            [$id]
        )->fetchAll();
        foreach ($coverRows as $row) {
            deletePhotoFile($row['nama_file']);
            query("DELETE FROM ruangan_foto WHERE id = ?", [$row['id']]);
        }
        $fileName = uploadImageFile($_FILES['foto_cover'], $id);
        query(
            "INSERT INTO ruangan_foto (ruangan_id, nama_file, tipe) VALUES (?, ?, 'cover')",
            [$id, $fileName]
        );
    }

    // Upload foto detail baru (multiple)
    if (!empty($_FILES['foto_detail']['name'])) {
        $detailFiles = normalizeFiles($_FILES['foto_detail']);
        foreach ($detailFiles as $file) {
            $fileName = uploadImageFile($file, $id);
            query(
                "INSERT INTO ruangan_foto (ruangan_id, nama_file, tipe) VALUES (?, ?, 'detail')",
                [$id, $fileName]
            );
        }
    }

    // Update database
    query(
        "UPDATE ruangan SET nama_ruangan = ?, lantai_id = ?, kapasitas = ?, deskripsi = ? WHERE id = ?",
        [$nama_ruangan, $lantaiId, $kapasitas, $deskripsi, $id]
    );

    syncRuanganFasilitas((int)$id, $fasilitasIds);

    header("Location: ../ruangan.php?success=edit");
    exit;
} catch (Exception $e) {
    header("Location: ../ruangan.php?error=" . urlencode($e->getMessage()));
    exit;
}
