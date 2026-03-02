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
    $nama_ruangan = $_POST['nama_ruangan'] ?? '';
    $lantaiId = (int)($_POST['lantai_id'] ?? 0);
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);
    $deskripsi = $_POST['deskripsi'] ?? '';
    $fasilitasIds = getValidFasilitasIds(sanitizeIdList($_POST['fasilitas_ids'] ?? []));

    if (empty($nama_ruangan)) {
        throw new Exception("Nama ruangan tidak boleh kosong");
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

    // Insert ke database
    query(
        "INSERT INTO ruangan (nama_ruangan, lantai_id, kapasitas, deskripsi) VALUES (?, ?, ?, ?)",
        [$nama_ruangan, $lantaiId, $kapasitas, $deskripsi]
    );

    $ruanganId = db()->lastInsertId();

    // Handle upload foto sampul
    if (!empty($_FILES['foto_cover']['name'])) {
        $fileName = uploadImageFile($_FILES['foto_cover'], $ruanganId);
        query(
            "INSERT INTO ruangan_foto (ruangan_id, nama_file, tipe) VALUES (?, ?, 'cover')",
            [$ruanganId, $fileName]
        );
    }

    // Handle upload foto detail (multiple)
    if (!empty($_FILES['foto_detail']['name'])) {
        $detailFiles = normalizeFiles($_FILES['foto_detail']);
        foreach ($detailFiles as $file) {
            $fileName = uploadImageFile($file, $ruanganId);
            query(
                "INSERT INTO ruangan_foto (ruangan_id, nama_file, tipe) VALUES (?, ?, 'detail')",
                [$ruanganId, $fileName]
            );
        }
    }

    syncRuanganFasilitas((int)$ruanganId, $fasilitasIds);

    header("Location: ../ruangan.php?success=add");
    exit;
} catch (Exception $e) {
    header("Location: ../ruangan.php?error=" . urlencode($e->getMessage()));
    exit;
}
