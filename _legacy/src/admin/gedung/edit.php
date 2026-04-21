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
    $namaGedung = sanitizeGedungName($_POST['nama_gedung'] ?? '');

    if (!$id) {
        throw new Exception("ID gedung tidak valid");
    }

    if (!checkGedungExists($id)) {
        throw new Exception("Data gedung tidak ditemukan");
    }

    validateGedungInput($namaGedung);

    if (checkGedungNameExists($namaGedung, $id)) {
        throw new Exception("Nama gedung sudah digunakan oleh data lain!");
    }

    query("UPDATE gedung SET nama_gedung = ? WHERE id = ?", [$namaGedung, $id]);

    header("Location: ../kelola_gedung.php?success=edit");
    exit;
} catch (Exception $e) {
    header("Location: ../kelola_gedung.php?error=" . urlencode($e->getMessage()));
    exit;
}
