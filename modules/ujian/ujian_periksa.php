<?php
// Panggil file-file yang dibutuhkan
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Periksa Ujian Siswa";

// Pastikan yang login adalah guru
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk Guru.</div></div>';
    require_once '../../includes/footer.php';
    exit();
}
$id_guru_login = (int)$_SESSION['id_guru'];

// 1. Ambil ID Hasil dari URL
$id_hasil = isset($_GET['id_hasil']) ? (int)$_GET['id_hasil'] : 0;
if ($id_hasil <= 0) {
    header("Location: ujian.php?error=Data hasil tidak valid");
    exit();
}

// 2. Ambil Data Hasil, Ujian, dan Siswa
$query_info = "SELECT 
                    uh.id_hasil, uh.status_pengerjaan, uh.nilai_akhir,
                    u.id_ujian, u.judul_ujian, u.jenis_ujian,
                    s.nama_lengkap AS nama_siswa,
                    k.nama_kelas,
                    mp.nama_mapel
               FROM ujian_hasil uh
               JOIN ujian u ON uh.id_ujian = u.id_ujian
               JOIN siswa s ON uh.id_siswa = s.id_siswa
               JOIN mengajar m ON u.id_mengajar = m.id_mengajar
               JOIN kelas k ON m.id_kelas = k.id_kelas
               JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
               WHERE uh.id_hasil = ? AND m.id_guru = ?";

$stmt_info = mysqli_prepare($koneksi, $query_info);
mysqli_stmt_bind_param($stmt_info, "ii", $id_hasil, $id_guru_login);
mysqli_stmt_execute($stmt_info);
$res_info = mysqli_stmt_get_result($stmt_info);
$info = mysqli_fetch_assoc($res_info);
mysqli_stmt_close($stmt_info);

if (!$info) {
    header("Location: ujian.php?error=Data tidak ditemukan atau akses ditolak");
    exit();
}

// Cek jenis ujian, harus Esai (atau campuran, tapi fitur ini fokus Esai)
if ($info['jenis_ujian'] != 'Esai') {
    echo '<div class="container-fluid px-4"><div class="alert alert-warning mt-4">Fitur ini khusus untuk ujian Esai.</div></div>';
    require_once '../../includes/footer.php';
    exit();
}

// 3. Ambil Soal dan Jawaban Siswa
$query_soal = "SELECT 
                    us.id_soal, us.nomor_soal, us.pertanyaan,
                    ujs.jawaban_siswa, ujs.nilai_esai
               FROM ujian_soal us
               LEFT JOIN ujian_jawaban_siswa ujs ON us.id_soal = ujs.id_soal AND ujs.id_hasil = ?
               WHERE us.id_ujian = ?
               ORDER BY us.nomor_soal ASC";

$stmt_soal = mysqli_prepare($koneksi, $query_soal);
mysqli_stmt_bind_param($stmt_soal, "ii", $id_hasil, $info['id_ujian']);
mysqli_stmt_execute($stmt_soal);
$res_soal = mysqli_stmt_get_result($stmt_soal);

$data_soal = [];
while ($row = mysqli_fetch_assoc($res_soal)) {
    $data_soal[] = $row;
}
mysqli_stmt_close($stmt_soal);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Periksa Jawaban Siswa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="ujian.php">Manajemen Ujian</a></li>
        <li class="breadcrumb-item"><a href="ujian_hasil.php?id=<?php echo $info['id_ujian']; ?>">Hasil Ujian</a></li>
        <li class="breadcrumb-item active">Periksa Jawaban</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-graduate me-1"></i>Informasi Siswa</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nama Siswa:</strong> <?php echo htmlspecialchars($info['nama_siswa']); ?></p>
                    <p><strong>Kelas:</strong> <?php echo htmlspecialchars($info['nama_kelas']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Ujian:</strong> <?php echo htmlspecialchars($info['judul_ujian']); ?> (<?php echo htmlspecialchars($info['nama_mapel']); ?>)</p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($info['status_pengerjaan']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <form action="proses_ujian_nilai.php" method="POST">
        <input type="hidden" name="id_hasil" value="<?php echo $id_hasil; ?>">
        <input type="hidden" name="id_ujian" value="<?php echo $info['id_ujian']; ?>">

        <?php foreach ($data_soal as $soal): ?>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Soal No. <?php echo $soal['nomor_soal']; ?></h5>
                <p class="card-text"><?php echo nl2br(htmlspecialchars($soal['pertanyaan'])); ?></p>
                
                <hr>
                
                <h6>Jawaban Siswa:</h6>
                <div class="p-3 bg-light border rounded mb-3">
                    <?php 
                    if (empty($soal['jawaban_siswa'])) {
                        echo "<em class='text-muted'>Siswa tidak menjawab.</em>";
                    } else {
                        echo nl2br(htmlspecialchars($soal['jawaban_siswa']));
                    }
                    ?>
                </div>

                <div class="mb-3 row">
                    <label for="nilai_<?php echo $soal['id_soal']; ?>" class="col-sm-2 col-form-label"><strong>Beri Nilai (0-100):</strong></label>
                    <div class="col-sm-3">
                        <input type="number" class="form-control" 
                               id="nilai_<?php echo $soal['id_soal']; ?>" 
                               name="nilai[<?php echo $soal['id_soal']; ?>]" 
                               value="<?php echo isset($soal['nilai_esai']) ? $soal['nilai_esai'] : '0'; ?>" 
                               min="0" max="100" required>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="card mb-4">
            <div class="card-body">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save me-2"></i>Simpan Penilaian</button>
                <a href="ujian_hasil.php?id=<?php echo $info['id_ujian']; ?>" class="btn btn-secondary btn-lg ms-2">Batal</a>
            </div>
        </div>
    </form>

</div>

<?php
require_once '../../includes/footer.php';
?>
