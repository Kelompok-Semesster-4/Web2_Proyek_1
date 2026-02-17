# Fitur Kelola Ruangan (Admin)

## Deskripsi

Halaman admin untuk mengelola data ruangan dengan fitur lengkap CRUD (Create, Read, Update, Delete) dan upload foto.

## Fitur Utama

### 1. **Tambah Ruangan**

- Admin dapat menambah ruangan baru
- Form input untuk:
  - Nama Ruangan (required)
  - Gedung
  - Kapasitas (dalam jumlah orang)
  - Deskripsi
  - Foto Ruangan (JPG, PNG, GIF, max 2MB)

### 2. **Menampilkan Daftar Ruangan**

- Tabel dengan informasi ruangan:
  - Nomor urut
  - Nama ruangan
  - Gedung
  - Kapasitas
  - Thumbnail foto
  - Tombol aksi (Edit & Hapus)

### 3. **Edit Ruangan**

- Ubah informasi ruangan yang sudah ada
- Dapat mengganti foto dengan upload foto baru
- Foto lama otomatis terhapus saat upload foto baru

### 4. **Hapus Ruangan**

- Hapus data ruangan dari database
- Foto ruangan otomatis terhapus dari folder uploads
- Konfirmasi sebelum menghapus untuk keamanan

### 5. **Upload Foto**

- Foto disimpan di folder: `/uploads/ruangan/`
- Format: `ruangan_[ID]_[TIMESTAMP].[ext]`
- Validasi tipe file (JPG, PNG, GIF)
- Validasi ukuran file (max 2MB)

## File yang Dibuat

### 1. `src/admin/ruangan.php`

File utama halaman admin kelola ruangan dengan:

- Daftar ruangan dalam bentuk tabel
- Modal form tambah ruangan
- Modal form edit ruangan
- Menampilkan alert sukses/error
- Integrasi Bootstrap 5 untuk styling

### 2. `src/admin/proses_ruangan.php`

File backend untuk semua operasi CRUD:

- `action=add` - Tambah ruangan baru
- `action=edit` - Update ruangan
- `action=delete` - Hapus ruangan

**Validasi yang dilakukan:**

- Nama ruangan harus diisi
- Validasi tipe file foto (JPEG, PNG, GIF)
- Validasi ukuran file (max 2MB)
- Auto-delete foto lama saat edit dengan foto baru

### 3. `src/templates/admin_sidebar.php` (diperbarui)

- Menambahkan wrapper div untuk struktur HTML yang tepat

## Struktur Database

```sql
CREATE TABLE ruangan (
  id INT NOT NULL AUTO_INCREMENT,
  nama_ruangan VARCHAR(100) NOT NULL,
  gedung VARCHAR(100) DEFAULT NULL,
  kapasitas INT DEFAULT NULL,
  deskripsi TEXT,
  foto VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Cara Menggunakan

### Menambah Ruangan

1. Klik tombol "Tambah Ruangan" di halaman admin
2. Isi form dengan data ruangan
3. Pilih foto ruangan (opsional)
4. Klik "Simpan"

### Mengedit Ruangan

1. Klik tombol "Edit" pada baris ruangan yang ingin diubah
2. Ubah data yang diinginkan
3. Upload foto baru jika diperlukan (opsional)
4. Klik "Update"

### Menghapus Ruangan

1. Klik tombol "Hapus" pada baris ruangan yang ingin dihapus
2. Konfirmasi penghapusan pada dialog
3. Ruangan dan foto akan dihapus otomatis

## Keamanan

- **Session Check**: Hanya admin yang bisa akses halaman ini
- **Input Sanitization**: Semua input di-escape menggunakan `htmlspecialchars()`
- **SQL Injection Prevention**: Menggunakan prepared statements (PDO)
- **File Upload Validation**: Validasi MIME type, ukuran, dan ekstensi file
- **CSRF Protection**: Menggunakan form POST/GET dengan konfirmasi delete

## Error Handling

Semua error akan ditampilkan dengan pesan yang jelas:

- Upload gagal
- Tipe file tidak valid
- Ukuran file terlalu besar
- Data tidak lengkap
- ID ruangan tidak ditemukan

## Browser Compatibility

Menggunakan:

- Bootstrap 5.3.3
- JavaScript vanilla (tanpa dependencies eksternal)
- Kompatibel dengan semua browser modern

## Placeholder untuk Pengembangan Lebih Lanjut

Fitur yang bisa ditambahkan di masa depan:

- Multi-upload foto per ruangan
- Ranking/rating ruangan
- Manajemen fasilitas per ruangan
- Jadwal ketersediaan ruangan
- Export data ke Excel
- Print laporan ruangan
