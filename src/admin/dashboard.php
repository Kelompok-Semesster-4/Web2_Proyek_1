<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/role.php';
require_once __DIR__ . '/../config/koneksi.php';

requireLogin();
requireRole('admin');
autoMarkSelesai();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$pageTitle = 'Dashboard';
$activeAdmin = 'dashboard';
$adminId = (int) ($_SESSION['user_id'] ?? 0);

$success = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['peminjaman_id'] ?? 0);
    $catatan = trim((string) ($_POST['catatan_admin'] ?? ''));

    if ($id <= 0) {
        $_SESSION['flash_error'] = 'ID peminjaman tidak valid.';
        header('Location: dashboard.php');
        exit;
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $target = query("SELECT id, ruangan_id, tanggal, jam_mulai, jam_selesai, status_id FROM peminjaman WHERE id = ? FOR UPDATE", [$id])->fetch();

        if (!$target) {
            throw new RuntimeException('Data peminjaman tidak ditemukan.');
        } elseif ((int) $target['status_id'] !== 1) {
            throw new RuntimeException('Pengajuan ini sudah diproses.');
        } elseif ($action === 'approve') {
            query("UPDATE peminjaman SET status_id = 2, catatan_admin = ? WHERE id = ? AND status_id = 1", [$catatan, $id]);
            $noteApprove = $catatan !== '' ? $catatan : 'Disetujui dari dashboard admin';
            query("INSERT INTO log_status (peminjaman_id, status_id, diubah_oleh, catatan) VALUES (?, ?, ?, ?)", [$id, 2, $adminId, $noteApprove]);

            $conflictIds = query(
                "SELECT id FROM peminjaman
                 WHERE status_id = 1 AND id <> ? AND ruangan_id = ? AND tanggal = ?
                   AND NOT (? >= jam_selesai OR ? <= jam_mulai)",
                [$id, $target['ruangan_id'], $target['tanggal'], $target['jam_mulai'], $target['jam_selesai']]
            )->fetchAll(PDO::FETCH_COLUMN);

            query(
                "UPDATE peminjaman
                 SET status_id = 3,
                     catatan_admin = IFNULL(NULLIF(catatan_admin, ''), 'Auto-ditolak: bentrok jadwal')
                 WHERE status_id = 1 AND id <> ? AND ruangan_id = ? AND tanggal = ?
                   AND NOT (? >= jam_selesai OR ? <= jam_mulai)",
                [$id, $target['ruangan_id'], $target['tanggal'], $target['jam_mulai'], $target['jam_selesai']]
            );

            foreach ($conflictIds as $conflictId) {
                query("INSERT INTO log_status (peminjaman_id, status_id, diubah_oleh, catatan) VALUES (?, ?, ?, ?)", [(int) $conflictId, 3, $adminId, 'Auto-ditolak karena bentrok jadwal']);
            }

            $_SESSION['flash_success'] = 'Pengajuan disetujui. Pengajuan bentrok otomatis ditolak.';
            $pdo->commit();
            header('Location: dashboard.php');
            exit;
        } elseif ($action === 'reject') {
            query("UPDATE peminjaman SET status_id = 3, catatan_admin = ? WHERE id = ? AND status_id = 1", [$catatan, $id]);
            $noteReject = $catatan !== '' ? $catatan : 'Ditolak dari dashboard admin';
            query("INSERT INTO log_status (peminjaman_id, status_id, diubah_oleh, catatan) VALUES (?, ?, ?, ?)", [$id, 3, $adminId, $noteReject]);
            $_SESSION['flash_success'] = 'Pengajuan berhasil ditolak.';
            $pdo->commit();
            header('Location: dashboard.php');
            exit;
        } else {
            throw new RuntimeException('Aksi tidak dikenal.');
        }
    } catch (Throwable $throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $_SESSION['flash_error'] = $throwable->getMessage();
        header('Location: dashboard.php');
        exit;
    }
}

$ruanganStatus = query(
    "SELECT SUM(terpakai) AS ruangan_terpakai, SUM(1 - terpakai) AS ruangan_tersedia
     FROM (
       SELECT r.id, MAX(CASE WHEN p.status_id = 2 AND p.tanggal = CURDATE() AND CURTIME() BETWEEN p.jam_mulai AND p.jam_selesai THEN 1 ELSE 0 END) AS terpakai
       FROM ruangan r LEFT JOIN peminjaman p ON p.ruangan_id = r.id GROUP BY r.id
     ) t"
)->fetch();

$todaySummary = query("SELECT COUNT(*) AS booking_hari_ini, SUM(status_id = 1) AS pending_hari_ini, SUM(status_id = 2) AS disetujui_hari_ini, SUM(status_id = 3) AS ditolak_hari_ini FROM peminjaman WHERE tanggal = CURDATE()")->fetch();
$pendingTotal = (int) (query("SELECT COUNT(*) AS total FROM peminjaman WHERE status_id = 1")->fetch()['total'] ?? 0);

$jadwalHariIni = query(
    "SELECT p.jam_mulai, p.jam_selesai, p.nama_kegiatan, sp.nama_status, u.nama AS nama_peminjam, r.nama_ruangan, g.nama_gedung AS gedung
     FROM peminjaman p
     JOIN status_peminjaman sp ON sp.id = p.status_id
     JOIN users u ON u.id = p.user_id
     JOIN ruangan r ON r.id = p.ruangan_id
     LEFT JOIN lantai l ON l.id = r.lantai_id
     LEFT JOIN gedung g ON g.id = l.gedung_id
     WHERE p.tanggal = CURDATE()
     ORDER BY p.jam_mulai ASC, p.id ASC"
)->fetchAll();

$pendingList = query(
    "SELECT p.id, p.tanggal, p.jam_mulai, p.jam_selesai, p.nama_kegiatan, u.nama AS nama_user, u.prodi, r.nama_ruangan, g.nama_gedung AS gedung
     FROM peminjaman p
     JOIN users u ON u.id = p.user_id
     JOIN ruangan r ON r.id = p.ruangan_id
     LEFT JOIN lantai l ON l.id = r.lantai_id
     LEFT JOIN gedung g ON g.id = l.gedung_id
    WHERE p.status_id = 1
         ORDER BY
             CASE
                 WHEN p.tanggal = CURDATE() THEN 0
                 WHEN p.tanggal > CURDATE() THEN 1
                 ELSE 2
             END ASC,
             CASE
                 WHEN p.tanggal = CURDATE() THEN ABS(TIMESTAMPDIFF(MINUTE, CURTIME(), p.jam_mulai))
                 ELSE 0
             END ASC,
             p.tanggal ASC,
             p.jam_mulai ASC,
             p.id ASC
         LIMIT 5"
)->fetchAll();

$ruanganTerpakai = (int) ($ruanganStatus['ruangan_terpakai'] ?? 0);
$ruanganTersedia = (int) ($ruanganStatus['ruangan_tersedia'] ?? 0);
$bookingHariIni = (int) ($todaySummary['booking_hari_ini'] ?? 0);
$pendingHariIni = (int) ($todaySummary['pending_hari_ini'] ?? 0);
$disetujuiHariIni = (int) ($todaySummary['disetujui_hari_ini'] ?? 0);
$ditolakHariIni = (int) ($todaySummary['ditolak_hari_ini'] ?? 0);

require_once __DIR__ . '/../templates/admin_head.php';
require_once __DIR__ . '/../templates/admin_sidebar.php';
?>

<style>
    .top-equal-card {
        height: 312px;
        display: flex;
        flex-direction: column;
    }

    .room-status-body {
        flex: 1 1 auto;
        min-height: 0;
    }

    .pending-equal-card {
        overflow: hidden;
    }

    .pending-equal-card .table-responsive {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
    }

    .dashboard-scroll-260 {
        max-height: 260px;
        overflow-y: auto;
    }

    .dashboard-scroll-220 {
        max-height: 220px;
        overflow-y: auto;
    }

    .dashboard-scroll-thin {
        scrollbar-width: thin;
        scrollbar-color: rgba(15, 23, 42, 0.35) transparent;
    }

    .dashboard-scroll-thin::-webkit-scrollbar {
        width: 6px;
    }

    .dashboard-scroll-thin::-webkit-scrollbar-track {
        background: transparent;
    }

    .dashboard-scroll-thin::-webkit-scrollbar-thumb {
        background: rgba(15, 23, 42, 0.35);
        border-radius: 999px;
    }

    .dashboard-scroll-thin::-webkit-scrollbar-thumb:hover {
        background: rgba(15, 23, 42, 0.5);
    }

    #pendingCompactTable thead th,
    #todayScheduleCompactTable thead th {
        position: sticky;
        top: 0;
        background: #fff;
        z-index: 1;
    }

    #pendingCompactTable th,
    #pendingCompactTable td,
    #todayScheduleCompactTable th,
    #todayScheduleCompactTable td {
        padding: 0.45rem 0.5rem;
        vertical-align: middle;
    }

    #pendingCompactTable thead th,
    #todayScheduleCompactTable thead th {
        font-size: 0.82rem;
        font-weight: 600;
    }

    #pendingCompactTable td,
    #todayScheduleCompactTable td {
        font-size: 0.88rem;
        line-height: 1.3;
    }

    #pendingCompactTable td small,
    #todayScheduleCompactTable td small {
        font-size: 0.76rem;
    }

    .pending-action-form {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.4rem;
        align-items: center;
        justify-content: flex-start;
    }

    .pending-action-form .form-control,
    .pending-action-form .form-control.form-control-sm {
        min-width: 108px;
        height: 34px !important;
        font-size: 0.82rem;
        padding: 0.25rem 0.5rem;
        margin: 0 !important;
        line-height: 1.2;
    }

    .pending-icon-btn {
        width: 34px;
        height: 34px;
        min-height: 34px;
        padding: 0;
        margin: 0 !important;
        flex: 0 0 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .pending-icon-btn i {
        line-height: 1;
    }

    .pending-card-header {
        gap: 0.35rem;
        padding-top: 0.45rem;
        padding-bottom: 0.45rem;
        padding-left: 0.75rem;
        padding-right: 0.75rem;
        border-bottom: 1px solid #dee2e6;
    }

    .pending-card-title {
        font-size: 0.92rem;
        font-weight: 600;
        color: #212529;
        margin: 0;
    }

    .pending-header-actions {
        display: flex;
        align-items: center;
        gap: 0.35rem !important;
    }

    .pending-total-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 28px;
        min-width: 28px;
        font-size: 0.72rem;
        line-height: 1;
        padding: 0 0.42rem;
    }

    .pending-view-all-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 28px;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0 0.56rem;
        line-height: 1;
        border-radius: 0.6rem;
        margin: 0;
    }
</style>

<div class="admin-container" style="max-width:100%;">
    <div class="kelola-header mb-4">
        <h1>Dashboard Real-Time</h1>
        <p class="text-muted mb-0">Fokus data hari ini, status ruangan saat ini, dan tindakan cepat.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card-slim bg-primary">
                <div class="stat-card-header"><span class="stat-value"><?= $bookingHariIni ?></span></div>
                <div class="stat-card-label">Booking Hari Ini</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card-slim bg-success">
                <div class="stat-card-header"><span class="stat-value"><?= $ruanganTerpakai ?></span></div>
                <div class="stat-card-label">Ruangan Sedang Dipakai</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card-slim bg-danger">
                <div class="stat-card-header"><span class="stat-value"><?= $pendingTotal ?></span></div>
                <div class="stat-card-label">Pending Approval (Semua)</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card shadow-sm top-equal-card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Status Ruangan Saat Ini</h6>
                </div>
                <div class="card-body room-status-body"><canvas id="roomStatusChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm top-equal-card pending-equal-card">
                <div
                    class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap pending-card-header">
                    <h6 class="pending-card-title">Pending Booking Requests</h6>
                    <div class="d-flex align-items-center pending-header-actions">
                        <span
                            class="badge bg-danger-subtle text-danger border border-danger-subtle pending-total-badge"><?= $pendingTotal ?></span>
                        <a href="persetujuan.php" class="btn btn-outline-danger pending-view-all-btn">Lihat semua</a>
                    </div>
                </div>
                <div class="table-responsive dashboard-scroll-thin">
                    <table class="table table-hover mb-0 align-middle" id="pendingCompactTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Mahasiswa</th>
                                <th>Ruangan</th>
                                <th>Waktu</th>
                                <th>Kegiatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$pendingList): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada antrean pending.</td>
                                </tr>
                            <?php else:
                                foreach ($pendingList as $i => $item): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= e($item['nama_user']) ?><br><small
                                                class="text-muted"><?= e($item['prodi'] ?? '-') ?></small></td>
                                        <td><?= e($item['nama_ruangan']) ?><br><small
                                                class="text-muted"><?= e($item['gedung'] ?? '-') ?></small></td>
                                        <td><?= e(date('d M Y', strtotime((string) $item['tanggal']))) ?><br><small
                                                class="text-muted"><?= e(substr((string) $item['jam_mulai'], 0, 5)) ?> -
                                                <?= e(substr((string) $item['jam_selesai'], 0, 5)) ?></small></td>
                                        <td><?= e($item['nama_kegiatan']) ?></td>
                                        <td>
                                            <form method="POST" class="pending-action-form" style="padding:0;">
                                                <input type="hidden" name="peminjaman_id" value="<?= (int) $item['id'] ?>">
                                                <input type="text" name="catatan_admin" class="form-control form-control-sm"
                                                    placeholder="Catatan">
                                                <button class="btn btn-success pending-icon-btn" name="action" value="approve"
                                                    title="Setujui" aria-label="Setujui"
                                                    onclick="return confirm('Setujui pengajuan ini?')"><i
                                                        class="bi bi-check-lg"></i></button>
                                                <button class="btn btn-danger pending-icon-btn" name="action" value="reject"
                                                    title="Tolak" aria-label="Tolak"
                                                    onclick="return confirm('Tolak pengajuan ini?')"><i
                                                        class="bi bi-x-lg"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Today's Schedule</h6>
                </div>
                <div class="table-responsive dashboard-scroll-220 dashboard-scroll-thin">
                    <table class="table table-hover mb-0 align-middle" id="todayScheduleCompactTable">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Ruangan</th>
                                <th>Peminjam</th>
                                <th>Kegiatan</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$jadwalHariIni): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Belum ada jadwal hari ini.</td>
                                </tr>
                            <?php else:
                                $todayStatusBadgeMap = [
                                    'Disetujui' => 'success',
                                    'Selesai' => 'success',
                                    'Ditolak' => 'danger',
                                    'Menunggu' => 'warning',
                                    'Dibatalkan' => 'secondary',
                                ];
                                foreach ($jadwalHariIni as $jadwal):
                                    $todayStatusBadge = $todayStatusBadgeMap[$jadwal['nama_status']] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td><?= e(substr((string) $jadwal['jam_mulai'], 0, 5)) ?> -
                                            <?= e(substr((string) $jadwal['jam_selesai'], 0, 5)) ?>
                                        </td>
                                        <td><?= e($jadwal['nama_ruangan']) ?><br><small
                                                class="text-muted"><?= e($jadwal['gedung'] ?? '-') ?></small></td>
                                        <td><?= e($jadwal['nama_peminjam']) ?></td>
                                        <td><?= e($jadwal['nama_kegiatan']) ?></td>
                                        <td><span
                                                class="badge bg-<?= e($todayStatusBadge) ?>"><?= e($jadwal['nama_status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Status Hari Ini</h6>
                </div>
                <div class="card-body" style="height:220px;"><canvas id="todayStatusChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    new Chart(document.getElementById('roomStatusChart'), {
        type: 'doughnut',
        data: { labels: ['Terpakai', 'Tersedia'], datasets: [{ data: [<?= $ruanganTerpakai ?>, <?= $ruanganTersedia ?>], backgroundColor: ['#ef4444', '#10b981'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } } } }
    });

    new Chart(document.getElementById('todayStatusChart'), {
        type: 'bar',
        data: { labels: ['Disetujui', 'Pending', 'Ditolak'], datasets: [{ data: [<?= $disetujuiHariIni ?>, <?= $pendingHariIni ?>, <?= $ditolakHariIni ?>], backgroundColor: ['#10b981', '#f59e0b', '#ef4444'], borderRadius: 8, borderSkipped: false }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 } } } }
    });
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>