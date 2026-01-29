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

// Validasi Metode: GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header("Location: ujian.php");
    exit();
}

// 1. Ambil ID Ujian dari URL
$id_ujian = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_ujian <= 0) {
    header("Location: ujian.php?error=Ujian tidak valid");
    exit();
}

// Waktu saat ini
$waktu_sekarang = time();

// 2. Validasi Kepemilikan Ujian & Kondisi Hapus
// Ambil status dan waktu mulai ujian
$query_cek = "SELECT u.status_ujian, u.waktu_mulai 
              FROM ujian u
              JOIN mengajar m ON u.id_mengajar = m.id_mengajar
              WHERE u.id_ujian = ? AND m.id_guru = ?";
$stmt_cek = mysqli_prepare($koneksi, $query_cek);
mysqli_stmt_bind_param($stmt_cek, "ii", $id_ujian, $id_guru_login);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) == 0) {
    // Tidak ditemukan atau bukan milik guru
    header("Location: ujian.php?status=gagal_hapus&msg=" . urlencode("Akses ditolak atau ujian tidak ditemukan."));
    exit();
}
$data_ujian = mysqli_fetch_assoc($result_cek);
mysqli_stmt_close($stmt_cek); // Tutup statement cek

// ==========================================
// PERUBAHAN LOGIKA VALIDASI HAPUS
// ==========================================
// ==========================================
// VALIDASI HAPUS DILEWATI
// ==========================================
// $boleh_hapus = false;
// $waktu_mulai_ts = strtotime($data_ujian['waktu_mulai']);
// if ($data_ujian['status_ujian'] == 'Draft') {
//     $boleh_hapus = true;
// } elseif ($data_ujian['status_ujian'] == 'Published' && $waktu_sekarang < $waktu_mulai_ts) {
//     $boleh_hapus = true;
// }
// if (!$boleh_hapus) { ... }
// Kita izinkan guru menghapus kapan saja
// ==========================================
// ==========================================

// 3. Hapus Ujian dari Database (Cascade akan hapus soal dll)
$query_delete = "DELETE FROM ujian WHERE id_ujian = ?";
$stmt_delete = mysqli_prepare($koneksi, $query_delete);

if ($stmt_delete) {
    mysqli_stmt_bind_param($stmt_delete, "i", $id_ujian);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        mysqli_stmt_close($stmt_delete);
        mysqli_close($koneksi);
        header("Location: ujian.php?status=sukses_hapus");
        exit();
    } else {
        $error_msg = mysqli_stmt_error($stmt_delete);
        mysqli_stmt_close($stmt_delete);
        mysqli_close($koneksi);
        header("Location: ujian.php?status=gagal_hapus&msg=" . urlencode("Gagal menghapus ujian: " . $error_msg));
        exit();
    }
} else {
    $error_msg = mysqli_error($koneksi);
    mysqli_close($koneksi);
    header("Location: ujian.php?status=gagal_hapus&msg=" . urlencode("Gagal menyiapkan statement hapus: " . $error_msg));
    exit();
}
?>
