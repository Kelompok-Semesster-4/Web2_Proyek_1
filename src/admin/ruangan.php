<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

// Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$activeAdmin = 'ruangan';
$pageTitle = "Kelola Ruangan";

// Ambil data ruangan
$stmt = query("SELECT * FROM ruangan ORDER BY nama_ruangan ASC");
$ruangans = $stmt->fetchAll();

require_once __DIR__ . "/../templates/admin_head.php";
require_once __DIR__ . "/../templates/admin_sidebar.php";
?>

<div class="admin-container" style="max-width: 100%;">
    <!-- Page Header -->
    <div class="kelola-header mb-4">
        <h1>Kelola Ruangan</h1>
        <button class="btn-tambah" data-bs-toggle="modal" data-bs-target="#modalAddRuangan">
            Tambah Ruangan
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php
            switch ($_GET['success']) {
                case 'add':
                    echo "<strong>Berhasil!</strong> Ruangan berhasil ditambahkan.";
                    break;
                case 'edit':
                    echo "<strong>Berhasil!</strong> Ruangan berhasil diperbarui.";
                    break;
                case 'delete':
                    echo "<strong>Berhasil!</strong> Ruangan berhasil dihapus.";
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

    <!-- Card Tabel Ruangan -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom" style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-list-ul me-2" style="color: #22c55e;"></i>Daftar Ruangan
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search" style="color: #22c55e;"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 bg-white" id="searchInput"
                            placeholder="Cari ruangan, gedung..." style="border-left: 0;">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableRuangan">
                    <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                        <tr>
                            <th class="text-center" style="width: 50px; padding: 15px 10px;">
                                <i class="bi bi-hash"></i>
                            </th>
                            <th style="width: 20%; padding: 15px;">
                                <i class="bi bi-door-closed me-1"></i>Nama Ruangan
                            </th>
                            <th style="width: 12%; padding: 15px;">
                                <i class="bi bi-building me-1"></i>Gedung
                            </th>
                            <th class="text-center" style="width: 12%; padding: 15px;">
                                <i class="bi bi-people me-1"></i>Kapasitas
                            </th>
                            <th class="text-center" style="width: 10%; padding: 15px;">
                                <i class="bi bi-image me-1"></i>Foto
                            </th>
                            <th class="text-center" style="width: 280px; padding: 15px;">
                                <i class="bi bi-gear me-1"></i>Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ruangans)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <p class="mb-0">Belum ada data ruangan</p>
                                        <small>Tambahkan ruangan pertama Anda</small>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ruangans as $i => $ruangan): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-secondary bg-opacity-75" style="font-size: 0.9rem; padding: 0.4rem 0.7rem;">
                                            <?= $i + 1 ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 1rem;"><?= htmlspecialchars($ruangan['nama_ruangan']) ?></div>
                                        <?php if (!empty($ruangan['deskripsi'])): ?>
                                            <small class="text-muted" style="font-size: 0.85rem;">
                                                <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars(substr($ruangan['deskripsi'], 0, 50)) ?><?= strlen($ruangan['deskripsi']) > 50 ? '...' : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-building-fill me-1"></i><?= htmlspecialchars($ruangan['gedung'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-people-fill me-1"></i><?= $ruangan['kapasitas'] ?? '0' ?> orang
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($ruangan['foto']): ?>
                                            <img src="../uploads/ruangan/<?= htmlspecialchars($ruangan['foto']) ?>"
                                                alt="<?= htmlspecialchars($ruangan['nama_ruangan']) ?>"
                                                class="rounded shadow-sm img-thumbnail"
                                                style="width: 80px; height: 55px; object-fit: cover; cursor: pointer;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalViewImage"
                                                onclick="viewImage('../uploads/ruangan/<?= htmlspecialchars($ruangan['foto']) ?>', '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>')">
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                <i class="bi bi-image"></i> No Image
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-info btn-sm text-white px-2"
                                                style="min-width: 65px; font-size: 0.8rem;"
                                                onclick="viewDetail(<?= $ruangan['id'] ?>, '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>', '<?= htmlspecialchars($ruangan['gedung'] ?? '') ?>', <?= $ruangan['kapasitas'] ?>, '<?= htmlspecialchars($ruangan['deskripsi'] ?? '') ?>', '<?= htmlspecialchars($ruangan['foto'] ?? '') ?>')">
                                                <i class="bi bi-eye-fill me-1"></i>Detail
                                            </button>
                                            <button class="btn btn-warning btn-sm text-white px-2"
                                                style="min-width: 60px; font-size: 0.8rem;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditRuangan"
                                                onclick="editRuangan(<?= $ruangan['id'] ?>, '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>', '<?= htmlspecialchars($ruangan['gedung'] ?? '') ?>', <?= $ruangan['kapasitas'] ?>, '<?= htmlspecialchars($ruangan['deskripsi'] ?? '') ?>')">
                                                <i class="bi bi-pencil-fill me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm px-2"
                                                style="min-width: 65px; font-size: 0.8rem;"
                                                onclick="deleteRuangan(<?= $ruangan['id'] ?>, '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>')">
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
                    <span class="badge ms-1" style="background: linear-gradient(135deg, #22c55e, #16a34a);"><?= count($ruangans) ?></span> ruangan terdaftar
                </small>
                <small class="text-muted">
                    <i class="bi bi-calendar-check me-1"></i><?= date('d F Y') ?>
                </small>
            </div>
        </div>
    </div>
</div>

</main><!-- end admin-main -->
</div><!-- end wrap -->

<!-- Modal Tambah Ruangan -->
<div class="modal fade" id="modalAddRuangan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle-fill me-2"></i>Tambah Ruangan Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_ruangan.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-door-closed me-1" style="color: #22c55e;"></i>Nama Ruangan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama_ruangan" required
                                placeholder="Contoh: Ruang 301">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-building me-1" style="color: #22c55e;"></i>Gedung
                            </label>
                            <input type="text" class="form-control" name="gedung"
                                placeholder="Contoh: Gedung A">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-people me-1" style="color: #22c55e;"></i>Kapasitas
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="kapasitas"
                                placeholder="Jumlah orang" min="1">
                            <span class="input-group-text">
                                <i class="bi bi-person-fill"></i> orang
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-card-text me-1" style="color: #22c55e;"></i>Deskripsi
                        </label>
                        <textarea class="form-control" name="deskripsi" rows="3"
                            placeholder="Keterangan ruangan (opsional)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-image me-1" style="color: #22c55e;"></i>Foto Ruangan
                        </label>
                        <input type="file" class="form-control" name="foto" accept="image/*"
                            id="addFotoInput" onchange="previewAddImage(event)">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Format: JPG, PNG, GIF (Max 2MB)
                        </small>
                        <div class="mt-3" id="addImagePreviewContainer" style="display: none;">
                            <img id="addImagePreview" src="" alt="Preview"
                                class="img-thumbnail rounded" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn text-white" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-save me-1"></i>Simpan Ruangan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Ruangan -->
<div class="modal fade" id="modalEditRuangan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Ruangan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_ruangan.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editRuanganId">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-door-closed me-1" style="color: #f59e0b;"></i>Nama Ruangan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama_ruangan"
                                id="editNamaRuangan" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-building me-1" style="color: #f59e0b;"></i>Gedung
                            </label>
                            <input type="text" class="form-control" name="gedung" id="editGedung">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-people me-1" style="color: #f59e0b;"></i>Kapasitas
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="kapasitas"
                                id="editKapasitas" min="1">
                            <span class="input-group-text">
                                <i class="bi bi-person-fill"></i> orang
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-card-text me-1" style="color: #f59e0b;"></i>Deskripsi
                        </label>
                        <textarea class="form-control" name="deskripsi" id="editDeskripsi" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-image me-1" style="color: #f59e0b;"></i>Foto Ruangan
                        </label>
                        <input type="file" class="form-control" name="foto" accept="image/*"
                            id="editFotoFile" onchange="previewEditImage(event)">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Kosongkan jika tidak ingin mengubah foto
                        </small>
                        <div class="mt-3" id="editImagePreviewContainer" style="display: none;">
                            <img id="editFotoPreview" src="" alt="Foto Preview"
                                class="img-thumbnail rounded" style="max-height: 200px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn text-white" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-check-circle me-1"></i>Update Ruangan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal View Detail -->
<div class="modal fade" id="modalViewDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%); border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-eye-fill me-2"></i>Detail Ruangan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">
                                <i class="bi bi-door-closed me-1"></i>Nama Ruangan
                            </label>
                            <h5 class="fw-bold" id="detailNamaRuangan">-</h5>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">
                                <i class="bi bi-building me-1"></i>Gedung
                            </label>
                            <h6 id="detailGedung">-</h6>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">
                                <i class="bi bi-people me-1"></i>Kapasitas
                            </label>
                            <h6 id="detailKapasitas">-</h6>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-2">
                            <i class="bi bi-image me-1"></i>Foto Ruangan
                        </label>
                        <div id="detailFotoContainer">
                            <img id="detailFoto" src="" alt="Foto Ruangan"
                                class="img-fluid rounded shadow-sm" style="max-height: 250px; width: 100%; object-fit: cover;">
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="text-muted small mb-1">
                        <i class="bi bi-card-text me-1"></i>Deskripsi
                    </label>
                    <p class="border p-3 rounded bg-light" id="detailDeskripsi">-</p>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px; padding: 10px 24px;">
                    <i class="bi bi-x-circle me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal View Image -->
<div class="modal fade" id="modalViewImage" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg bg-dark" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold" id="viewImageTitle">
                    <i class="bi bi-images me-2"></i>Foto Ruangan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-dark text-center">
                <img id="viewImageSrc" src="" alt="Foto Ruangan" class="img-fluid" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize Bootstrap tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#tableRuangan tbody tr');

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Preview image on add modal
    function previewAddImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('addImagePreview').src = e.target.result;
                document.getElementById('addImagePreviewContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    // Preview image on edit modal
    function previewEditImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editFotoPreview').src = e.target.result;
                document.getElementById('editImagePreviewContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    // Edit ruangan function
    function editRuangan(id, nama, gedung, kapasitas, deskripsi) {
        document.getElementById('editRuanganId').value = id;
        document.getElementById('editNamaRuangan').value = nama;
        document.getElementById('editGedung').value = gedung;
        document.getElementById('editKapasitas').value = kapasitas;
        document.getElementById('editDeskripsi').value = deskripsi;

        // Hide preview when opening modal
        document.getElementById('editImagePreviewContainer').style.display = 'none';
        document.getElementById('editFotoFile').value = '';
    }

    // View detail function
    function viewDetail(id, nama, gedung, kapasitas, deskripsi, foto) {
        document.getElementById('detailNamaRuangan').textContent = nama;
        document.getElementById('detailGedung').textContent = gedung || '-';
        document.getElementById('detailKapasitas').textContent = kapasitas ? kapasitas + ' orang' : '-';
        document.getElementById('detailDeskripsi').textContent = deskripsi || 'Tidak ada deskripsi';

        if (foto) {
            document.getElementById('detailFoto').src = '../uploads/ruangan/' + foto;
            document.getElementById('detailFotoContainer').style.display = 'block';
        } else {
            document.getElementById('detailFotoContainer').innerHTML = '<div class="alert alert-secondary text-center"><i class="bi bi-image"></i> Tidak ada foto</div>';
        }

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('modalViewDetail'));
        modal.show();
    }

    // View image in modal
    function viewImage(src, title) {
        document.getElementById('viewImageSrc').src = src;
        document.getElementById('viewImageTitle').textContent = title;
    }

    // Delete ruangan with better confirmation
    function deleteRuangan(id, nama) {
        if (confirm('Apakah Anda yakin ingin menghapus ruangan "' + nama + '"?\n\nTindakan ini tidak dapat dibatalkan!')) {
            window.location.href = 'proses_ruangan.php?action=delete&id=' + id;
        }
    }

    // Reset add form when modal is closed
    document.getElementById('modalAddRuangan').addEventListener('hidden.bs.modal', function() {
        this.querySelector('form').reset();
        document.getElementById('addImagePreviewContainer').style.display = 'none';
    });

    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>

<style>
    /* Kelola Header Styling */
    .kelola-header {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        border-radius: 16px;
        padding: 28px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .kelola-header h1 {
        margin: 0;
        color: #ffffff;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .btn-tambah {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 12px 32px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    .btn-tambah:hover {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
    }

    .btn-tambah:active {
        transform: translateY(0);
    }

    /* Custom styles for enhanced appearance */
    .card {
        transition: all 0.3s ease;
    }

    /* Override Bootstrap primary color to match theme */
    .text-primary {
        color: #22c55e !important;
    }

    .bg-primary {
        background-color: #22c55e !important;
    }

    .btn-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border: none;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #16a34a, #15803d);
    }

    .badge.bg-primary {
        background: linear-gradient(135deg, #22c55e, #16a34a) !important;
    }

    .table tbody tr {
        transition: all 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.001);
    }

    .img-thumbnail:hover {
        transform: scale(1.08);
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .modal-content {
        border-radius: 15px;
    }

    .badge {
        font-weight: 500;
        font-size: 0.85rem;
    }

    /* Action Buttons Styling */
    .btn-sm {
        padding: 0.35rem 0.6rem;
        font-size: 0.8rem;
        border-radius: 6px;
        font-weight: 600;
        transition: all 0.3s ease;
        white-space: nowrap;
        border: none;
    }

    .btn-sm i {
        font-size: 0.85rem;
    }

    .btn-info {
        background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%);
    }

    .btn-info:hover {
        background: linear-gradient(135deg, #0aa2c0 0%, #088ba8 100%);
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(13, 202, 240, 0.4);
    }

    .btn-warning {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(255, 193, 7, 0.4);
    }

    .btn-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
        transform: translateY(-2px);
        box-shadow: 0 5px 12px rgba(220, 53, 69, 0.4);
    }

    .d-flex.gap-1 {
        gap: 0.25rem !important;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #22c55e;
        box-shadow: 0 0 0 0.25rem rgba(34, 197, 94, 0.15);
    }

    .input-group-text {
        background-color: #ffffff;
        border-color: #dee2e6;
    }

    .card-header {
        border-bottom: 2px solid #e9ecef;
    }

    .card-footer {
        border-top: 2px solid #e9ecef;
        background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;
    }

    /* Animation for alerts */
    .alert {
        animation: slideInDown 0.5s ease;
        border-radius: 10px;
        border: none;
    }

    @keyframes slideInDown {
        from {
            transform: translateY(-20px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    /* Table header styling */
    thead th {
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }

    /* Table body styling */
    tbody td {
        vertical-align: middle;
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #f0f0f0;
    }

    tbody td:first-child {
        font-weight: 600;
        color: #6c757d;
    }

    /* Empty state styling */
    tbody .text-muted i {
        opacity: 0.5;
    }

    /* Gradient header for main card */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
    }

    /* Button hover effects for header */
    .btn-light:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15) !important;
        background-color: #ffffff !important;
    }

    /* Table striping alternative */
    tbody tr:nth-child(even) {
        background-color: #fafbfc;
    }

    /* Badge styling improvements */
    .badge {
        transition: all 0.3s ease;
    }

    .badge:hover {
        transform: scale(1.05);
    }

    /* Form control enhancements */
    .form-control,
    .form-select {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 0.6rem 0.75rem;
        transition: all 0.3s ease;
    }

    .form-control:hover {
        border-color: #adb5bd;
    }

    /* Modal animations */
    .modal.fade .modal-dialog {
        transition: transform 0.3s ease-out;
        transform: scale(0.8);
    }

    .modal.show .modal-dialog {
        transform: scale(1);
    }

    /* Button hover effects */
    .btn {
        transition: all 0.3s ease;
    }

    .btn:active {
        transform: scale(0.95);
    }

    /* Image thumbnail styling */
    .img-thumbnail {
        border: 2px solid #dee2e6;
        transition: all 0.3s ease;
    }

    /* Card shadow on hover */
    .card:hover {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    }

    /* Scrollbar styling */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #16a34a, #22c55e);
    }

    /* Responsive Design for Kelola Header */
    @media (max-width: 768px) {
        .kelola-header {
            flex-direction: column;
            gap: 20px;
            padding: 24px 28px;
            text-align: center;
        }

        .kelola-header h1 {
            font-size: 26px;
        }

        .btn-tambah {
            width: 100%;
            padding: 14px 32px;
        }
    }

    @media (max-width: 480px) {
        .kelola-header {
            padding: 20px 20px;
        }

        .kelola-header h1 {
            font-size: 22px;
        }

        .btn-tambah {
            font-size: 14px;
            padding: 12px 24px;
        }
    }
</style>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>