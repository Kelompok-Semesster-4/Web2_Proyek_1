<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/role.php';
require_once __DIR__ . '/../config/koneksi.php';

requireLogin();
requireRole('mahasiswa');

$pageTitle = "Ruangan";
$activeNav = "ruangan";

/* HERO IMAGE */
$heroImg = query("
      SELECT nama_file
    FROM ruangan_foto
    WHERE nama_file IS NOT NULL AND nama_file != ''
    ORDER BY id DESC
    LIMIT 1
")->fetchColumn();

/* ambil semua ruangan */
$ruangans = query(
    "SELECT r.*, g.nama_gedung AS gedung, l.nomor AS Lantai,
         (
             SELECT rf.nama_file
             FROM ruangan_foto rf
             WHERE rf.ruangan_id = r.id
             ORDER BY rf.id DESC
             LIMIT 1
         ) AS foto_utama
    FROM ruangan r
    LEFT JOIN lantai l ON l.id = r.lantai_id
    LEFT JOIN gedung g ON g.id = l.gedung_id
    ORDER BY r.nama_ruangan"
)->fetchAll();

require_once __DIR__ . "/../templates/header.php";
?>

<!-- HERO -->
<section class="hero-page">

   <img src="<?= !empty($heroImg) ? ($BASE . 'uploads/ruangan/' . e($heroImg)) : ($BASE . 'assets/icons/logo_big.svg') ?>"
      class="hero-bg" alt="Hero Ruangan">

   <div class="hero-overlay"></div>

   <div class="hero-page-content">
      <h1>Ruangan Kami</h1>
      <div class="breadcrumb">
         <a href="<?= $BASE ?>/mahasiswa/dashboard.php">Home</a>
         <span class="sep">›</span>
         <span class="current">Ruangan</span>
      </div>

   </div>

</section>


<div class="wrap">
   <section class="room-section">
      <div class="container">

         <?php if (!$ruangans): ?>

            <div class="text-center text-white mt-5">
               <h5>Belum ada data ruangan</h5>
            </div>

         <?php else: ?>

            <div class="room-grid">

               <?php foreach ($ruangans as $r): ?>
                  <?php $fotoRuangan = $r['foto_utama'] ?? ''; ?>

                  <div class="room-item">
                     <div class="room-card">

                        <div class="room-img">
                           <img src="<?= !empty($fotoRuangan) ? ($BASE . 'uploads/ruangan/' . e($fotoRuangan)) : ($BASE . 'assets/icons/logo_big.svg') ?>"
                              alt="<?= e($r['nama_ruangan']) ?>">
                        </div>

                        <div class="room-body">

                           <div class="room-title"><?= e($r['nama_ruangan']) ?></div>

                           <div class="room-meta">
                              Lokasi : <?= e($r['gedung'] ?: '-') ?><br>
                              Lantai : <?= e($r['Lantai'] ?? ($r['lantai'] ?? '-')) ?><br>
                              Kapasitas : <?= e($r['kapasitas'] ?? 0) ?> orang
                           </div>

                           <a href="<?= $BASE ?>/mahasiswa/detail_ruangan.php?id=<?= $r['id'] ?>" class="room-btn">
                              View Details
                           </a>

                        </div>

                     </div>
                  </div>

               <?php endforeach; ?>

            </div>
         <?php endif; ?>

      </div>
   </section>
</div>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>
