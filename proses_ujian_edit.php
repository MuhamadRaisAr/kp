<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/koneksi.php';
require_once 'includes/auth_check.php';

// 1. Validasi Akses: Guru
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    header("Location: dashboard.php?error=Akses ditolak");
    exit();
}
$id_guru_login = (int)$_SESSION['id_guru'];

// 2. Validasi Metode: POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ujian.php");
    exit();
}

// 3. Ambil dan Validasi Data dari Form
$id_ujian = isset($_POST['id_ujian']) ? (int)$_POST['id_ujian'] : 0;
// $id_mengajar tidak perlu diambil lagi karena tidak boleh diubah
$judul_ujian = isset($_POST['judul_ujian']) ? trim($_POST['judul_ujian']) : '';
$durasi_menit = isset($_POST['durasi_menit']) ? (int)$_POST['durasi_menit'] : 0;
$waktu_mulai_str = isset($_POST['waktu_mulai']) ? trim($_POST['waktu_mulai']) : '';
$waktu_selesai_str = isset($_POST['waktu_selesai']) ? trim($_POST['waktu_selesai']) : '';

// Konversi waktu
$waktu_mulai = date('Y-m-d H:i:s', strtotime($waktu_mulai_str));
$waktu_selesai = date('Y-m-d H:i:s', strtotime($waktu_selesai_str));

// Validasi dasar
if ($id_ujian <= 0 || empty($judul_ujian) || $durasi_menit <= 0 || empty($waktu_mulai_str) || empty($waktu_selesai_str)) {
    header("Location: ujian_edit.php?id=" . $id_ujian . "&status=error&msg=" . urlencode("Semua field wajib diisi."));
    exit();
}
if (strtotime($waktu_selesai_str) <= strtotime($waktu_mulai_str)) {
    header("Location: ujian_edit.php?id=" . $id_ujian . "&status=error&msg=" . urlencode("Waktu selesai harus setelah waktu mulai."));
    exit();
}

// 4. Validasi Kepemilikan & Status Draft
// (PENTING: Pastikan guru ini adalah pemilik ujian DAN ujian masih draft sebelum update)
$query_cek = "SELECT u.id_ujian 
              FROM ujian u
              JOIN mengajar m ON u.id_mengajar = m.id_mengajar
              WHERE u.id_ujian = ? AND m.id_guru = ? AND u.status_ujian = 'Draft'";
$stmt_cek = mysqli_prepare($koneksi, $query_cek);
mysqli_stmt_bind_param($stmt_cek, "ii", $id_ujian, $id_guru_login);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) == 0) {
    header("Location: ujian.php?error=Akses ditolak atau ujian tidak bisa diedit lagi.");
    exit();
}
mysqli_stmt_close($stmt_cek); // Tutup statement cek

// 5. Proses Update ke Database
$query_update = "UPDATE ujian 
                 SET judul_ujian = ?, durasi_menit = ?, waktu_mulai = ?, waktu_selesai = ?
                 WHERE id_ujian = ?";
$stmt_update = mysqli_prepare($koneksi, $query_update);

if ($stmt_update) {
    mysqli_stmt_bind_param(
        $stmt_update, 
        "sisss", // s = string, i = integer
        $judul_ujian, 
        $durasi_menit, 
        $waktu_mulai, 
        $waktu_selesai, 
        $id_ujian
    );

    if (mysqli_stmt_execute($stmt_update)) {
        // Berhasil
        mysqli_stmt_close($stmt_update);
        mysqli_close($koneksi);
        header("Location: ujian_detail.php?id=" . $id_ujian . "&status=sukses_edit");
        exit();
    } else {
        // Gagal eksekusi query
        $error_msg = mysqli_stmt_error($stmt_update);
        mysqli_stmt_close($stmt_update);
        mysqli_close($koneksi);
        header("Location: ujian_edit.php?id=" . $id_ujian . "&status=error&msg=" . urlencode("Gagal menyimpan perubahan: " . $error_msg));
        exit();
    }
} else {
    // Gagal menyiapkan statement
    $error_msg = mysqli_error($koneksi);
    mysqli_close($koneksi);
    header("Location: ujian_edit.php?id=" . $id_ujian . "&status=error&msg=" . urlencode("Gagal menyiapkan statement update: " . $error_msg));
    exit();
}
?>