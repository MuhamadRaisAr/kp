<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Buat Ujian Baru";

// Pastikan yang login adalah guru
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk Guru.</div></div>';
    require_once 'includes/footer.php';
    exit();
}
$id_guru_login = (int)$_SESSION['id_guru'];
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Buat Ujian Baru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="ujian.php">Manajemen Ujian</a></li>
        <li class="breadcrumb-item active">Buat Ujian Baru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus-square me-1"></i>
            Formulir Ujian Baru
        </div>
        <div class="card-body">
            <form action="proses_ujian_tambah.php" method="POST">
                
                <div class="mb-3">
                    <label for="id_mengajar" class="form-label">Pilih Mapel / Kelas / Tahun Ajaran</label>
                    <select class="form-select" id="id_mengajar" name="id_mengajar" required>
                        <option value="">-- Pilih Penugasan Mengajar --</option>
                        <?php
                        // Ambil semua data mengajar guru ini
                        $query_mengajar = "SELECT 
                                               m.id_mengajar, 
                                               mp.nama_mapel, 
                                               k.nama_kelas, 
                                               ta.tahun_ajaran, ta.semester
                                           FROM mengajar m
                                           JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                           JOIN kelas k ON m.id_kelas = k.id_kelas
                                           JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran
                                           WHERE m.id_guru = ?
                                           ORDER BY ta.tahun_ajaran DESC, mp.nama_mapel ASC";
                        
                        $stmt = mysqli_prepare($koneksi, $query_mengajar);
                        mysqli_stmt_bind_param($stmt, "i", $id_guru_login);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<option value='{$row['id_mengajar']}'>" . 
                                 htmlspecialchars($row['nama_mapel'] . " - " . $row['nama_kelas'] . " (" . $row['tahun_ajaran'] . "/" . $row['semester'] . ")") . 
                                 "</option>";
                        }
                        mysqli_stmt_close($stmt);
                        ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="judul_ujian" class="form-label">Judul Ujian</label>
                    <input type="text" class="form-control" id="judul_ujian" name="judul_ujian" placeholder="Contoh: Ujian Tengah Semester Ganjil" required>
                </div>
                
                <div class="mb-3">
                    <label for="durasi_menit" class="form-label">Durasi (dalam Menit)</label>
                    <input type="number" class="form-control" id="durasi_menit" name="durasi_menit" value="60" min="1" required>
                </div>

                <div class="mb-3">
                    <label for="waktu_mulai" class="form-label">Waktu Mulai Ujian</label>
                    <input type="datetime-local" class="form-control" id="waktu_mulai" name="waktu_mulai" required>
                </div>
                
                <div class="mb-3">
                    <label for="waktu_selesai" class="form-label">Waktu Selesai Ujian</label>
                    <input type="datetime-local" class="form-control" id="waktu_selesai" name="waktu_selesai" required>
                </div>

                <button type="submit" class="btn btn-primary">Simpan & Lanjut Tambah Soal</button>
                <a href="ujian.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>