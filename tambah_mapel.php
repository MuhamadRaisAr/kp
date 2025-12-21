<?php
require_once 'includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once 'includes/header.php';
$judul_halaman = "Tambah Mata Pelajaran";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Mata Pelajaran</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="mapel.php">Data Mapel</a></li>
        <li class="breadcrumb-item active">Tambah Mapel</li>
    </ol>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus-circle me-1"></i>Formulir Tambah Mapel</div>
        <div class="card-body">
            <form action="proses_tambah_mapel.php" method="POST">
                <div class="mb-3">
                    <label for="kode_mapel" class="form-label">Kode Mapel</label>
                    <input type="text" class="form-control" id="kode_mapel" name="kode_mapel" required>
                </div>
                <div class="mb-3">
                    <label for="nama_mapel" class="form-label">Nama Mata Pelajaran</label>
                    <input type="text" class="form-control" id="nama_mapel" name="nama_mapel" required>
                </div>
                <div class="mb-3">
                    <label for="jenis" class="form-label">Jenis Mapel</label>
                    <select class="form-select" id="jenis" name="jenis" required>
                        <option value="Normatif">Normatif</option>
                        <option value="Adaptif">Adaptif</option>
                        <option value="Produktif">Produktif</option>
                        <option value="Muatan Lokal">Muatan Lokal</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="mapel.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>