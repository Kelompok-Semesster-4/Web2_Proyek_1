<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$activeAdmin = 'dashboard';
$pageTitle = "Dashboard";

$ruangan = query(
    "SELECT SUM(terpakai) AS ruangan_terpakai, SUM(1 - terpakai) AS ruangan_tersedia 
    FROM (
        SELECT r.id, MAX(CASE
            WHEN p.status_id = 2 
                AND p.tanggal = CURDATE() 
                AND CURTIME() BETWEEN p.jam_mulai AND p.jam_selesai 
            THEN 1
            ELSE 0
            END
        ) AS terpakai
        FROM ruangan AS r LEFT JOIN peminjaman as p on r.id=p.ruangan_id
        GROUP BY r.id
    ) AS status_ruangan"
)->fetchAll()[0];

$users = query(
    "SELECT 
        COUNT(*) AS total_pengguna, 
        COUNT(IF(role = 'admin', 1, NULL)) AS total_admin, 
        COUNT(IF(role = 'mahasiswa', 1, NULL)) AS total_mahasiswa 
    FROM users"
)->fetchAll()[0];

$peminjaman = query(
    "SELECT 
        COUNT(IF(status_id = 1, 1, NULL)) AS pending_hari_ini, 
        COUNT(IF(status_id = 2, 1, NULL)) AS disetujui_hari_ini, 
        COUNT(IF(status_id = 3, 1, NULL)) AS ditolak_hari_ini
    FROM peminjaman
    WHERE tanggal = CURDATE()"
)->fetchAll()[0];

$most_borrowed = query(
    "SELECT r.id, r.nama_ruangan, g.nama_gedung AS gedung,
            COUNT(p.id) AS j_pinjaman,
            COALESCE(SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(p.jam_selesai, p.jam_mulai)))), '00:00:00') AS total_jam
    FROM peminjaman p
    JOIN ruangan r ON r.id = p.ruangan_id
    LEFT JOIN lantai l ON l.id = r.lantai_id
    LEFT JOIN gedung g ON g.id = l.gedung_id
    WHERE p.status_id IN (2,4)
      AND p.tanggal BETWEEN DATE_FORMAT(CURDATE(), '%Y-%m-01') AND LAST_DAY(CURDATE())
    GROUP BY r.id, r.nama_ruangan, g.nama_gedung
    ORDER BY j_pinjaman DESC
    LIMIT 3"
)->fetchAll();

foreach ($most_borrowed as &$row) {
    $row['total_jam'] = preg_replace('/\.\d+$/', '', (string) ($row['total_jam'] ?? '00:00:00')) ?? '00:00:00';
}
unset($row);

// Data untuk grafik kunjungan bulanan (6 bulan terakhir)
$kunjunganBulanan = query(
    "SELECT 
        DATE_FORMAT(p.tanggal, '%b') AS bulan,
        MONTH(p.tanggal) AS bulan_num,
        COUNT(DISTINCT p.user_id) AS total_kunjungan
    FROM peminjaman p
    WHERE p.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(p.tanggal), MONTH(p.tanggal), DATE_FORMAT(p.tanggal, '%b')
    ORDER BY YEAR(p.tanggal), MONTH(p.tanggal) ASC"
)->fetchAll();

// Data untuk grafik peminjaman vs pengembalian bulanan
$trendPeminjamanBulanan = query(
    "SELECT 
        DATE_FORMAT(p.tanggal, '%b') AS bulan,
        MONTH(p.tanggal) AS bulan_num,
        COUNT(IF(p.status_id = 2, 1, NULL)) AS peminjaman,
        COUNT(IF(p.status_id = 4, 1, NULL)) AS pengembalian
    FROM peminjaman p
    WHERE p.tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(p.tanggal), MONTH(p.tanggal), DATE_FORMAT(p.tanggal, '%b')
    ORDER BY YEAR(p.tanggal), MONTH(p.tanggal) ASC"
)->fetchAll();

// Total statistik
$totalPeminjaman = query("SELECT COUNT(*) AS total FROM peminjaman WHERE status_id IN (2,4)")->fetchAll()[0]['total'] ?? 0;
$totalPengembalian = query("SELECT COUNT(*) AS total FROM peminjaman WHERE status_id = 4")->fetchAll()[0]['total'] ?? 0;

function renderProgressBar($max, $segments)
{
    $html = '<div class="progress mb-3" style="height: 25px;">';
    $accValue = 0;

    foreach ($segments as $segment) {
        $val = max(0, (float) $segment['value']);

        if (($accValue + $val) > $max) {
            $val = $max - $accValue;
        }

        if ($val > 0) {
            $percent = round(($val / $max) * 100, 2);

            $bgClass = $segment['bg_class'] ?? 'bg-primary';
            $textClass = $segment['text_class'] ?? '';

            $html .= sprintf(
                '<div class="progress-bar %s %s" role="progressbar" style="width: %s%%;" aria-valuenow="%s" aria-valuemin="0" aria-valuemax="%s"></div>',
                htmlspecialchars($bgClass),
                htmlspecialchars($textClass),
                $percent,
                $val,
                $max
            );
        }

        $accValue += $val;

        if ($accValue >= $max)
            break;
    }

    $html .= "</div>";
    return $html;
}

require_once __DIR__ . "/../templates/admin_head.php";
require_once __DIR__ . "/../templates/admin_sidebar.php";
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

<div class="admin-container" style="max-width: 100%">
    <div class="kelola-header mb-4">
        <h1>Dashboard</h1>
    </div>
</div>

<?php
$ruanganTerpakai = $ruangan['ruangan_terpakai'];
$ruanganTersedia = $ruangan['ruangan_tersedia'];

$peminjamanDitolak = $peminjaman['ditolak_hari_ini'];
$peminjamanPending = $peminjaman['pending_hari_ini'];
$peminjamanDisetujui = $peminjaman['disetujui_hari_ini'];

$totalPengguna = $users['total_pengguna'];
$totalAdmin = $users['total_admin'];
$totalMahasiswa = $users['total_mahasiswa'];
?>

<!-- Stat Cards - Kompak dan Modern -->
<div class="container-fluid p-3">
    <div class="row mb-3">
        <div class="col-md-6 col-lg-3 col-xl-2 mb-3">
            <div class="stat-card-slim bg-primary">
                <div class="stat-card-header">
                    <i class="bi bi-book"></i>
                    <span class="stat-value"><?= $ruanganTerpakai + $ruanganTersedia ?></span>
                </div>
                <div class="stat-card-label">Total Ruangan</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 col-xl-2 mb-3">
            <div class="stat-card-slim bg-success">
                <div class="stat-card-header">
                    <i class="bi bi-person"></i>
                    <span class="stat-value"><?= $totalPengguna ?></span>
                </div>
                <div class="stat-card-label">Total Pengguna</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 col-xl-2 mb-3">
            <div class="stat-card-slim bg-warning">
                <div class="stat-card-header">
                    <i class="bi bi-calendar2-check"></i>
                    <span class="stat-value"><?= $peminjamanDisetujui ?></span>
                </div>
                <div class="stat-card-label">Peminjaman Aktif</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 col-xl-2 mb-3">
            <div class="stat-card-slim bg-info">
                <div class="stat-card-header">
                    <i class="bi bi-door-open"></i>
                    <span class="stat-value"><?= $ruanganTersedia ?></span>
                </div>
                <div class="stat-card-label">Tersedia</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 col-xl-2 mb-3">
            <div class="stat-card-slim bg-danger">
                <div class="stat-card-header">
                    <i class="bi bi-exclamation-circle"></i>
                    <span class="stat-value"><?= $peminjamanPending ?></span>
                </div>
                <div class="stat-card-label">Pending</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 col-xl-2 mb-3">
            <div class="stat-card-slim bg-secondary">
                <div class="stat-card-header">
                    <i class="bi bi-clock-history"></i>
                    <span
                        class="stat-value"><?= $peminjamanPending + $peminjamanDitolak + $peminjamanDisetujui ?></span>
                </div>
                <div class="stat-card-label">Total Transaksi</div>
            </div>
        </div>
    </div>

    <!-- Distribution Charts Row - All Histograms -->
    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Status Ruangan
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="ruanganChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Status Peminjaman Hari Ini
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="peminjamanChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart me-2"></i>Distribusi Pengguna
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="userChart" style="max-height: 250px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Trend Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Kunjungan 6 Bulan Terakhir
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="kunjunganChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Tren Peminjaman & Pengembalian
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Most Borrowed Rooms Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-door-open-fill me-2"></i>Ruangan Paling Sering Dipinjam
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead
                                style="background: linear-gradient(to right, #f8f9fa, #e9ecef); white-space: nowrap;">
                                <tr>
                                    <th style="width: 50px; padding: 15px 10px;" class="text-center">
                                        <i class="bi bi-hash"></i>
                                    </th>
                                    <th style="padding: 15px;">
                                        <i class="bi bi-door-open me-1"></i>Ruangan
                                    </th>
                                    <th class="text-center" style="padding: 15px;">
                                        <i class="bi bi-calendar-check me-1"></i>Jumlah Booking
                                    </th>
                                    <th class="text-center" style="padding: 15px;">
                                        <i class="bi bi-clock me-1"></i>Total Jam
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$most_borrowed): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                <p class="mb-0">Belum ada data peminjaman</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($most_borrowed as $i => $r): ?>
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge-number"><?= $i + 1 ?></span>
                                            </td>
                                            <td>
                                                <span
                                                    class="fw-bold"><?= e(($r['gedung'] ?? '-') . ' - ' . $r['nama_ruangan']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?= (int) $r['j_pinjaman'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><?= e($r['total_jam'] ?? '00:00:00') ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Data untuk grafik
    const kunjunganData = <?= json_encode($kunjunganBulanan) ?>;
    const trendData = <?= json_encode($trendPeminjamanBulanan) ?>;
    const ruanganTerpakai = <?= $ruanganTerpakai ?>;
    const ruanganTersedia = <?= $ruanganTersedia ?>;
    const peminjamanDisetujui = <?= $peminjamanDisetujui ?>;
    const peminjamanPending = <?= $peminjamanPending ?>;
    const peminjamanDitolak = <?= $peminjamanDitolak ?>;
    const totalAdmin = <?= $totalAdmin ?>;
    const totalMahasiswa = <?= $totalMahasiswa ?>;

    // ===== BAR CHART: Status Ruangan =====
    const ruanganCtx = document.getElementById('ruanganChart').getContext('2d');
    const maxRuangan = ruanganTerpakai + ruanganTersedia;
    new Chart(ruanganCtx, {
        type: 'bar',
        data: {
            labels: ['Terpakai', 'Tersedia'],
            datasets: [{
                label: 'Jumlah Ruangan',
                data: [ruanganTerpakai, ruanganTersedia],
                backgroundColor: ['#ef4444', '#10b981'],
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // ===== BAR CHART: Status Peminjaman Hari Ini =====
    const peminjamanCtx = document.getElementById('peminjamanChart').getContext('2d');
    const maxPeminjaman = peminjamanDisetujui + peminjamanPending + peminjamanDitolak;
    new Chart(peminjamanCtx, {
        type: 'bar',
        data: {
            labels: ['Disetujui', 'Pending', 'Ditolak'],
            datasets: [{
                label: 'Jumlah Peminjaman',
                data: [peminjamanDisetujui, peminjamanPending, peminjamanDitolak],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // ===== BAR CHART: Distribusi Pengguna =====
    const userCtx = document.getElementById('userChart').getContext('2d');
    new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: ['Admin', 'Mahasiswa'],
            datasets: [{
                label: 'Jumlah Pengguna',
                data: [totalAdmin, totalMahasiswa],
                backgroundColor: ['#3b82f6', '#06b6d4'],
                borderRadius: 6,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // ===== BAR CHART: Kunjungan Bulanan =====
    if (kunjunganData.length > 0) {
        const kunjunganCtx = document.getElementById('kunjunganChart').getContext('2d');
        const kunjunganLabels = kunjunganData.map(d => d.bulan);
        const kunjunganValues = kunjunganData.map(d => parseInt(d.total_kunjungan));

        new Chart(kunjunganCtx, {
            type: 'bar',
            data: {
                labels: kunjunganLabels,
                datasets: [{
                    label: 'Jumlah Kunjungan',
                    data: kunjunganValues,
                    backgroundColor: [
                        '#3b82f6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'
                    ],
                    borderRadius: 6,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'x',
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    // ===== BAR CHART: Tren Peminjaman & Pengembalian =====
    if (trendData.length > 0) {
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendLabels = trendData.map(d => d.bulan);
        const peminjamanValues = trendData.map(d => parseInt(d.peminjaman));
        const pengembalianValues = trendData.map(d => parseInt(d.pengembalian));

        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: 'Peminjaman',
                        data: peminjamanValues,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                        borderSkipped: false
                    },
                    {
                        label: 'Pengembalian',
                        data: pengembalianValues,
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        borderSkipped: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'x',
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
</script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>