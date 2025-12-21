<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

// Atur judul halaman dan panggil header
$judul_halaman = "Tambah Data Kelas";
require_once 'includes/header.php';

// Query untuk mengambil data jurusan
$sql_jurusan = "SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan ASC";
$result_jurusan = mysqli_query($koneksi, $sql_jurusan);

// Query untuk mengambil data guru
$sql_guru = "SELECT id_guru, nama_lengkap FROM guru ORDER BY nama_lengkap ASC";
$result_guru = mysqli_query($koneksi, $sql_guru);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Data Kelas</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="kelas.php">Data Kelas</a></li>
        <li class="breadcrumb-item active">Tambah Kelas</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle me-1"></i>
            Formulir Tambah Kelas
        </div>
        <div class="card-body">
            
            <form action="proses_tambah_kelas.php" method="POST">
                <div class="mb-3">
                    <label for="nama_kelas" class="form-label">Nama Kelas</label>
                    <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" placeholder="Contoh: XI TKJ 1" required>
                </div>
                <div class="mb-3">
                    <label for="tingkat" class="form-label">Tingkat</label>
                    <select class="form-select" id="tingkat" name="tingkat" required>
                        <option value="10">10</option>
                        <option value="11">11</option>
                        <option value="12">12</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_jurusan" class="form-label">Jurusan</label>
                    <select class="form-select" id="id_jurusan" name="id_jurusan" required>
                        <option value="">-- Pilih Jurusan --</option>
                        <?php
                        // Tampilkan daftar jurusan dari database
                        if (mysqli_num_rows($result_jurusan) > 0) {
                            while ($row = mysqli_fetch_assoc($result_jurusan)) {
                                echo "<option value='" . $row['id_jurusan'] . "'>" . htmlspecialchars($row['nama_jurusan']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_guru_wali_kelas" class="form-label">Wali Kelas</label>
                    <select class="form-select" id="id_guru_wali_kelas" name="id_guru_wali_kelas" required>
                        <option value="">-- Pilih Wali Kelas --</option>
                        <?php
                        // Tampilkan daftar guru dari database
                        if (mysqli_num_rows($result_guru) > 0) {
                            while ($row = mysqli_fetch_assoc($result_guru)) {
                                echo "<option value='" . $row['id_guru'] . "'>" . htmlspecialchars($row['nama_lengkap']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="kelas.php" class="btn btn-secondary">Batal</a>
            </form>

        </div>
    </div>
</div>

<?php
// Panggil footer
require_once 'includes/footer.php';
?>