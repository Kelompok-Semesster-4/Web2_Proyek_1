<?php
// src/auth/login_google.php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/koneksi.php'; // untuk memuat .env

session_start();

$client = new Google\Client();
$client->setClientId(env('GOOGLE_CLIENT_ID'));
$client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
$client->addScope("email");
$client->addScope("profile");

// Generate URL login Google dan arahkan user ke sana
$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;
