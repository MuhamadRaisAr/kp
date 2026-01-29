<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

date_default_timezone_set('Asia/Jakarta');
mysqli_query($koneksi, "SET time_zone = '+07:00'");

$judul_halaman = "Mengerjakan Ujian";

/* ======================================
   VALIDASI LOGIN
====================================== */
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'siswa' ||
    empty($_SESSION['id_siswa'])
) {
    header("Location: /sistem-penilaian/login.php");
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];
$id_ujian = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_ujian <= 0) {
    header("Location: ujian_saya.php");
    exit;
}

$now = time();

/* ======================================
   AMBIL DATA UJIAN + VALIDASI KELAS
====================================== */
$query = "
SELECT u.*, m.id_kelas
FROM ujian u
JOIN mengajar m ON u.id_mengajar = m.id_mengajar
JOIN siswa s ON m.id_kelas = s.id_kelas
WHERE u.id_ujian = ?
  AND s.id_siswa = ?
  AND u.status_ujian = 'Published'
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_ujian, $id_siswa);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: ujian_saya.php?error=Ujian tidak valid");
    exit;
}

$ujian = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

/* ======================================
   CEK / BUAT ujian_hasil
====================================== */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM ujian_hasil WHERE id_ujian = ? AND id_siswa = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $id_ujian, $id_siswa);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    // BELUM PERNAH MULAI → BUAT
    $stmt_insert = mysqli_prepare(
        $koneksi,
        "INSERT INTO ujian_hasil
         (id_ujian, id_siswa, status_pengerjaan, waktu_mulai_mengerjakan)
         VALUES (?, ?, 'Mengerjakan', NOW())"
    );
    mysqli_stmt_bind_param($stmt_insert, "ii", $id_ujian, $id_siswa);
    mysqli_stmt_execute($stmt_insert);
    mysqli_stmt_close($stmt_insert);

    $id_hasil = mysqli_insert_id($koneksi);
    $status_pengerjaan = 'Mengerjakan';
    $waktu_mulai_mengerjakan = date('Y-m-d H:i:s');

} else {
    $hasil = mysqli_fetch_assoc($result);
    $id_hasil = $hasil['id_hasil'];
    $status_pengerjaan = $hasil['status_pengerjaan'];
    $waktu_mulai_mengerjakan = $hasil['waktu_mulai_mengerjakan'];
}
mysqli_stmt_close($stmt);

/* ======================================
   VALIDASI STATUS
====================================== */
if ($status_pengerjaan === 'Selesai' || $status_pengerjaan === 'Dinilai') {
    header("Location: ujian_saya.php?info=Ujian sudah selesai");
    exit;
}

/* ======================================
   HITUNG SISA WAKTU
====================================== */
$mulai_ts = strtotime($waktu_mulai_mengerjakan);
$durasi_ts = $ujian['durasi_menit'] * 60;
$selesai_ts = strtotime($ujian['waktu_selesai']);

$batas_ts = min($mulai_ts + $durasi_ts, $selesai_ts);
$sisa_detik = $batas_ts - $now;

if ($sisa_detik <= 0) {
    header("Location: proses_ujian_selesai.php?id_ujian=$id_ujian&timeout=1");
    exit;
}

/* ======================================
   AMBIL SOAL & JAWABAN
====================================== */
$soal = [];
$jawaban = [];

$query = "
SELECT us.*, ujs.jawaban_siswa
FROM ujian_soal us
LEFT JOIN ujian_jawaban_siswa ujs
  ON us.id_soal = ujs.id_soal AND ujs.id_hasil = ?
WHERE us.id_ujian = ?
ORDER BY us.nomor_soal
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_hasil, $id_ujian);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $soal[] = $row;
    if (!empty($row['jawaban_siswa'])) {
        $jawaban[$row['id_soal']] = $row['jawaban_siswa'];
    }
}
mysqli_stmt_close($stmt);
?>

<!-- =================== HTML =================== -->

<div class="container-fluid px-4">
    <h2 class="mt-4"><?= htmlspecialchars($ujian['judul_ujian']) ?></h2>

    <div class="alert alert-info fw-bold">
        Sisa Waktu: <span id="time">--:--:--</span>
    </div>

    <form id="form-ujian" method="POST" action="proses_ujian_selesai.php">
        <input type="hidden" name="id_ujian" value="<?= $id_ujian ?>">
        <input type="hidden" name="id_hasil" value="<?= $id_hasil ?>">

        <?php foreach ($soal as $s): ?>
        <div class="card mb-3">
            <div class="card-body">
                <strong><?= $s['nomor_soal'] ?>. <?= nl2br(htmlspecialchars($s['pertanyaan'])) ?></strong>

                <?php foreach (['A','B','C','D','E'] as $o):
                    $kol = 'opsi_' . strtolower($o);
                    if (empty($s[$kol])) continue;
                ?>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="radio"
                        name="jawaban[<?= $s['id_soal'] ?>]"
                        value="<?= $o ?>"
                        <?= (isset($jawaban[$s['id_soal']]) && $jawaban[$s['id_soal']] === $o) ? 'checked' : '' ?>>
                    <label class="form-check-label"><?= htmlspecialchars($s[$kol]) ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="text-center mb-5">
            <button class="btn btn-success btn-lg"
                onclick="return confirm('Yakin ingin menyelesaikan ujian?')">
                Selesai Ujian
            </button>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>

<script>
let sisa = <?= $sisa_detik ?>;
const time = document.getElementById('time');
const form = document.getElementById('form-ujian');

setInterval(() => {
    if (sisa <= 0) {
        alert('Waktu habis! Jawaban dikirim.');
        form.submit();
    }
    sisa--;
    const h = String(Math.floor(sisa / 3600)).padStart(2,'0');
    const m = String(Math.floor((sisa % 3600)/60)).padStart(2,'0');
    const s = String(sisa % 60).padStart(2,'0');
    time.textContent = `${h}:${m}:${s}`;
}, 1000);
</script>
