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
    $id = (int)($_POST['id'] ?? 0);
    $nama = sanitizeString($_POST['nama'] ?? '');
    $username = sanitizeString($_POST['username'] ?? '');
    $role = $_POST['role'] ?? '';
    $prodi = sanitizeProdi($_POST['prodi'] ?? '');
    $password = sanitizeString($_POST['password'] ?? '');

    if (!$id) {
        throw new Exception("ID tidak valid");
    }

    // Validasi input (password optional di edit)
    validateUserInput($nama, $username, $password, $role, true);

    // Cek username sudah digunakan user lain
    if (checkUsernameExists($username, $id)) {
        throw new Exception("Username sudah digunakan oleh user lain!");
    }

    // Update user
    if (!empty($password)) {
        // Jika password diisi, update dengan password baru
        $hashedPassword = hashPassword($password);
        $sql = "UPDATE users SET nama = ?, username = ?, password = ?, role = ?, prodi = ? WHERE id = ?";
        query($sql, [$nama, $username, $hashedPassword, $role, $prodi, $id]);
    } else {
        // Jika password kosong, tidak update password
        $sql = "UPDATE users SET nama = ?, username = ?, role = ?, prodi = ? WHERE id = ?";
        query($sql, [$nama, $username, $role, $prodi, $id]);
    }

    header("Location: ../kelola_user.php?success=edit");
    exit;
} catch (Exception $e) {
    header("Location: ../kelola_user.php?error=" . urlencode($e->getMessage()));
    exit;
}
