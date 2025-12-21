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

// 1. Ambil ID Ujian
$id_ujian = isset($_POST['id_ujian']) ? (int)$_POST['id_ujian'] : 0;

// 2. Validasi Kepemilikan Ujian
$query_cek = "SELECT u.id_ujian, (SELECT COUNT(*) FROM ujian_soal WHERE id_ujian = u.id_ujian) AS total_soal
              FROM ujian u
              JOIN mengajar m ON u.id_mengajar = m.id_mengajar
              WHERE u.id_ujian = ? AND m.id_guru = ? AND u.status_ujian = 'Draft'";
$stmt_cek = mysqli_prepare($koneksi, $query_cek);
mysqli_stmt_bind_param($stmt_cek, "ii", $id_ujian, $id_guru_login);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) == 0) {
    header("Location: ujian.php?error=Akses ditolak atau ujian sudah di-publish.");
    exit();
}
$data = mysqli_fetch_assoc($result_cek);

// 3. Validasi: Pastikan ada minimal 1 soal
if ($data['total_soal'] == 0) {
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=gagal_publish&msg=" . urlencode("Tidak bisa mem-publish ujian. Belum ada soal yang ditambahkan."));
    exit();
}

// 4. Update Status Ujian menjadi 'Published'
$query_update = "UPDATE ujian SET status_ujian = 'Published' WHERE id_ujian = ?";
$stmt_update = mysqli_prepare($koneksi, $query_update);
mysqli_stmt_bind_param($stmt_update, "i", $id_ujian);

if (mysqli_stmt_execute($stmt_update)) {
    // Berhasil
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=sukses_publish");
} else {
    // Gagal
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=gagal_publish&msg=" . urlencode("Gagal mengupdate status ujian."));
}
mysqli_stmt_close($stmt_cek);
mysqli_stmt_close($stmt_update);
mysqli_close($koneksi);
exit();
?>