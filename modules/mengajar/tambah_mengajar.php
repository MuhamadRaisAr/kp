<?php
require_once '../../includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';
$judul_halaman = "Tambah Penugasan";

// Ambil semua data untuk dropdown
$result_guru = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama_lengkap");
$result_mapel = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran ORDER BY nama_mapel");
$result_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas");
$result_tahun = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran WHERE status_aktif = 'Aktif' ORDER BY tahun_ajaran DESC");
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah Penugasan Mengajar</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="mengajar.php">Penugasan Mengajar</a></li>
        <li class="breadcrumb-item active">Tambah</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus-circle me-1"></i>Formulir Penugasan</div>
        <div class="card-body">
            <form action="proses_tambah_mengajar.php" method="POST">
                <div class="mb-3">
                    <label for="id_guru" class="form-label">Guru</label>
                    <select class="form-select" name="id_guru" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php while ($row = mysqli_fetch_assoc($result_guru)) { echo "<option value='{$row['id_guru']}'>" . htmlspecialchars($row['nama_lengkap']) . "</option>"; } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_mapel" class="form-label">Mata Pelajaran</label>
                    <select class="form-select" name="id_mapel" required>
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        <?php while ($row = mysqli_fetch_assoc($result_mapel)) { echo "<option value='{$row['id_mapel']}'>" . htmlspecialchars($row['nama_mapel']) . "</option>"; } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_kelas" class="form-label">Kelas</label>
                    <select class="form-select" name="id_kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php while ($row = mysqli_fetch_assoc($result_kelas)) { echo "<option value='{$row['id_kelas']}'>" . htmlspecialchars($row['nama_kelas']) . "</option>"; } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_tahun_ajaran" class="form-label">Tahun Ajaran (Hanya yang Aktif)</label>
                    <select class="form-select" name="id_tahun_ajaran" required>
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        <?php while ($row = mysqli_fetch_assoc($result_tahun)) { echo "<option value='{$row['id_tahun_ajaran']}'>" . htmlspecialchars($row['tahun_ajaran']) . " - " . htmlspecialchars($row['semester']) . "</option>"; } ?>
                    </select>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="hari" class="form-label">Hari</label>
                        <select class="form-select" id="hari" name="hari" required>
                            <option value="">-- Pilih Hari --</option>
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="jam_mulai" class="form-label">Jam Mulai</label>
                        <input type="time" class="form-control" id="jam_mulai" name="jam_mulai" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="jam_selesai" class="form-label">Jam Selesai</label>
                        <input type="time" class="form-control" id="jam_selesai" name="jam_selesai" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="mengajar.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
