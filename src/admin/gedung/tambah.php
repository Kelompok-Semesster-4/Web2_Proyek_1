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
    $namaGedung = sanitizeGedungName($_POST['nama_gedung'] ?? '');

    validateGedungInput($namaGedung);

    if (checkGedungNameExists($namaGedung)) {
        throw new Exception("Nama gedung sudah terdaftar!");
    }

    query("INSERT INTO gedung (nama_gedung) VALUES (?)", [$namaGedung]);

    header("Location: ../kelola_gedung.php?success=add");
    exit;
} catch (Exception $e) {
    header("Location: ../kelola_gedung.php?error=" . urlencode($e->getMessage()));
    exit;
}
