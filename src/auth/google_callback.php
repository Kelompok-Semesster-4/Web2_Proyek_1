<?php
// src/auth/google_callback.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/koneksi.php';
session_start();

$client = new Google\Client();
$client->setClientId(env('GOOGLE_CLIENT_ID'));
$client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    
    // Pastikan tidak ada error saat mengambil token
    if (!isset($token['error'])) {
        $client->setAccessToken($token['access_token']);

        // Ambil data profil Google
        $google_oauth = new Google\Service\Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $google_id = $google_account_info->id;
        $email = $google_account_info->email;
        $name = $google_account_info->name;

        // Cek apakah user dengan email/google_id ini sudah ada di database kita
        $user = query("SELECT id, nama, role FROM users WHERE email = ? OR google_id = ? LIMIT 1", [$email, $google_id])->fetch();

        if ($user) {
            // Jika sudah ada, langsung login
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['role']    = (string)$user['role'];
            $_SESSION['nama']    = (string)$user['nama'];
            
            // Update google_id jika belum diset (opsional, jika login via email sebelumnya)
            query("UPDATE users SET google_id = ? WHERE id = ?", [$google_id, $user['id']]);

            header("Location: " . ($user['role'] === 'admin' ? "../admin/dashboard.php" : "../mahasiswa/dashboard.php"));
            exit;
        } else {
            // Jika belum terdaftar, buatkan akun otomatis dengan role default 'mahasiswa'
            $role_default = 'mahasiswa';
            // Buat username acak atau ambil dari prefix email
            $username_baru = explode('@', $email)[0] . rand(10, 99); 
            
            query("INSERT INTO users (username, password, nama, role, email, google_id) VALUES (?, ?, ?, ?, ?, ?)", 
                [$username_baru, '', $name, $role_default, $email, $google_id]
            );
            
            $new_id = query("SELECT LAST_INSERT_ID() as id")->fetch()['id'];
            
            $_SESSION['user_id'] = (int)$new_id;
            $_SESSION['role']    = $role_default;
            $_SESSION['nama']    = $name;
            
            header("Location: ../mahasiswa/dashboard.php");
            exit;
        }
    } else {
        // Jika token gagal (misal kode sudah tidak valid)
        header("Location: login.php?err=google_auth_failed");
        exit;
    }
} else {
    // Jika tidak ada code (user membatalkan login)
    header("Location: login.php");
    exit;
}
