<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/koneksi.php';
require_once 'includes/auth_check.php';

// Validasi Akses: Guru
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    header("Location: dashboard.php?error=Akses ditolak");
    exit();
}
$id_guru_login = (int)$_SESSION['id_guru'];

// Validasi Metode: POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ujian.php");
    exit();
}

// 1. Ambil Data dari Form
$id_soal = isset($_POST['id_soal']) ? (int)$_POST['id_soal'] : 0;
$id_ujian = isset($_POST['id_ujian']) ? (int)$_POST['id_ujian'] : 0; // Untuk redirect
$pertanyaan = isset($_POST['pertanyaan']) ? trim($_POST['pertanyaan']) : '';
$opsi_a = isset($_POST['opsi_a']) ? trim($_POST['opsi_a']) : '';
$opsi_b = isset($_POST['opsi_b']) ? trim($_POST['opsi_b']) : '';
$opsi_c = isset($_POST['opsi_c']) ? trim($_POST['opsi_c']) : '';
$opsi_d = isset($_POST['opsi_d']) ? trim($_POST['opsi_d']) : '';
$opsi_e = isset($_POST['opsi_e']) ? trim($_POST['opsi_e']) : null; 
$kunci_jawaban = isset($_POST['kunci_jawaban']) ? trim($_POST['kunci_jawaban']) : '';

// 2. Validasi Data
if ($id_soal <= 0 || $id_ujian <= 0 || empty($pertanyaan) || empty($opsi_a) || empty($opsi_b) || empty($opsi_c) || empty($opsi_d) || empty($kunci_jawaban)) {
    header("Location: soal_edit.php?id_soal=" . $id_soal . "&status=gagal_edit&msg=" . urlencode("Data soal tidak lengkap."));
    exit();
}

// 3. Validasi Kepemilikan Soal (via Ujian & Guru) & Status 'Draft'
$query_cek = "SELECT u.status_ujian 
              FROM ujian_soal us
              JOIN ujian u ON us.id_ujian = u.id_ujian
              JOIN mengajar m ON u.id_mengajar = m.id_mengajar
              WHERE us.id_soal = ? AND m.id_guru = ?";
$stmt_cek = mysqli_prepare($koneksi, $query_cek);
mysqli_stmt_bind_param($stmt_cek, "ii", $id_soal, $id_guru_login);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) == 0) {
    header("Location: ujian.php?error=Akses ditolak");
    exit();
}
$ujian_data = mysqli_fetch_assoc($result_cek);
$ujian_data = mysqli_fetch_assoc($result_cek);
// CHECK REMOVED: if ($ujian_data['status_ujian'] !== 'Draft') { ... }

// 4. Update Soal di Database
$query_update = "UPDATE ujian_soal 
                 SET pertanyaan = ?, opsi_a = ?, opsi_b = ?, opsi_c = ?, opsi_d = ?, opsi_e = ?, kunci_jawaban = ?
                 WHERE id_soal = ?";
$stmt_update = mysqli_prepare($koneksi, $query_update);
mysqli_stmt_bind_param($stmt_update, "sssssssi", 
    $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, $kunci_jawaban, $id_soal
);

if (mysqli_stmt_execute($stmt_update)) {
    // Berhasil
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=sukses_edit_soal");
} else {
    // Gagal
    header("Location: soal_edit.php?id_soal=" . $id_soal . "&status=gagal_edit&msg=" . urlencode("Gagal menyimpan perubahan soal."));
}
mysqli_stmt_close($stmt_cek);
mysqli_stmt_close($stmt_update);
mysqli_close($koneksi);
exit();
?>