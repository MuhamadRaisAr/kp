<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php'; // Memastikan login & set role
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Konfirmasi Mulai Ujian";

// Pastikan yang login adalah siswa dan ID siswa ada
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa' || !isset($_SESSION['id_siswa']) || empty($_SESSION['id_siswa'])) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk Siswa.</div></div>';
    require_once 'includes/footer.php';
    exit();
}
$id_siswa_login = (int)$_SESSION['id_siswa'];

// 1. Ambil ID Ujian dari URL
$id_ujian = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_ujian <= 0) {
    header("Location: ujian_saya.php?error=Ujian tidak valid");
    exit();
}

// Waktu server saat ini
$waktu_sekarang_dt = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$waktu_sekarang_sql = $waktu_sekarang_dt->format('Y-m-d H:i:s');

// 2. Ambil detail ujian & validasi ketersediaan untuk siswa
$query_ujian = "SELECT 
                    u.id_ujian, u.judul_ujian, u.durasi_menit, u.waktu_mulai, u.waktu_selesai,
                    mp.nama_mapel, 
                    g.nama_lengkap AS nama_guru,
                    m.id_kelas,
                    uh.status_pengerjaan
                FROM ujian u
                JOIN mengajar m ON u.id_mengajar = m.id_mengajar
                JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                JOIN guru g ON m.id_guru = g.id_guru
                JOIN siswa s ON m.id_kelas = s.id_kelas AND s.id_siswa = ? 
                LEFT JOIN ujian_hasil uh ON u.id_ujian = uh.id_ujian AND uh.id_siswa = ?
                WHERE u.id_ujian = ? 
                  AND u.status_ujian = 'Published'";
                  
$stmt = mysqli_prepare($koneksi, $query_ujian);
mysqli_stmt_bind_param($stmt, "iii", $id_siswa_login, $id_siswa_login, $id_ujian);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    // Ujian tidak ditemukan, tidak published, atau tidak sesuai kelas siswa
    header("Location: ujian_saya.php?error=Ujian tidak ditemukan atau tidak tersedia untuk Anda.");
    exit();
}
$ujian_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// 3. Validasi Waktu Ujian & Status Pengerjaan
$waktu_mulai_ujian_dt = new DateTime($ujian_data['waktu_mulai'], new DateTimeZone('Asia/Jakarta'));
$waktu_selesai_ujian_dt = new DateTime($ujian_data['waktu_selesai'], new DateTimeZone('Asia/Jakarta'));
$status_pengerjaan = $ujian_data['status_pengerjaan'] ?? 'Belum';

if ($waktu_sekarang_dt < $waktu_mulai_ujian_dt) {
    header("Location: ujian_saya.php?error=Ujian belum dimulai.");
    exit();
}
if ($waktu_sekarang_dt > $waktu_selesai_ujian_dt && $status_pengerjaan != 'Mengerjakan') {
     // Boleh lanjut jika 'Mengerjakan' (mungkin telat submit)
    header("Location: ujian_saya.php?error=Waktu ujian sudah berakhir.");
    exit();
}
if ($status_pengerjaan == 'Selesai' || $status_pengerjaan == 'Dinilai') {
    header("Location: ujian_saya.php?info=Ujian sudah Anda selesaikan.");
    exit();
}

// Jika status 'Mengerjakan', langsung redirect ke halaman pengerjaan
if ($status_pengerjaan == 'Mengerjakan') {
    header("Location: ujian_mengerjakan.php?id=" . $id_ujian);
    exit();
}

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Konfirmasi Mulai Ujian</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="ujian_saya.php">Ujian</a></li>
        <li class="breadcrumb-item active">Konfirmasi Ujian</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-info-circle me-1"></i>
            Detail Ujian
        </div>
        <div class="card-body">
            <h3 class="card-title"><?php echo htmlspecialchars($ujian_data['judul_ujian']); ?></h3>
            <p><strong>Mata Pelajaran:</strong> <?php echo htmlspecialchars($ujian_data['nama_mapel']); ?></p>
            <p><strong>Guru:</strong> <?php echo htmlspecialchars($ujian_data['nama_guru']); ?></p>
            <p><strong>Durasi:</strong> <?php echo $ujian_data['durasi_menit']; ?> Menit</p>
            <p><strong>Waktu Tersedia:</strong> <?php echo $waktu_mulai_ujian_dt->format('d M Y, H:i'); ?> s/d <?php echo $waktu_selesai_ujian_dt->format('d M Y, H:i'); ?></p>
            
            <hr>
            
            <h4>Peraturan Ujian:</h4>
            <ul>
                <li>Pastikan koneksi internet Anda stabil.</li>
                <li>Waktu akan mulai berjalan setelah Anda menekan tombol "Mulai Kerjakan".</li>
                <li>Jangan me-refresh halaman selama mengerjakan ujian.</li>
                <li>Jika waktu habis, jawaban akan otomatis tersimpan.</li>
                <li>Pastikan Anda menekan tombol "Selesai Ujian" setelah selesai.</li>
            </ul>

            <div class="alert alert-warning">
                <strong>Perhatian!</strong> Waktu ujian akan dimulai segera setelah Anda menekan tombol di bawah ini. Pastikan Anda sudah siap.
            </div>

            <form action="proses_ujian_mulai.php" method="POST">
                <input type="hidden" name="id_ujian" value="<?php echo $id_ujian; ?>">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-play-circle me-2"></i> Mulai Kerjakan
                </button>
                <a href="ujian_saya.php" class="btn btn-secondary btn-lg">Batal</a>
            </form>

        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>