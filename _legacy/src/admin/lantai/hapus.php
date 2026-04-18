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
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        throw new Exception("ID lantai tidak valid!");
    }

    // Cek apakah lantai masih digunakan di ruangan
    if (checkLantaiReferenced($id)) {
        throw new Exception("Lantai tidak dapat dihapus karena masih digunakan oleh ruangan!");
    }

    // Hapus lantai
    query("DELETE FROM lantai WHERE id = ?", [$id]);

    header("Location: ../kelola_lantai.php?success=delete");
    exit;
} catch (Exception $e) {
    header("Location: ../kelola_lantai.php?error=" . urlencode($e->getMessage()));
    exit;
}
