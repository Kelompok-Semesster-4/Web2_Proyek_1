<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/role.php';
require_once __DIR__ . '/../config/koneksi.php';

requireLogin();
requireRole('admin');
autoMarkSelesai();

$pageTitle = 'Laporan & Analisis';
$activeAdmin = 'laporan';

$year = (int) ($_GET['year'] ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));
if ($year < 2000 || $year > 2100) $year = (int) date('Y');
if ($month < 1 || $month > 12) $month = (int) date('n');

$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate));
$yearStart = sprintf('%04d-01-01', $year);
$yearEnd = sprintf('%04d-12-31', $year);

$ruanganId = (int) ($_GET['ruangan_id'] ?? 0);
$statusId = (int) ($_GET['status_id'] ?? 0);

$reportWhere = 'p.tanggal BETWEEN ? AND ?';
$reportParams = [$startDate, $endDate];
if ($ruanganId > 0) { $reportWhere .= ' AND p.ruangan_id = ?'; $reportParams[] = $ruanganId; }
if ($statusId > 0) { $reportWhere .= ' AND p.status_id = ?'; $reportParams[] = $statusId; }

$ruanganList = query("SELECT r.id, r.nama_ruangan, g.nama_gedung AS gedung FROM ruangan r LEFT JOIN lantai l ON l.id = r.lantai_id LEFT JOIN gedung g ON g.id = l.gedung_id ORDER BY g.nama_gedung, r.nama_ruangan")->fetchAll();
$statusList = query('SELECT id, nama_status FROM status_peminjaman ORDER BY id')->fetchAll();

$totalRequests = (int) (query("SELECT COUNT(*) AS total FROM peminjaman p WHERE $reportWhere", $reportParams)->fetch()['total'] ?? 0);
$statusCountsRaw = query("SELECT sp.id, sp.nama_status, COUNT(p.id) AS jumlah FROM status_peminjaman sp LEFT JOIN peminjaman p ON p.status_id = sp.id AND $reportWhere GROUP BY sp.id, sp.nama_status ORDER BY sp.id", $reportParams)->fetchAll();
$statusCounts = [];
foreach ($statusCountsRaw as $row) $statusCounts[(int) $row['id']] = ['nama' => (string) $row['nama_status'], 'jumlah' => (int) $row['jumlah']];

$approvedCount = (int) ($statusCounts[2]['jumlah'] ?? 0);
$rejectedCount = (int) ($statusCounts[3]['jumlah'] ?? 0);
$approvalRate = $totalRequests > 0 ? round(($approvedCount / $totalRequests) * 100, 1) : 0.0;
$rejectionRate = $totalRequests > 0 ? round(($rejectedCount / $totalRequests) * 100, 1) : 0.0;

if (!function_exists('normalize_time_text')) {
    function normalize_time_text(string $timeText): string
    {
        return preg_replace('/\.\d+$/', '', $timeText) ?? $timeText;
    }
}

$durationWhere = $reportWhere . ' AND p.status_id IN (2,4)';
$totalJam = (string) (query("SELECT COALESCE(SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(p.jam_selesai, p.jam_mulai)))), '00:00:00') AS total_jam FROM peminjaman p WHERE $durationWhere", $reportParams)->fetch()['total_jam'] ?? '00:00:00');
$avgDurasi = (string) (query("SELECT COALESCE(SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(p.jam_selesai, p.jam_mulai)))), '00:00:00') AS avg_durasi FROM peminjaman p WHERE $durationWhere", $reportParams)->fetch()['avg_durasi'] ?? '00:00:00');
$totalJam = normalize_time_text($totalJam);
$avgDurasi = normalize_time_text($avgDurasi);

$dailyActivity = query("SELECT p.tanggal, COUNT(*) AS total_pengajuan FROM peminjaman p WHERE $reportWhere GROUP BY p.tanggal ORDER BY p.tanggal ASC", $reportParams)->fetchAll();

$yearTrendWhere = 'p.tanggal BETWEEN ? AND ?';
$yearTrendParams = [$yearStart, $yearEnd];
if ($ruanganId > 0) { $yearTrendWhere .= ' AND p.ruangan_id = ?'; $yearTrendParams[] = $ruanganId; }
if ($statusId > 0) { $yearTrendWhere .= ' AND p.status_id = ?'; $yearTrendParams[] = $statusId; }

$yearlyTrendRaw = query("SELECT MONTH(p.tanggal) AS month_num, COUNT(*) AS total_pengajuan, SUM(p.status_id = 2) AS disetujui, SUM(p.status_id = 3) AS ditolak FROM peminjaman p WHERE $yearTrendWhere GROUP BY MONTH(p.tanggal) ORDER BY MONTH(p.tanggal)", $yearTrendParams)->fetchAll();
$monthlyVisitsRaw = query("SELECT MONTH(p.tanggal) AS month_num, COUNT(DISTINCT p.user_id) AS total_kunjungan FROM peminjaman p WHERE p.tanggal BETWEEN ? AND ? GROUP BY MONTH(p.tanggal) ORDER BY MONTH(p.tanggal)", [$yearStart, $yearEnd])->fetchAll();

$topRuangan = query("SELECT r.id, g.nama_gedung AS gedung, r.nama_ruangan, COUNT(p.id) AS jumlah_booking, COALESCE(SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(p.jam_selesai, p.jam_mulai)))), '00:00:00') AS total_jam FROM peminjaman p JOIN ruangan r ON r.id = p.ruangan_id LEFT JOIN lantai l ON l.id = r.lantai_id LEFT JOIN gedung g ON g.id = l.gedung_id WHERE $durationWhere GROUP BY r.id, g.nama_gedung, r.nama_ruangan ORDER BY jumlah_booking DESC LIMIT 10", $reportParams)->fetchAll();
foreach ($topRuangan as &$room) $room['total_jam'] = normalize_time_text((string) ($room['total_jam'] ?? '00:00:00'));
unset($room);

$userDistribution = query("SELECT SUM(role = 'admin') AS admin_total, SUM(role = 'mahasiswa') AS mahasiswa_total FROM users")->fetch();
$hoursUsedSeconds = (int) (query("SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(p.jam_selesai, p.jam_mulai))), 0) AS total_detik FROM peminjaman p WHERE $durationWhere", $reportParams)->fetch()['total_detik'] ?? 0);
$roomCount = $ruanganId > 0 ? 1 : (int) (query('SELECT COUNT(*) AS total FROM ruangan')->fetch()['total'] ?? 0);
$capacityHours = max(1.0, $roomCount * ((int) date('t', strtotime($startDate))) * 12.0);
$usedHours = round($hoursUsedSeconds / 3600, 2);
$utilizationRate = round(min(100, ($usedHours / $capacityHours) * 100), 1);

$detailBaseSql = "SELECT p.id, p.tanggal, p.jam_mulai, p.jam_selesai, p.nama_kegiatan, p.jumlah_peserta, p.catatan_admin, sp.nama_status, u.nama AS nama_peminjam, u.prodi, g.nama_gedung AS gedung, r.nama_ruangan FROM peminjaman p JOIN users u ON u.id = p.user_id JOIN ruangan r ON r.id = p.ruangan_id LEFT JOIN lantai l ON l.id = r.lantai_id LEFT JOIN gedung g ON g.id = l.gedung_id JOIN status_peminjaman sp ON sp.id = p.status_id WHERE $reportWhere ORDER BY p.tanggal DESC, p.jam_mulai DESC, p.id DESC";

if (($_GET['export'] ?? '') === 'csv') {
    $detail = query($detailBaseSql, $reportParams)->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_' . $year . '_' . sprintf('%02d', $month) . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Tanggal', 'Jam Mulai', 'Jam Selesai', 'Durasi (menit)', 'Ruangan', 'Peminjam', 'Prodi', 'Kegiatan', 'Peserta', 'Status', 'Catatan']);
    foreach ($detail as $d) {
        $durMin = 0;
        if (!empty($d['jam_mulai']) && !empty($d['jam_selesai'])) $durMin = (int) round((strtotime($d['tanggal'] . ' ' . $d['jam_selesai']) - strtotime($d['tanggal'] . ' ' . $d['jam_mulai'])) / 60);
        fputcsv($out, [$d['id'], $d['tanggal'], substr((string) $d['jam_mulai'], 0, 5), substr((string) $d['jam_selesai'], 0, 5), $durMin, $d['gedung'] . ' - ' . $d['nama_ruangan'], $d['nama_peminjam'], $d['prodi'] ?? '-', $d['nama_kegiatan'], $d['jumlah_peserta'] ?? '', $d['nama_status'], $d['catatan_admin'] ?? '']);
    }
    fclose($out);
    exit;
}

$detailPerPage = 10;
$detailTotalRows = (int) (query("SELECT COUNT(*) AS total FROM peminjaman p WHERE $reportWhere", $reportParams)->fetch()['total'] ?? 0);
$detailTotalPages = max(1, (int) ceil($detailTotalRows / $detailPerPage));
$detailCurrentPage = max(1, (int) ($_GET['page'] ?? 1));
if ($detailCurrentPage > $detailTotalPages) {
    $detailCurrentPage = $detailTotalPages;
}
$detailOffset = ($detailCurrentPage - 1) * $detailPerPage;
$detailPagedSql = $detailBaseSql . ' LIMIT ' . $detailPerPage . ' OFFSET ' . $detailOffset;
$detail = query($detailPagedSql, $reportParams)->fetchAll();

$detailQueryBase = [
    'year' => $year,
    'month' => $month,
    'ruangan_id' => $ruanganId,
    'status_id' => $statusId,
];

$yearlyTrendMap = [];
foreach ($yearlyTrendRaw as $row) $yearlyTrendMap[(int) $row['month_num']] = $row;
$monthlyVisitMap = [];
foreach ($monthlyVisitsRaw as $row) $monthlyVisitMap[(int) $row['month_num']] = $row;

$monthLabels = [];
$trendTotalData = [];
$trendApprovedData = [];
$trendRejectedData = [];
$visitData = [];
for ($m = 1; $m <= 12; $m++) {
    $monthLabels[] = date('M', mktime(0, 0, 0, $m, 1));
    $trendRow = $yearlyTrendMap[$m] ?? null;
    $visitRow = $monthlyVisitMap[$m] ?? null;
    $trendTotalData[] = (int) ($trendRow['total_pengajuan'] ?? 0);
    $trendApprovedData[] = (int) ($trendRow['disetujui'] ?? 0);
    $trendRejectedData[] = (int) ($trendRow['ditolak'] ?? 0);
    $visitData[] = (int) ($visitRow['total_kunjungan'] ?? 0);
}

$dailyLabels = [];
$dailyTotals = [];
foreach ($dailyActivity as $d) { $dailyLabels[] = date('d M', strtotime((string) $d['tanggal'])); $dailyTotals[] = (int) ($d['total_pengajuan'] ?? 0); }

require_once __DIR__ . '/../templates/admin_head.php';
require_once __DIR__ . '/../templates/admin_sidebar.php';
?>

<style>
html {
    scroll-behavior: auto !important;
}

:root {
    --usage-card-height: 210px;
}

.usage-equal-card {
    height: var(--usage-card-height);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.usage-analysis-body {
    flex: 1 1 auto;
}

.usage-table-scroll {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(15, 23, 42, 0.35) transparent;
}

.usage-table-scroll::-webkit-scrollbar {
    width: 6px;
}

.usage-table-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.usage-table-scroll::-webkit-scrollbar-thumb {
    background: rgba(15, 23, 42, 0.35);
    border-radius: 999px;
}

.filter-report-body {
    padding-top: 0.1rem;
    padding-bottom: 0.1rem;
}

.filter-report-form {
    row-gap: 0.55rem !important;
}

.filter-report-form .form-label {
    margin-bottom: 0.1rem;
    font-size: 0.95rem;
}

.filter-report-form .form-select {
    height: 42px;
    padding-top: 0.1rem;
    padding-bottom: 0.1rem;
}

.filter-report-form .btn {
    min-height: 42px;
    padding-top: 0.4rem;
    padding-bottom: 0.4rem;
}
</style>

<div class="admin-container" style="max-width:100%;">
    <div class="kelola-header mb-4">
        <h1>Laporan</h1>
        <p class="text-muted mb-0">Seluruh chart bulanan, statistik penggunaan, dan tabel transaksi detail ada di halaman ini.</p>
    </div>

    <div class="card shadow border-0 mb-4">
        <div class="card-header bg-light"><h6 class="mb-0">Filter Laporan</h6></div>
        <div class="card-body filter-report-body">
            <form class="row g-2 align-items-end filter-report-form" method="GET">
                <div class="col-md-2"><label class="form-label">Bulan</label><select name="month" class="form-select"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option><?php endfor; ?></select></div>
                <div class="col-md-2"><label class="form-label">Tahun</label><select name="year" class="form-select"><?php $yNow = (int) date('Y'); for ($y = $yNow - 3; $y <= $yNow + 1; $y++): ?><option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option><?php endfor; ?></select></div>
                <div class="col-md-4"><label class="form-label">Ruangan</label><select name="ruangan_id" class="form-select"><option value="0">Semua Ruangan</option><?php foreach ($ruanganList as $r): ?><option value="<?= (int) $r['id'] ?>" <?= ((int) $r['id'] === $ruanganId) ? 'selected' : '' ?>><?= e(($r['gedung'] ?? '-') . ' - ' . $r['nama_ruangan']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><label class="form-label">Status</label><select name="status_id" class="form-select"><option value="0">Semua Status</option><?php foreach ($statusList as $s): ?><option value="<?= (int) $s['id'] ?>" <?= ((int) $s['id'] === $statusId) ? 'selected' : '' ?>><?= e($s['nama_status']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><button type="submit" class="btn btn-success w-100">Terapkan</button></div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card-slim bg-primary"><div class="stat-card-header"><span class="stat-value"><?= $totalRequests ?></span></div><div class="stat-card-label">Total Request</div></div></div>
        <div class="col-md-3"><div class="stat-card-slim bg-success"><div class="stat-card-header"><span class="stat-value"><?= number_format($approvalRate, 1) ?>%</span></div><div class="stat-card-label">Approval Rate</div></div></div>
        <div class="col-md-3"><div class="stat-card-slim bg-danger"><div class="stat-card-header"><span class="stat-value"><?= number_format($rejectionRate, 1) ?>%</span></div><div class="stat-card-label">Rejection Rate</div></div></div>
        <div class="col-md-3"><div class="stat-card-slim bg-info"><div class="stat-card-header"><span class="stat-value" style="font-size:20px;"><?= e($avgDurasi) ?></span></div><div class="stat-card-label">Rata-rata Durasi</div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8"><div class="card shadow-sm"><div class="card-header bg-light"><h6 class="mb-0">Booking Trends Over Time (<?= $year ?>)</h6></div><div class="card-body" style="height:280px;"><canvas id="bookingTrendChart"></canvas></div></div></div>
        <div class="col-lg-4"><div class="card shadow-sm"><div class="card-header bg-light"><h6 class="mb-0">Daily Activity (<?= e(date('F Y', strtotime($startDate))) ?>)</h6></div><div class="card-body" style="height:280px;"><canvas id="dailyActivityChart"></canvas></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header bg-light"><h6 class="mb-0">Kunjungan Bulanan</h6></div><div class="card-body" style="height:240px;"><canvas id="monthlyVisitChart"></canvas></div></div></div>
        <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header bg-light"><h6 class="mb-0">Distribusi Pengguna</h6></div><div class="card-body" style="height:240px;"><canvas id="userDistributionChart"></canvas></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card shadow-sm usage-equal-card"><div class="card-header bg-light"><h6 class="mb-0">Room Usage Analysis</h6></div><div class="card-body usage-analysis-body"><div class="d-flex justify-content-between mb-2"><span>Total Jam Digunakan</span><strong><?= e($totalJam) ?></strong></div><div class="d-flex justify-content-between mb-2"><span>Total Jam (desimal)</span><strong><?= number_format($usedHours, 2) ?> jam</strong></div><div class="d-flex justify-content-between mb-2"><span>Kapasitas Jam</span><strong><?= number_format($capacityHours, 0) ?> jam</strong></div><div class="d-flex justify-content-between mb-3"><span>Utilization Rate</span><strong><?= number_format($utilizationRate, 1) ?>%</strong></div><div class="progress" style="height:10px;"><div class="progress-bar bg-success" style="width:<?= $utilizationRate ?>%;"></div></div></div></div>
        </div>
        <div class="col-lg-8"><div class="card shadow-sm usage-equal-card"><div class="card-header bg-light"><h6 class="mb-0">Most Used Rooms</h6></div><div class="table-responsive usage-table-scroll"><table class="table table-hover mb-0 align-middle"><thead><tr><th>#</th><th>Ruangan</th><th class="text-center">Jumlah Booking</th><th class="text-center">Total Jam</th></tr></thead><tbody><?php if (!$topRuangan): ?><tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada data.</td></tr><?php else: foreach ($topRuangan as $i => $room): ?><tr><td><?= $i + 1 ?></td><td><?= e($room['nama_ruangan']) ?><br><small class="text-muted"><?= e($room['gedung'] ?? '-') ?></small></td><td class="text-center"><span class="badge bg-info"><?= (int) $room['jumlah_booking'] ?></span></td><td class="text-center"><span class="badge bg-success"><?= e($room['total_jam']) ?></span></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div>
    </div>

    <div class="card shadow-sm" id="detail-transaction-table">
        <div class="card-header bg-light d-flex justify-content-between align-items-center"><h6 class="mb-0">Detail Transaction Table</h6><a class="btn btn-sm btn-success" href="?year=<?= $year ?>&month=<?= $month ?>&ruangan_id=<?= $ruanganId ?>&status_id=<?= $statusId ?>&export=csv">Export CSV</a></div>
        <div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>#</th><th>Tanggal</th><th>Jam</th><th>Ruangan</th><th>Peminjam</th><th>Prodi</th><th>Kegiatan</th><th class="text-center">Peserta</th><th class="text-center">Status</th><th>Catatan</th></tr></thead><tbody><?php if (!$detail): ?><tr><td colspan="10" class="text-center py-5 text-muted">Tidak ada data transaksi.</td></tr><?php else: $statusBadgeMap=['Menunggu'=>'warning','Disetujui'=>'success','Ditolak'=>'danger','Selesai'=>'info','Dibatalkan'=>'secondary']; foreach ($detail as $idx => $d): $badgeClass=$statusBadgeMap[$d['nama_status']] ?? 'secondary'; ?><tr><td><?= $detailOffset + $idx + 1 ?></td><td><?= e(date('d M Y', strtotime((string) $d['tanggal']))) ?></td><td><?= e(substr((string) $d['jam_mulai'], 0, 5)) ?> - <?= e(substr((string) $d['jam_selesai'], 0, 5)) ?></td><td><?= e($d['nama_ruangan']) ?><br><small class="text-muted"><?= e($d['gedung'] ?? '-') ?></small></td><td><?= e($d['nama_peminjam']) ?></td><td><?= !empty($d['prodi']) ? e((string) $d['prodi']) : '-' ?></td><td><?= e($d['nama_kegiatan']) ?></td><td class="text-center"><span class="badge bg-info"><?= e((string) ($d['jumlah_peserta'] ?? '-')) ?></span></td><td class="text-center"><span class="badge bg-<?= $badgeClass ?>"><?= e($d['nama_status']) ?></span></td><td><?= !empty($d['catatan_admin']) ? e((string) $d['catatan_admin']) : '-' ?></td></tr><?php endforeach; endif; ?></tbody></table></div>
        <?php if ($detailTotalRows > 0): ?>
        <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">Menampilkan <?= $detailOffset + 1 ?>-<?= min($detailOffset + count($detail), $detailTotalRows) ?> dari <?= $detailTotalRows ?> data</small>
            <nav aria-label="Detail transaction pagination">
                <ul class="pagination pagination-sm mb-0">
                    <?php $prevPage = max(1, $detailCurrentPage - 1); $prevQuery = http_build_query($detailQueryBase + ['page' => $prevPage]); ?>
                    <li class="page-item <?= $detailCurrentPage <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= e($prevQuery) ?>#detail-transaction-table">Prev</a></li>
                    <?php for ($p = 1; $p <= $detailTotalPages; $p++): $pageQuery = http_build_query($detailQueryBase + ['page' => $p]); ?>
                    <li class="page-item <?= $p === $detailCurrentPage ? 'active' : '' ?>"><a class="page-link" href="?<?= e($pageQuery) ?>#detail-transaction-table"><?= $p ?></a></li>
                    <?php endfor; ?>
                    <?php $nextPage = min($detailTotalPages, $detailCurrentPage + 1); $nextQuery = http_build_query($detailQueryBase + ['page' => $nextPage]); ?>
                    <li class="page-item <?= $detailCurrentPage >= $detailTotalPages ? 'disabled' : '' ?>"><a class="page-link" href="?<?= e($nextQuery) ?>#detail-transaction-table">Next</a></li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const monthLabels = <?= json_encode($monthLabels) ?>;
new Chart(document.getElementById('bookingTrendChart'), {type:'line',data:{labels:monthLabels,datasets:[{label:'Total Request',data:<?= json_encode($trendTotalData) ?>,borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.12)',tension:.35,fill:true},{label:'Disetujui',data:<?= json_encode($trendApprovedData) ?>,borderColor:'#10b981',tension:.35},{label:'Ditolak',data:<?= json_encode($trendRejectedData) ?>,borderColor:'#ef4444',tension:.35}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{usePointStyle:true}}},scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('dailyActivityChart'), {type:'bar',data:{labels:<?= json_encode($dailyLabels) ?>,datasets:[{data:<?= json_encode($dailyTotals) ?>,backgroundColor:'#3b82f6',borderRadius:6,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('monthlyVisitChart'), {type:'bar',data:{labels:monthLabels,datasets:[{data:<?= json_encode($visitData) ?>,backgroundColor:'#0ea5e9',borderRadius:8,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}},x:{grid:{display:false}}}}});
new Chart(document.getElementById('userDistributionChart'), {type:'doughnut',data:{labels:['Admin','Mahasiswa'],datasets:[{data:[<?= (int) ($userDistribution['admin_total'] ?? 0) ?>,<?= (int) ($userDistribution['mahasiswa_total'] ?? 0) ?>],backgroundColor:['#2563eb','#06b6d4'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{usePointStyle:true}}}}});
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
