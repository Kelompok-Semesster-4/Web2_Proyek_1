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
        throw new Exception("ID gedung tidak valid!");
    }

    if (!checkGedungExists($id)) {
        throw new Exception("Data gedung tidak ditemukan");
    }

    $counts = getGedungRelationCount($id);
    if ($counts['lantai'] > 0 || $counts['ruangan'] > 0) {
        throw new Exception(
            "Gedung tidak dapat dihapus karena masih memiliki " .
                $counts['lantai'] . " lantai dan " . $counts['ruangan'] . " ruangan"
        );
    }

    query("DELETE FROM gedung WHERE id = ?", [$id]);

    header("Location: ../kelola_gedung.php?success=delete");
    exit;
} catch (Exception $e) {
    header("Location: ../kelola_gedung.php?error=" . urlencode($e->getMessage()));
    exit;
}
