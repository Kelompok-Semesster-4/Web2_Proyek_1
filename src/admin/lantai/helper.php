<?php
// File helper untuk fungsi-fungsi yang digunakan bersama di CRUD lantai

function validateLantaiInput($gedung_id, $nomor)
{
    if (empty($gedung_id)) {
        throw new Exception("Gedung harus dipilih");
    }

    if (empty($nomor)) {
        throw new Exception("Nomor lantai tidak boleh kosong");
    }

    $nomor = (int)$nomor;
    if ($nomor < 1) {
        throw new Exception("Nomor lantai harus lebih dari 0");
    }
}

function checkLantaiExists($gedung_id, $nomor, $excludeId = null)
{
    if ($excludeId) {
        $stmt = query(
            "SELECT id FROM lantai WHERE gedung_id = ? AND nomor = ? AND id != ?",
            [$gedung_id, $nomor, $excludeId]
        );
    } else {
        $stmt = query(
            "SELECT id FROM lantai WHERE gedung_id = ? AND nomor = ?",
            [$gedung_id, $nomor]
        );
    }

    return $stmt->fetch() !== false;
}

function checkGedungExists($gedung_id)
{
    $stmt = query("SELECT id FROM gedung WHERE id = ?", [$gedung_id]);
    return $stmt->fetch() !== false;
}

function sanitizeNumber($value)
{
    return (int)trim($value);
}

function sanitizeGedungId($value)
{
    return (int)trim($value);
}

function checkLantaiReferenced($lantai_id)
{
    $stmt = query("SELECT id FROM ruangan WHERE lantai_id = ? LIMIT 1", [$lantai_id]);
    return $stmt->fetch() !== false;
}
