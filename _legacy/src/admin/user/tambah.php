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
    $nama = sanitizeString($_POST['nama'] ?? '');
    $username = sanitizeString($_POST['username'] ?? '');
    $password = sanitizeString($_POST['password'] ?? '');
    $role = $_POST['role'] ?? '';
    $prodi = sanitizeProdi($_POST['prodi'] ?? '');

    // Validasi input
    validateUserInput($nama, $username, $password, $role);

    // Cek username sudah ada
    if (checkUsernameExists($username)) {
        throw new Exception("Username sudah digunakan!");
    }

    // Hash password
    $hashedPassword = hashPassword($password);

    // Insert user baru
    $sql = "INSERT INTO users (nama, username, password, role, prodi) VALUES (?, ?, ?, ?, ?)";
    query($sql, [$nama, $username, $hashedPassword, $role, $prodi]);

    header("Location: ../kelola_user.php?success=add");
    exit;
} catch (Exception $e) {
    header("Location: ../kelola_user.php?error=" . urlencode($e->getMessage()));
    exit;
}
