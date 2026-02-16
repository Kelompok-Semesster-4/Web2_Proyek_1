<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/role.php';
requireLogin();
requireRole('mahasiswa');

$pageTitle = "Status Ruangan";
$activeNav = "status";
require_once __DIR__ . "/../templates/header.php";
?>

<!-- KOSONG dulu, nanti isi status ruangan di sini -->

<?php require_once __DIR__ . "/../templates/footer.php"; ?>