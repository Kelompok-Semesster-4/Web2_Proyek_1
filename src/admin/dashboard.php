<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$activeAdmin = 'dashboard';
$pageTitle = "Dashboard";

$ruangan = query(
    "SELECT SUM(terpakai) AS ruangan_terpakai, SUM(1 - terpakai) AS ruangan_tersedia 
    FROM (
        SELECT r.id, MAX(CASE
            WHEN p.status_id = 2 
                AND p.tanggal = CURDATE() 
                AND CURTIME() BETWEEN p.jam_mulai AND p.jam_selesai 
            THEN 1
            ELSE 0
            END
        ) AS terpakai
        FROM ruangan AS r LEFT JOIN peminjaman as p on r.id=p.ruangan_id
        GROUP BY r.id
    ) AS status_ruangan"
)->fetchAll()[0];

$users = query(
    "SELECT 
        COUNT(*) AS total_pengguna, 
        COUNT(IF(role = 'admin', 1, NULL)) AS total_admin, 
        COUNT(IF(role = 'mahasiswa', 1, NULL)) AS total_mahasiswa 
    FROM users"
)->fetchAll()[0];

$peminjaman = query(
    "SELECT 
        COUNT(IF(status_id = 1, 1, NULL)) AS pending_hari_ini, 
        COUNT(IF(status_id = 2, 1, NULL)) AS disetujui_hari_ini, 
        COUNT(IF(status_id = 3, 1, NULL)) AS ditolak_hari_ini
    FROM peminjaman
    WHERE tanggal = CURDATE()"
)->fetchAll()[0];

require_once __DIR__ . "/../templates/admin_head.php";
require_once __DIR__ . "/../templates/admin_sidebar.php";
?>

<div class="admin-container" style="max-width: 100%">
    <div class="kelola-header mb-4">
        <h1>Dashboard</h1>
    </div>
</div>