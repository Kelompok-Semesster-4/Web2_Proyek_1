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
    $id = $_GET['id'] ?? 0;

    if (empty($id)) {
        throw new Exception("ID ruangan tidak valid");
    }

    // Get ruangan
    $stmt = query("SELECT * FROM ruangan WHERE id = ?", [$id]);
    $ruangan = $stmt->fetch();

    if (!$ruangan) {
        throw new Exception("Ruangan tidak ditemukan");
    }

    // Hapus semua foto dari tabel ruangan_foto
    $fotoRows = query(
        "SELECT nama_file FROM ruangan_foto WHERE ruangan_id = ?",
        [$id]
    )->fetchAll();
    foreach ($fotoRows as $row) {
        deletePhotoFile($row['nama_file']);
    }

    // Hapus foto lama di tabel ruangan (jika ada)
    if (!empty($ruangan['foto'])) {
        deletePhotoFile($ruangan['foto']);
    }

    // Delete dari database
    query("DELETE FROM ruangan WHERE id = ?", [$id]);

    header("Location: ../ruangan.php?success=delete");
    exit;
} catch (Exception $e) {
    header("Location: ../ruangan.php?error=" . urlencode($e->getMessage()));
    exit;
}
