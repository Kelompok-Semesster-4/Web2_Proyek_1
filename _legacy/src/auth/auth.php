<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void
{
    global $BASE;

    if (!empty($_SESSION['user_id'])) {
        // Cek apakah user masih ada di database
        $userId = (int) $_SESSION['user_id'];
        $user = query("SELECT id FROM users WHERE id = ? LIMIT 1", [$userId])->fetch();

        if ($user) {
            return; // User valid, lanjutkan
        }

        // User tidak ditemukan, hapus session
        session_unset();
        session_destroy();
    }

    $current = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: ' . $BASE . 'auth/login.php?redirect=' . urlencode($current));
    exit;
}
?>