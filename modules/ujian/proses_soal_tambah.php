<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/koneksi.php';
require_once '../../includes/auth_check.php';

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
$id_ujian = isset($_POST['id_ujian']) ? (int)$_POST['id_ujian'] : 0;
$pertanyaan = isset($_POST['pertanyaan']) ? trim($_POST['pertanyaan']) : '';
$opsi_a = isset($_POST['opsi_a']) ? trim($_POST['opsi_a']) : '';
$opsi_b = isset($_POST['opsi_b']) ? trim($_POST['opsi_b']) : '';
$opsi_c = isset($_POST['opsi_c']) ? trim($_POST['opsi_c']) : '';
$opsi_d = isset($_POST['opsi_d']) ? trim($_POST['opsi_d']) : '';
$opsi_e = isset($_POST['opsi_e']) ? trim($_POST['opsi_e']) : null; // Boleh NULL
$kunci_jawaban = isset($_POST['kunci_jawaban']) ? trim($_POST['kunci_jawaban']) : '';

// 2. Validasi Data
if ($id_ujian <= 0 || empty($pertanyaan) || empty($opsi_a) || empty($opsi_b) || empty($opsi_c) || empty($opsi_d) || empty($kunci_jawaban)) {
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=gagal_soal&msg=" . urlencode("Data soal tidak lengkap."));
    exit();
}

// 3. Validasi Kepemilikan Ujian & Status 'Draft'
// (PENTING: Pastikan guru ini adalah pemilik ujian DAN ujian masih draft)
$query_cek = "SELECT u.status_ujian 
              FROM ujian u
              JOIN mengajar m ON u.id_mengajar = m.id_mengajar
              WHERE u.id_ujian = ? AND m.id_guru = ?";
$stmt_cek = mysqli_prepare($koneksi, $query_cek);
mysqli_stmt_bind_param($stmt_cek, "ii", $id_ujian, $id_guru_login);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) == 0) {
    header("Location: ujian.php?error=Akses ditolak");
    exit();
}
$ujian_data = mysqli_fetch_assoc($result_cek);
if ($ujian_data['status_ujian'] !== 'Draft') {
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=gagal_soal&msg=" . urlencode("Tidak bisa menambah soal, ujian sudah di-publish."));
    exit();
}

// 4. Hitung Nomor Soal Baru
$q_next_nomor = mysqli_query($koneksi, "SELECT COUNT(id_soal) AS total_soal FROM ujian_soal WHERE id_ujian = $id_ujian");
$nomor_soal = mysqli_fetch_assoc($q_next_nomor)['total_soal'] + 1;

// 5. Simpan Soal ke Database
$query_insert = "INSERT INTO ujian_soal (id_ujian, nomor_soal, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, kunci_jawaban)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_insert = mysqli_prepare($koneksi, $query_insert);
mysqli_stmt_bind_param($stmt_insert, "iisssssss", 
    $id_ujian, $nomor_soal, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $opsi_e, $kunci_jawaban
);

if (mysqli_stmt_execute($stmt_insert)) {
    // Berhasil
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=sukses_soal");
} else {
    // Gagal
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=gagal_soal&msg=" . urlencode("Gagal menyimpan soal ke database."));
}
mysqli_stmt_close($stmt_insert);
mysqli_close($koneksi);
exit();
?>
