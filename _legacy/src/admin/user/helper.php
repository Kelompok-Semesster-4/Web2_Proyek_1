<?php
// File helper untuk fungsi-fungsi yang digunakan bersama di CRUD user

function validateUserInput($nama, $username, $password, $role, $isEdit = false)
{
    if (empty($nama)) {
        throw new Exception("Nama tidak boleh kosong");
    }
    if (empty($username)) {
        throw new Exception("Username tidak boleh kosong");
    }
    if (!$isEdit && empty($password)) {
        throw new Exception("Password tidak boleh kosong");
    }
    if (empty($role)) {
        throw new Exception("Role tidak boleh kosong");
    }
    if (!in_array($role, ['admin', 'mahasiswa'])) {
        throw new Exception("Role tidak valid");
    }
}

function checkUsernameExists($username, $excludeId = null)
{
    if ($excludeId) {
        $stmt = query("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $excludeId]);
    } else {
        $stmt = query("SELECT id FROM users WHERE username = ?", [$username]);
    }
    
    return $stmt->fetch() !== false;
}

function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

function sanitizeString($value)
{
    return trim($value);
}

function sanitizeProdi($prodi)
{
    $prodi = trim($prodi);
    return empty($prodi) ? null : $prodi;
}
