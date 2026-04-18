<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

// Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$activeAdmin = 'gedung';
$pageTitle = "Kelola Gedung";

// Ambil data gedung + jumlah lantai + jumlah ruangan
$stmt = query(
    "SELECT g.id, g.nama_gedung,
            (
                SELECT COUNT(*)
                FROM lantai l
                WHERE l.gedung_id = g.id
            ) AS jumlah_lantai,
            (
                SELECT COUNT(*)
                FROM ruangan r
                INNER JOIN lantai l2 ON l2.id = r.lantai_id
                WHERE l2.gedung_id = g.id
            ) AS jumlah_ruangan
     FROM gedung g
     ORDER BY g.nama_gedung ASC"
);
$gedungs = $stmt->fetchAll();

require_once __DIR__ . "/../templates/admin_head.php";
require_once __DIR__ . "/../templates/admin_sidebar.php";
?>

<div class="admin-container" style="max-width: 100%;">
    <!-- Page Header -->
    <div class="kelola-header mb-4">
        <h1>Kelola Gedung</h1>
        <button class="btn-tambah" data-bs-toggle="modal" data-bs-target="#modalAddGedung">
            <i class="bi bi-plus-circle-fill me-2"></i>Tambah Gedung
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php
            switch ($_GET['success']) {
                case 'add':
                    echo "<strong>Berhasil!</strong> Gedung berhasil ditambahkan.";
                    break;
                case 'edit':
                    echo "<strong>Berhasil!</strong> Gedung berhasil diperbarui.";
                    break;
                case 'delete':
                    echo "<strong>Berhasil!</strong> Gedung berhasil dihapus.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Error!</strong> <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Card Tabel Gedung -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom"
            style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-building me-2" style="color: #22c55e;"></i>Daftar Gedung
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search" style="color: #22c55e;"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 bg-white" id="searchInput"
                            placeholder="Cari gedung..." style="border-left: 0;">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableGedung">
                    <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                        <tr>
                            <th class="text-center" style="width: 50px; padding: 15px 10px;">
                                <i class="bi bi-hash"></i>
                            </th>
                            <th class="text-center" style="width: 35%; padding: 15px;">
                                <i class="bi bi-building me-1"></i>Nama Gedung
                            </th>
                            <th class="text-center" style="width: 15%; padding: 15px;">
                                <i class="bi bi-layers me-1"></i>Lantai
                            </th>
                            <th class="text-center" style="width: 20%; padding: 15px;">
                                <i class="bi bi-door-closed me-1"></i>Ruangan
                            </th>
                            <th class="text-center" style="width: 280px; padding: 15px;">
                                <i class="bi bi-gear me-1"></i>Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gedungs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <p class="mb-0">Belum ada data gedung</p>
                                        <small>Tambahkan gedung pertama Anda</small>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($gedungs as $i => $gedung): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge-number">
                                            <?= $i + 1 ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-building-fill me-1"></i><span class="gedung-name"><?php echo htmlspecialchars($gedung['nama_gedung']); ?></span>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-layers-fill me-1"></i><?php echo (int)($gedung['jumlah_lantai'] ?? 0); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-door-closed-fill me-1"></i><?php echo (int)($gedung['jumlah_ruangan'] ?? 0); ?> ruangan
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button type="button" class="btn btn-info aksi-btn" style="min-width: 65px; font-size: 0.8rem;"
                                                onclick="viewDetailGedung('<?php echo htmlspecialchars($gedung['nama_gedung'], ENT_QUOTES); ?>', '<?php echo (int)($gedung['jumlah_lantai'] ?? 0); ?>', '<?php echo (int)($gedung['jumlah_ruangan'] ?? 0); ?>')">
                                                <i class="bi bi-eye-fill me-1"></i>Detail
                                            </button>
                                            <button type="button" class="btn btn-warning aksi-btn" style="min-width: 60px; font-size: 0.8rem;" data-bs-toggle="modal"
                                                data-bs-target="#modalEditGedung"
                                                onclick="editGedung(<?php echo $gedung['id']; ?>, '<?php echo htmlspecialchars($gedung['nama_gedung'], ENT_QUOTES); ?>')">
                                                <i class="bi bi-pencil-fill me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-danger aksi-btn" style="min-width: 65px; font-size: 0.8rem;"
                                                onclick="deleteGedung(<?php echo $gedung['id']; ?>, '<?php echo htmlspecialchars($gedung['nama_gedung'], ENT_QUOTES); ?>')">
                                                <i class="bi bi-trash-fill me-1"></i>Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted fw-semibold">
                    <i class="bi bi-info-circle-fill me-1" style="color: #22c55e;"></i>Total Data:
                    <span class="badge ms-1"
                        style="background: linear-gradient(135deg, #22c55e, #16a34a);"><?= count($gedungs) ?></span>
                    gedung terdaftar
                </small>
                <small class="text-muted">
                    <i class="bi bi-calendar-check me-1"></i><?= date('d F Y') ?>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Gedung -->
<div class="modal fade" id="modalAddGedung" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white"
                style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle-fill me-2"></i>Tambah Gedung Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="gedung/tambah.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="addNamaGedung" class="form-label fw-semibold">
                            <i class="bi bi-building me-1" style="color: #22c55e;"></i>Nama Gedung
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="addNamaGedung" name="nama_gedung"
                            maxlength="100" required placeholder="Contoh: Gedung A">
                        <small class="text-muted">Nama gedung harus unik</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Gedung -->
<div class="modal fade" id="modalEditGedung" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white"
                style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Gedung
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="gedung/edit.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" id="editGedungId" name="id">
                    <div class="mb-3">
                        <label for="editNamaGedung" class="form-label fw-semibold">
                            <i class="bi bi-building me-1" style="color: #22c55e;"></i>Nama Gedung
                            <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="editNamaGedung" name="nama_gedung"
                            maxlength="100" required placeholder="Contoh: Gedung A">
                        <small class="text-muted">Nama gedung harus unik</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle me-1"></i>Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Gedung -->
<div class="modal fade" id="modalViewDetailGedung" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white"
                style="background: linear-gradient(135deg, #22b8cf 0%, #0ea5b7 100%); border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-eye-fill me-2"></i>Detail Gedung
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted mb-1">Nama Gedung</label>
                    <div id="detailGedungNama" class="fw-bold fs-5"></div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <label class="form-label fw-semibold text-muted mb-1">Jumlah Lantai</label>
                        <div id="detailGedungLantai" class="fw-bold"></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold text-muted mb-1">Jumlah Ruangan</label>
                        <div id="detailGedungRuangan" class="fw-bold"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#tableGedung tbody tr');

        tableRows.forEach(row => {
            const gedungName = row.querySelector('.gedung-name')?.textContent.toLowerCase() || '';
            if (gedungName.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // View detail gedung
    function viewDetailGedung(namaGedung, jumlahLantai, jumlahRuangan) {
        document.getElementById('detailGedungNama').textContent = namaGedung || '-';
        document.getElementById('detailGedungLantai').textContent = `${jumlahLantai || 0} lantai`;
        document.getElementById('detailGedungRuangan').textContent = `${jumlahRuangan || 0} ruangan`;

        const modal = new bootstrap.Modal(document.getElementById('modalViewDetailGedung'));
        modal.show();
    }

    // Edit gedung function
    function editGedung(id, namaGedung) {
        document.getElementById('editGedungId').value = id;
        document.getElementById('editNamaGedung').value = namaGedung;
    }

    // Delete gedung with confirmation
    function deleteGedung(id, namaGedung) {
        Swal.fire({
            title: 'Hapus Gedung?',
            html: `<strong>${namaGedung}</strong><br><small class="text-muted">Data ini akan dihapus secara permanen</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bi bi-trash me-2"></i>Hapus',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'gedung/hapus.php?id=' + id;
            }
        });
    }

    // Reset add form when modal is closed
    document.getElementById('modalAddGedung').addEventListener('hidden.bs.modal', function() {
        document.getElementById('addNamaGedung').value = '';
    });

    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alertsToClose = document.querySelectorAll('.alert');
        alertsToClose.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>