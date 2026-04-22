<?php
session_start();
require_once __DIR__ . "/../../config/koneksi.php";

// Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

try {
    $id = (int)($_GET['id'] ?? 0);

    if (!$id) {
        throw new Exception("ID tidak valid!");
    }

    // Cek apakah user yang akan dihapus adalah user yang sedang login
    if ($id == $_SESSION['user_id']) {
        throw new Exception("Tidak dapat menghapus akun sendiri!");
    }

    // Hapus user
    query("DELETE FROM users WHERE id = ?", [$id]);

    header("Location: ../kelola_user.php?success=delete");
    exit;
} catch (Exception $e) {
    header("Location: ../kelola_user.php?error=" . urlencode($e->getMessage()));
    exit;
}
