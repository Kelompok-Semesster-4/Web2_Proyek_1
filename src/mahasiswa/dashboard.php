<?php
require_once __DIR__ . "/../auth/auth.php";
requireLogin();

require_once __DIR__ . "/../config/koneksi.php";

$pageTitle = "Dashboard";
$activeNav = "home";
require_once __DIR__ . "/../templates/header.php";

$BASE = "/web2/projek/Web2_Proyek_1/src";
?>

<!-- HERO -->
<section class="py-5 text-center text-white" style="background:linear-gradient(rgba(0,0,0,.6),rgba(0,0,0,.6)),
url('<?= $BASE ?>/uploads/banner.jpg') center/cover; min-height:70vh; display:flex; align-items:center;">
   <div class="container">
      <h1 class="display-5 fw-bold">Room Booking System</h1>
      <p class="lead">Selamat datang,
         <?= e($_SESSION['nama'] ?? 'Mahasiswa') ?>
      </p>
      <a href="#ruangan" class="btn btn-success btn-lg mt-3">Lihat Ruangan</a>
   </div>
</section>


<!-- TENTANG -->
<section id="tentang" class="py-5">
   <div class="container text-center">
      <h2 class="mb-4">Tentang Sistem</h2>
      <p class="col-md-8 mx-auto text-muted">
         Sistem ini membantu mahasiswa melakukan peminjaman ruangan secara online.
         Mulai dari melihat ruangan tersedia, mengajukan peminjaman,
         hingga memantau status persetujuan oleh admin fakultas.
      </p>
   </div>
</section>


<!-- FITUR -->
<section id="fitur" class="py-5 bg-light">
   <div class="container">
      <h2 class="text-center mb-5">Fitur Sistem</h2>

      <div class="row g-4 text-center">

         <div class="col-md-4">
            <div class="p-4 border rounded h-100">
               <i class="bi bi-calendar-check fs-1 text-primary"></i>
               <h5 class="mt-3">Cek Ketersediaan</h5>
               <p class="text-muted">Mengetahui ruangan kosong berdasarkan tanggal kegiatan</p>
            </div>
         </div>

         <div class="col-md-4">
            <div class="p-4 border rounded h-100">
               <i class="bi bi-send-check fs-1 text-success"></i>
               <h5 class="mt-3">Pengajuan Online</h5>
               <p class="text-muted">Ajukan peminjaman langsung dari website</p>
            </div>
         </div>

         <div class="col-md-4">
            <div class="p-4 border rounded h-100">
               <i class="bi bi-clock-history fs-1 text-warning"></i>
               <h5 class="mt-3">Riwayat Status</h5>
               <p class="text-muted">Melihat disetujui atau ditolak admin</p>
            </div>
         </div>

      </div>
   </div>
</section>


<!-- RUANGAN -->
<section id="ruangan" class="py-5">
   <div class="container">
      <h2 class="text-center mb-5">Daftar Ruangan</h2>

      <div class="row g-4">
         <?php foreach ($ruangan as $r): ?>
            <div class="col-md-4">

               <div class="card shadow-sm h-100">
                  <img src="<?= $BASE ?>/uploads/ruangan/<?= e($r['gambar']) ?>" class="card-img-top"
                     style="height:200px;object-fit:cover">

                  <div class="card-body">
                     <h5 class="card-title">
                        <?= e($r['nama_ruangan']) ?>
                     </h5>
                     <p class="card-text text-muted">
                        Gedung
                        <?= e($r['gedung']) ?> • Lantai
                        <?= e($r['lantai']) ?><br>
                        Kapasitas
                        <?= e($r['kapasitas']) ?> orang
                     </p>

                     <button class="btn btn-primary w-100" data-bs-toggle="modal"
                        data-bs-target="#modal<?= $r['id_ruangan'] ?>">
                        Detail
                     </button>
                  </div>
               </div>

            </div>

            <!-- MODAL DETAIL -->
            <div class="modal fade" id="modal<?= $r['id_ruangan'] ?>" tabindex="-1">
               <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content">

                     <div class="modal-header">
                        <h5 class="modal-title">
                           <?= e($r['nama_ruangan']) ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                     </div>

                     <div class="modal-body">
                        <div class="row">

                           <div class="col-md-6">
                              <img src="<?= $BASE ?>/uploads/ruangan/<?= e($r['gambar']) ?>" class="img-fluid rounded">
                           </div>

                           <div class="col-md-6">
                              <p><b>Gedung:</b>
                                 <?= e($r['gedung']) ?>
                              </p>
                              <p><b>Lantai:</b>
                                 <?= e($r['lantai']) ?>
                              </p>
                              <p><b>Kapasitas:</b>
                                 <?= e($r['kapasitas']) ?> orang
                              </p>
                              <p><b>Deskripsi:</b><br>
                                 <?= e($r['deskripsi']) ?>
                              </p>
                              <p><b>Fasilitas:</b><br>
                                 <?= e($r['fasilitas']) ?>
                              </p>

                              <a href="<?= $BASE ?>/mahasiswa/peminjaman.php?ruang=<?= $r['id_ruangan'] ?>"
                                 class="btn btn-success w-100 mt-3">
                                 Ajukan Peminjaman
                              </a>
                           </div>

                        </div>
                     </div>

                  </div>
               </div>
            </div>

         <?php endforeach; ?>
      </div>
   </div>
</section>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>