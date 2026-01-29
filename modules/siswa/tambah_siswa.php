<?php
// Panggil file auth_check.php
require_once '../../includes/auth_check.php';
// Panggil file koneksi
require_once '../../includes/koneksi.php';

// Atur judul halaman
$judul_halaman = "Tambah Data Siswa";
// Panggil header
require_once '../../includes/header.php';

// Query untuk mengambil data kelas untuk dropdown
$sql_kelas = "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC";
$result_kelas = mysqli_query($koneksi, $sql_kelas);
?>

<div class="container-fluid">
    <h1 class="mt-4">Tambah Data Siswa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="siswa.php">Data Siswa</a></li>
        <li class="breadcrumb-item active">Tambah Siswa</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-circle me-1"></i>
            Formulir Tambah Siswa
        </div>
        <div class="card-body">
            
            <form action="proses_tambah_siswa.php" method="POST">
                <div class="mb-3">
                    <label for="nis" class="form-label">NIS</label>
                    <input type="text" class="form-control" id="nis" name="nis" required>
                </div>
                <div class="mb-3">
                    <label for="nisn" class="form-label">NISN</label>
                    <input type="text" class="form-control" id="nisn" name="nisn" required>
                </div>
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="id_kelas" class="form-label">Kelas</label>
                    <select class="form-select" id="id_kelas" name="id_kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php
                        // Tampilkan daftar kelas dari database
                        if (mysqli_num_rows($result_kelas) > 0) {
                            while ($row_kelas = mysqli_fetch_assoc($result_kelas)) {
                                echo "<option value='" . $row_kelas['id_kelas'] . "'>" . htmlspecialchars($row_kelas['nama_kelas']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="siswa.php" class="btn btn-secondary">Batal</a>
            </form>

        </div>
    </div>
</div>

<?php
// Panggil footer
require_once '../../includes/footer.php';
?>
