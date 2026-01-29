<?php
// Panggil file auth_check.php dan koneksi.php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Atur judul halaman dan panggil header
$judul_halaman = "Tambah Data Guru";
require_once '../../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Data Guru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="guru.php">Data Guru</a></li>
        <li class="breadcrumb-item active">Tambah Guru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle me-1"></i>
            Formulir Tambah Guru
        </div>
        <div class="card-body">
            
            <form action="proses_tambah_guru.php" method="POST">
                <div class="mb-3">
                    <label for="nip" class="form-label">NIP</label>
                    <input type="text" class="form-control" id="nip" name="nip" required>
                </div>
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="no_telepon" class="form-label">No. Telepon</label>
                    <input type="text" class="form-control" id="no_telepon" name="no_telepon">
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="guru.php" class="btn btn-secondary">Batal</a>
            </form>

        </div>
    </div>
</div>

<?php
// Panggil footer
require_once '../../includes/footer.php';
?>
