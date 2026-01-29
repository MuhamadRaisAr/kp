<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php'; // Memastikan login & set role
require_once 'includes/header.php'; // Kita butuh header untuk layout
require_once 'includes/koneksi.php';

// Judul halaman diset di header, jadi kita set di sini
$judul_halaman = "Mengerjakan Ujian";

// --- Validasi Akses dan Status ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa' || !isset($_SESSION['id_siswa']) || empty($_SESSION['id_siswa'])) {
    // Redirect jika bukan siswa
    header("Location: login.php?error=Akses ditolak");
    exit();
}
$id_siswa_login = (int)$_SESSION['id_siswa'];

$id_ujian = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_ujian <= 0) {
    header("Location: ujian_saya.php?error=Ujian tidak valid");
    exit();
}

// Waktu server saat ini
$waktu_sekarang_dt = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$waktu_sekarang_ts = $waktu_sekarang_dt->getTimestamp(); // Unix timestamp

// --- Ambil Data Ujian dan Status Pengerjaan Siswa ---
$query_data = "SELECT 
                   u.id_ujian, u.judul_ujian, u.durasi_menit, u.waktu_mulai, u.waktu_selesai,
                   uh.id_hasil, uh.waktu_mulai_mengerjakan, uh.status_pengerjaan
               FROM ujian u
               JOIN mengajar m ON u.id_mengajar = m.id_mengajar
               JOIN siswa s ON m.id_kelas = s.id_kelas AND s.id_siswa = ? 
               JOIN ujian_hasil uh ON u.id_ujian = uh.id_ujian AND uh.id_siswa = ?
               WHERE u.id_ujian = ? 
                 AND u.status_ujian = 'Published'";

$stmt_data = mysqli_prepare($koneksi, $query_data);
mysqli_stmt_bind_param($stmt_data, "iii", $id_siswa_login, $id_siswa_login, $id_ujian);
mysqli_stmt_execute($stmt_data);
$result_data = mysqli_stmt_get_result($stmt_data);

if (mysqli_num_rows($result_data) == 0) {
    // Mungkin ujian tidak ada, tidak published, beda kelas, atau siswa belum pernah 'mulai'
    header("Location: ujian_saya.php?error=Ujian tidak ditemukan atau Anda belum memulai ujian ini.");
    exit();
}
$ujian_info = mysqli_fetch_assoc($result_data);
mysqli_stmt_close($stmt_data);

// Validasi Status Pengerjaan
if ($ujian_info['status_pengerjaan'] == 'Belum') {
     header("Location: ujian_konfirmasi.php?id=" . $id_ujian . "&error=Harap konfirmasi untuk memulai ujian.");
     exit();
}
if ($ujian_info['status_pengerjaan'] == 'Selesai' || $ujian_info['status_pengerjaan'] == 'Dinilai') {
     header("Location: ujian_saya.php?info=Ujian sudah Anda selesaikan.");
     exit();
}
// Jika status 'Mengerjakan', lanjut

// --- Hitung Sisa Waktu ---
$waktu_mulai_mengerjakan_dt = new DateTime($ujian_info['waktu_mulai_mengerjakan'], new DateTimeZone('Asia/Jakarta'));
$waktu_mulai_mengerjakan_ts = $waktu_mulai_mengerjakan_dt->getTimestamp();
$durasi_detik = $ujian_info['durasi_menit'] * 60;
$waktu_akhir_pengerjaan_ts = $waktu_mulai_mengerjakan_ts + $durasi_detik;

// Bandingkan juga dengan waktu selesai ujian global
$waktu_selesai_ujian_dt = new DateTime($ujian_info['waktu_selesai'], new DateTimeZone('Asia/Jakarta'));
$waktu_selesai_ujian_ts = $waktu_selesai_ujian_dt->getTimestamp();

// Waktu akhir pengerjaan siswa tidak boleh melebihi waktu selesai ujian global
$batas_akhir_real_ts = min($waktu_akhir_pengerjaan_ts, $waktu_selesai_ujian_ts);

$sisa_detik = $batas_akhir_real_ts - $waktu_sekarang_ts;

// Jika waktu sudah habis saat halaman load
if ($sisa_detik <= 0) {
    // Idealnya, langsung proses submit otomatis di sini via PHP
    // Tapi untuk sementara, kita redirect ke proses selesai
    header("Location: proses_ujian_selesai.php?id_ujian=" . $id_ujian . "&timeout=1");
    exit();
}

// --- Ambil Soal dan Jawaban Siswa (jika ada) ---
$soal_list = [];
$jawaban_siswa = []; // Format: [id_soal => jawaban_siswa]

$query_soal_jawaban = "SELECT 
                           us.id_soal, us.nomor_soal, us.pertanyaan, us.opsi_a, us.opsi_b, us.opsi_c, us.opsi_d, us.opsi_e,
                           ujs.jawaban_siswa
                       FROM ujian_soal us
                       LEFT JOIN ujian_jawaban_siswa ujs ON us.id_soal = ujs.id_soal AND ujs.id_hasil = ?
                       WHERE us.id_ujian = ?
                       ORDER BY us.nomor_soal ASC";

$stmt_soal = mysqli_prepare($koneksi, $query_soal_jawaban);
mysqli_stmt_bind_param($stmt_soal, "ii", $ujian_info['id_hasil'], $id_ujian);
mysqli_stmt_execute($stmt_soal);
$result_soal = mysqli_stmt_get_result($stmt_soal);

while ($row = mysqli_fetch_assoc($result_soal)) {
    $soal_list[] = $row;
    if (!empty($row['jawaban_siswa'])) {
        $jawaban_siswa[$row['id_soal']] = $row['jawaban_siswa'];
    }
}
mysqli_stmt_close($stmt_soal);
mysqli_close($koneksi); // Tutup koneksi setelah semua data diambil

?>

<div class="container-fluid px-4">
    <div class="row mt-4 mb-3 align-items-center sticky-top bg-light py-2 border-bottom">
        <div class="col-md-8">
            <h1 class="h3"><?php echo htmlspecialchars($ujian_info['judul_ujian']); ?></h1>
        </div>
        <div class="col-md-4 text-md-end">
            <div id="timer" class="alert alert-info py-2 px-3 d-inline-block fw-bold fs-5" role="alert">
                Sisa Waktu: <span id="time">--:--:--</span>
            </div>
        </div>
    </div>

    <form id="form-ujian" action="proses_ujian_selesai.php" method="POST">
        <input type="hidden" name="id_ujian" value="<?php echo $id_ujian; ?>">
        <input type="hidden" name="id_hasil" value="<?php echo $ujian_info['id_hasil']; ?>">

        <?php foreach ($soal_list as $index => $soal): ?>
        <div class="card mb-3 shadow-sm">
            <div class="card-body">
                <p class="card-text"><strong><?php echo $soal['nomor_soal']; ?>. <?php echo nl2br(htmlspecialchars($soal['pertanyaan'])); ?></strong></p>
                
                <?php $opsi_tersedia = ['A', 'B', 'C', 'D', 'E']; ?>
                <?php foreach ($opsi_tersedia as $opsi_huruf): ?>
                    <?php 
                        $kolom_opsi = 'opsi_' . strtolower($opsi_huruf); 
                        $teks_opsi = $soal[$kolom_opsi];
                        // Hanya tampilkan opsi jika ada isinya
                        if (!empty($teks_opsi)):
                            $jawaban_tersimpan = isset($jawaban_siswa[$soal['id_soal']]) ? $jawaban_siswa[$soal['id_soal']] : null;
                            $checked = ($jawaban_tersimpan == $opsi_huruf) ? 'checked' : '';
                    ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" 
                               name="jawaban[<?php echo $soal['id_soal']; ?>]" 
                               id="opsi-<?php echo $soal['id_soal']; ?>-<?php echo $opsi_huruf; ?>" 
                               value="<?php echo $opsi_huruf; ?>" <?php echo $checked; ?>>
                        <label class="form-check-label" for="opsi-<?php echo $soal['id_soal']; ?>-<?php echo $opsi_huruf; ?>">
                            <?php echo htmlspecialchars($teks_opsi); ?>
                        </label>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="mt-4 mb-5 text-center">
            <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan ujian ini?');">
                <i class="fas fa-check-circle me-2"></i> Selesai Ujian
            </button>
        </div>
    </form>

</div>

<?php
// Panggil footer.php
require_once 'includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeDisplay = document.getElementById('time');
    const formUjian = document.getElementById('form-ujian');
    let sisaDetik = <?php echo $sisa_detik; ?>;

    function startTimer() {
        const interval = setInterval(() => {
            if (sisaDetik <= 0) {
                clearInterval(interval);
                timeDisplay.textContent = 'Waktu Habis!';
                timeDisplay.classList.remove('alert-info');
                timeDisplay.classList.add('alert-danger');
                // Auto submit form
                alert('Waktu habis! Jawaban Anda akan otomatis dikirim.');
                formUjian.submit(); 
            } else {
                sisaDetik--;
                const hours = Math.floor(sisaDetik / 3600);
                const minutes = Math.floor((sisaDetik % 3600) / 60);
                const seconds = sisaDetik % 60;
                
                // Format HH:MM:SS
                timeDisplay.textContent = 
                    String(hours).padStart(2, '0') + ':' + 
                    String(minutes).padStart(2, '0') + ':' + 
                    String(seconds).padStart(2, '0');
            }
        }, 1000); // Update setiap 1 detik
    }

    // Mulai timer saat halaman dimuat
    startTimer();
});
</script>