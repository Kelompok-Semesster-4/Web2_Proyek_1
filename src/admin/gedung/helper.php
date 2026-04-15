<?php
// File helper untuk fungsi-fungsi yang digunakan bersama di CRUD gedung

function sanitizeGedungName($value)
{
    return trim($value);
}

function validateGedungInput($namaGedung)
{
    if ($namaGedung === '') {
        throw new Exception("Nama gedung tidak boleh kosong");
    }

    if (strlen($namaGedung) > 100) {
        throw new Exception("Nama gedung maksimal 100 karakter");
    }
}

function checkGedungNameExists($namaGedung, $excludeId = null)
{
    if ($excludeId) {
        $stmt = query(
            "SELECT id FROM gedung WHERE nama_gedung = ? AND id != ?",
            [$namaGedung, $excludeId]
        );
    } else {
        $stmt = query("SELECT id FROM gedung WHERE nama_gedung = ?", [$namaGedung]);
    }

    return $stmt->fetch() !== false;
}

function checkGedungExists($id)
{
    $stmt = query("SELECT id FROM gedung WHERE id = ?", [$id]);
    return $stmt->fetch() !== false;
}

function getGedungRelationCount($id)
{
    $lantaiCountStmt = query("SELECT COUNT(*) AS total FROM lantai WHERE gedung_id = ?", [$id]);
    $lantaiCount = (int)$lantaiCountStmt->fetch()['total'];

    $ruanganCountStmt = query(
        "SELECT COUNT(*) AS total
         FROM ruangan r
         INNER JOIN lantai l ON l.id = r.lantai_id
         WHERE l.gedung_id = ?",
        [$id]
    );
    $ruanganCount = (int)$ruanganCountStmt->fetch()['total'];

    return [
        'lantai' => $lantaiCount,
        'ruangan' => $ruanganCount,
    ];
}
