<?php
require_once '../../includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once '../../includes/header.php';
$judul_halaman = "Tambah Tahun Ajaran";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Tahun Ajaran</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="tahun_ajaran.php">Tahun Ajaran</a></li>
        <li class="breadcrumb-item active">Tambah</li>
    </ol>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus-circle me-1"></i>Formulir Tambah Tahun Ajaran</div>
        <div class="card-body">
            <form action="proses_tambah_tahun_ajaran.php" method="POST">
                <div class="mb-3">
                    <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                    <input type="text" class="form-control" id="tahun_ajaran" name="tahun_ajaran" placeholder="Contoh: 2025/2026" required>
                </div>
                <div class="mb-3">
                    <label for="semester" class="form-label">Semester</label>
                    <select class="form-select" id="semester" name="semester" required>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>
                 <div class="mb-3">
                    <label for="status_aktif" class="form-label">Status</label>
                    <select class="form-select" id="status_aktif" name="status_aktif" required>
                        <option value="Tidak Aktif">Tidak Aktif</option>
                        <option value="Aktif">Aktif</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="tahun_ajaran.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
