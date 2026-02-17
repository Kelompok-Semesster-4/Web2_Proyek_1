# Panduan Implementasi Fitur Kelola Ruangan

## ✅ File yang Telah Dibuat

### File Baru:

1. **`src/admin/proses_ruangan.php`** - Backend untuk semua operasi CRUD ruangan
2. **`FITUR_KELOLA_RUANGAN.md`** - Dokumentasi lengkap fitur

### File yang Diperbarui:

1. **`src/admin/ruangan.php`** - Tampilan halaman kelola ruangan (diganti total dengan versi lengkap)
2. **`src/templates/admin_sidebar.php`** - Diperbaiki struktur HTML (menambah wrapper div)

## 📋 Fitur yang Diimplementasikan

### 1. **Menampilkan Daftar Ruangan** ✓

- Tabel dengan nama ruangan, gedung, kapasitas, dan foto thumbnail
- Menampilkan message ketika belum ada data

### 2. **Tambah Ruangan** ✓

- Modal form untuk input data baru
- Field: Nama, Gedung, Kapasitas, Deskripsi, Foto
- Upload foto dengan validasi tipe dan ukuran

### 3. **Edit Ruangan** ✓

- Modal form untuk mengubah data
- Dapat upload foto baru (foto lama otomatis terhapus)
- Pre-fill form dengan data yang ada

### 4. **Hapus Ruangan** ✓

- Konfirmasi sebelum hapus
- Foto otomatis terhapus dari folder uploads
- Data langsung dihapus dari database

### 5. **Upload Foto** ✓

- Validasi format file: JPG, PNG, GIF
- Validasi ukuran: max 2MB
- Auto-generate nama file: `ruangan_[ID]_[TIMESTAMP].[ext]`
- Folder: `/uploads/ruangan/`

## 🔧 Setup & Konfigurasi

### Prasyarat

- PHP 7.4+ dengan PDO MySQL
- Database: `peminjaman_ruangan` dengan tabel `ruangan`
- Folder `/uploads/ruangan/` dengan write permission

### Langkah Instalasi

1. **Upload file ke server:**

   ```
   src/admin/ruangan.php (REPLACE yang lama)
   src/admin/proses_ruangan.php (NEW)
   src/templates/admin_sidebar.php (UPDATE)
   ```

2. **Pastikan folder uploads dapat ditulis:**

   ```bash
   chmod -R 755 uploads/ruangan/
   ```

3. **Gunakan koneksi database yang sudah ada** (via `src/config/koneksi.php`)

## 🚀 Cara Menggunakan

### Akses Halaman

- URL: `http://yoursite/src/admin/ruangan.php`
- Hanya admin yang bisa akses (ada session check)

### Operasi CRUD

#### Tambah Ruangan

```
1. Klik tombol "Tambah Ruangan"
2. Isi form:
   - Nama Ruangan: (required)
   - Gedung: Gedung A, Gedung B, dll
   - Kapasitas: 30, 40, 50, dll (dalam jumlah orang)
   - Deskripsi: (optional) keterangan tambahan
   - Foto: pilih file gambar (optional)
3. Klik "Simpan"
```

#### Edit Ruangan

```
1. Klik tombol "Edit" pada baris ruangan
2. Form otomatis terisi dengan data sebelumnya
3. Ubah field yang diperlukan
4. Upload foto baru jika ingin ganti (optional)
5. Klik "Update"
```

#### Hapus Ruangan

```
1. Klik tombol "Hapus" pada baris ruangan
2. Konfirmasi penghapusan pada dialog
3. Selesai (foto otomatis terhapus dari folder)
```

## 📊 Struktur Database

Tabel yang sudah ada: `ruangan`

```sql
CREATE TABLE ruangan (
  id INT NOT NULL AUTO_INCREMENT,
  nama_ruangan VARCHAR(100) NOT NULL,
  gedung VARCHAR(100) DEFAULT NULL,
  kapasitas INT DEFAULT NULL,
  deskripsi TEXT,
  foto VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Tidak perlu migrasi database - struktur sudah sesuai!

## 🔐 Keamanan

Fitur keamanan yang implementasikan:

- ✓ Session validation (hanya admin)
- ✓ Input sanitization (htmlspecialchars)
- ✓ SQL injection prevention (PDO prepared statements)
- ✓ File type validation (MIME type check)
- ✓ File size validation (max 2MB)
- ✓ Delete confirmation (prevent accidental delete)
- ✓ Automatic file cleanup (foto lama dihapus)

## 💾 Storage Direktori

```
Web2_Proyek_1/
├── src/
│   ├── admin/
│   │   ├── ruangan.php (UPDATED)
│   │   ├── proses_ruangan.php (NEW)
│   │   └── ...
│   └── uploads/
│       └── ruangan/
│           ├── ruangan_1_1708xxx.jpg
│           ├── ruangan_2_1708xxx.png
│           └── ...
```

## 🧪 Testing

Untuk test fitur:

1. **Test Tambah:**
   - Tambah ruangan dengan foto
   - Cek foto ada di `/uploads/ruangan/`
   - Cek data ada di database

2. **Test Edit:**
   - Edit ruangan existing
   - Upload foto baru
   - Cek foto lama terhapus, foto baru ada

3. **Test Hapus:**
   - Hapus ruangan dengan foto
   - Cek data hilang dari database
   - Cek foto hilang dari folder

## ⚠️ Troubleshooting

### Error "Gagal mengupload foto"

- Cek permission folder `/uploads/ruangan/` (harus writable)
- Cek ukuran file (max 2MB)
- Cek tipe file (JPG, PNG, GIF)

### Error "Ruangan tidak ditemukan"

- Jika data sudah dihapus sebelumnya
- Refresh halaman

### Foto tidak muncul di tabel

- Periksa apakah foto sudah ter-upload di folder
- Periksa nama file di database

### Session timeout

- Login ulang sebagai admin
- Pastikan session masih aktif

## 📝 Catatan Penting

1. **Foto bersifat optional** - Ruangan bisa dibuat tanpa foto
2. **Foto lama otomatis dihapus** - Saat edit dengan upload foto baru
3. **Direktori auto-create** - Folder `/uploads/ruangan/` otomatis dibuat jika tidak ada
4. **Nama unik foto** - Sistem auto-generate nama file menggunakan ID + timestamp

## 🔄 Pengembangan Lebih Lanjut

Fitur tambahan yang bisa dikembang:

- [ ] Multi-foto per ruangan
- [ ] Gallery preview
- [ ] Fasilitas per ruangan
- [ ] Jadwal ketersediaan
- [ ] Rating/review ruangan
- [ ] Export ke Excel/PDF
- [ ] Bulk upload ruangan

## 📞 Support

Jika ada error atau pertanyaan, periksa:

1. File logs PHP (jika tersedia)
2. Browser console (F12 → Console tab)
3. Database connection status
4. File permission di `/uploads/` folder
