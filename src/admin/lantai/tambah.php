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
    // Ambil dan sanitize input
    $gedung_id = sanitizeGedungId($_POST['gedung_id'] ?? 0);
    $nomor = sanitizeNumber($_POST['nomor'] ?? 0);

    // Validasi input
    validateLantaiInput($gedung_id, $nomor);

    // Cek gedung ada
    if (!checkGedungExists($gedung_id)) {
        throw new Exception("Gedung tidak ditemukan!");
    }

    // Cek lantai sudah ada
    if (checkLantaiExists($gedung_id, $nomor)) {
        throw new Exception("Lantai dengan nomor ini sudah ada di gedung yang sama!");
    }

    // Insert lantai baru
    $sql = "INSERT INTO lantai (gedung_id, nomor) VALUES (?, ?)";
    query($sql, [$gedung_id, $nomor]);

    header("Location: ../kelola_lantai.php?success=add");
    exit;
} catch (Exception $e) {
    header("Location: ../kelola_lantai.php?error=" . urlencode($e->getMessage()));
    exit;
}
