<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
// Hanya admin yang bisa mengakses
if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak. Halaman ini hanya untuk Admin.");
}
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

// Atur judul halaman
$judul_halaman = "Tambah Data Jurusan";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Data Jurusan</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="jurusan.php">Data Jurusan</a></li>
        <li class="breadcrumb-item active">Tambah Jurusan</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle me-1"></i>
            Formulir Tambah Jurusan
        </div>
        <div class="card-body">
            
            <form action="proses_tambah_jurusan.php" method="POST">
                <div class="mb-3">
                    <label for="kode_jurusan" class="form-label">Kode Jurusan</label>
                    <input type="text" class="form-control" id="kode_jurusan" name="kode_jurusan" placeholder="Contoh: TKJ, AKL, OTKP" required>
                </div>
                <div class="mb-3">
                    <label for="nama_jurusan" class="form-label">Nama Jurusan</label>
                    <input type="text" class="form-control" id="nama_jurusan" name="nama_jurusan" placeholder="Contoh: Teknik Komputer dan Jaringan" required>
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="jurusan.php" class="btn btn-secondary">Batal</a>
            </form>

        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>