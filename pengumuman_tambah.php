<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Buat Pengumuman Baru";

// Pastikan yang login adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk Admin.</div></div>';
    require_once 'includes/footer.php';
    exit();
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Buat Pengumuman Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="pengumuman.php">Pengumuman</a></li>
        <li class="breadcrumb-item active">Buat Baru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-square me-1"></i>
            Formulir Pengumuman Baru
        </div>
        <div class="card-body">
            <form action="proses_pengumuman_tambah.php" method="POST">

                <div class="mb-3">
                    <label for="judul" class="form-label">Judul Pengumuman</label>
                    <input type="text" class="form-control" id="judul" name="judul" placeholder="Masukkan judul..." required>
                </div>

                <div class="mb-3">
                    <label for="isi" class="form-label">Isi Pengumuman</label>
                    <textarea class="form-control" id="isi" name="isi" rows="5" placeholder="Ketik isi pengumuman di sini..." required></textarea>
                </div>

                <div class="mb-3">
                    <label for="target_role" class="form-label">Tampilkan Untuk</label>
                    <select class="form-select" id="target_role" name="target_role" required>
                        <option value="semua" selected>Semua Pengguna</option>
                        <option value="guru">Hanya Guru</option>
                        <option value="siswa">Hanya Siswa</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Simpan & Publikasikan</button>
                <a href="pengumuman.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>