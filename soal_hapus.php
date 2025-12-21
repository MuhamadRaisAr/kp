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

// Validasi Metode: GET (karena dari link)
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: ujian.php");
    exit();
}

// 1. Ambil ID Soal dari URL
$id_soal = isset($_GET['id_soal']) ? (int)$_GET['id_soal'] : 0;
if ($id_soal <= 0) {
    header("Location: ujian.php?error=Soal tidak valid");
    exit();
}

// 2. Validasi Kepemilikan Soal & Status 'Draft'
// Ambil juga id_ujian untuk redirect
$query_cek = "SELECT u.id_ujian, u.status_ujian 
              FROM ujian_soal us
              JOIN ujian u ON us.id_ujian = u.id_ujian
              JOIN mengajar m ON u.id_mengajar = m.id_mengajar
              WHERE us.id_soal = ? AND m.id_guru = ?";
$stmt_cek = mysqli_prepare($koneksi, $query_cek);
mysqli_stmt_bind_param($stmt_cek, "ii", $id_soal, $id_guru_login);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) == 0) {
    header("Location: ujian.php?error=Akses ditolak atau soal tidak ditemukan");
    exit();
}
$data = mysqli_fetch_assoc($result_cek);
$id_ujian = $data['id_ujian']; // Simpan untuk redirect

if ($data['status_ujian'] !== 'Draft') {
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=gagal_hapus&msg=" . urlencode("Tidak bisa menghapus soal, ujian sudah di-publish."));
    exit();
}

// 3. Hapus Soal dari Database
$query_delete = "DELETE FROM ujian_soal WHERE id_soal = ?";
$stmt_delete = mysqli_prepare($koneksi, $query_delete);
mysqli_stmt_bind_param($stmt_delete, "i", $id_soal);

if (mysqli_stmt_execute($stmt_delete)) {
    // Berhasil
    // TODO: Idealnya, kita harus update nomor_soal yang lain setelah ini, tapi kita skip dulu
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=sukses_hapus_soal");
} else {
    // Gagal
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=gagal_hapus&msg=" . urlencode("Gagal menghapus soal."));
}
mysqli_stmt_close($stmt_cek);
mysqli_stmt_close($stmt_delete);
mysqli_close($koneksi);
exit();
?>