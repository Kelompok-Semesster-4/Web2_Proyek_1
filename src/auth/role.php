<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/koneksi.php';

function requireRole(string $mustRole): void {
    global $BASE;
    $role = $_SESSION['role'] ?? '';

    // kalau belum login, lempar ke login
    if ($role === '') {
        header('Location: ' . $BASE . 'auth/login.php');
        exit;
    }

    // kalau role sesuai, lanjut
    if ($role === $mustRole) {
        return;
    }

    // kalau admin nyasar ke halaman mahasiswa → balik ke admin
    if ($role === 'admin') {
        header('Location: ' . $BASE . 'admin/dashboard.php');
        exit;
    }

    // kalau mahasiswa nyasar ke halaman admin → balik ke mahasiswa
    if ($role === 'mahasiswa') {
        header('Location: ' . $BASE . 'mahasiswa/dashboard.php');
        exit;
    }

    // fallback
    header('Location: ' . $BASE . 'auth/logout.php');
    exit;
}