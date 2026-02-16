<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/role.php';
requireLogin();
requireRole('mahasiswa');

$pageTitle = "Peminjaman";
$activeNav = "peminjaman";
require_once __DIR__ . "/../templates/header.php";
?>

<!-- KOSONG dulu, nanti isi peminjaman di sini -->

<?php require_once __DIR__ . "/../templates/footer.php"; ?>