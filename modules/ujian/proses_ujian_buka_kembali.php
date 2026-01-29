<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/koneksi.php';
require_once '../../includes/auth_check.php';

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

// 3. Ambil Data
$id_ujian = isset($_POST['id_ujian']) ? (int)$_POST['id_ujian'] : 0;
$waktu_selesai_baru_str = isset($_POST['waktu_selesai_baru']) ? trim($_POST['waktu_selesai_baru']) : '';
$tambah_menit = isset($_POST['tambah_menit']) ? (int)$_POST['tambah_menit'] : 0;

if ($id_ujian <= 0) {
    header("Location: ujian.php?error=ID Ujian tidak valid");
    exit();
}

// Validasi Kepemilikan
$query_cek = "SELECT u.id_ujian, u.waktu_selesai 
              FROM ujian u
              JOIN mengajar m ON u.id_mengajar = m.id_mengajar
              WHERE u.id_ujian = ? AND m.id_guru = ?";
$stmt_cek = mysqli_prepare($koneksi, $query_cek);
mysqli_stmt_bind_param($stmt_cek, "ii", $id_ujian, $id_guru_login);
mysqli_stmt_execute($stmt_cek);
$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) == 0) {
    header("Location: ujian.php?error=Akses ditolak atau ujian tidak ditemukan.");
    exit();
}

// Tentukan Waktu Selesai Baru
$waktu_selesai_final = '';

if (!empty($waktu_selesai_baru_str)) {
    // Jika input datetime manual
    $waktu_selesai_final = date('Y-m-d H:i:s', strtotime($waktu_selesai_baru_str));
} elseif ($tambah_menit > 0) {
    // Jika input menit tambahan (dari waktu sekarang)
    $waktu_sekarang = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
    $waktu_sekarang->modify("+$tambah_menit minutes");
    $waktu_selesai_final = $waktu_sekarang->format('Y-m-d H:i:s');
} else {
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=error&msg=" . urlencode("Waktu selesai baru belum ditentukan."));
    exit();
}

// Validasi logika waktu (opsional, tapi disarankan)
// Pastikan lebih dari NOW? Ya, karena tujuannya membuka kembali.
$now = date('Y-m-d H:i:s');
if ($waktu_selesai_final <= $now) {
     header("Location: ujian_detail.php?id=" . $id_ujian . "&status=error&msg=" . urlencode("Waktu selesai baru harus di masa depan."));
     exit();
}

// Update Database
// Kita juga set status_ujian = 'Published' untuk jaga-jaga jika sebelumnya statusnya lain
$query_update = "UPDATE ujian SET waktu_selesai = ?, status_ujian = 'Published' WHERE id_ujian = ?";
$stmt_update = mysqli_prepare($koneksi, $query_update);
mysqli_stmt_bind_param($stmt_update, "si", $waktu_selesai_final, $id_ujian);

if (mysqli_stmt_execute($stmt_update)) {
    mysqli_stmt_close($stmt_update);
    mysqli_close($koneksi);
    header("Location: ujian.php?status=sukses_buka_kembali");
    exit();
} else {
    $err = mysqli_error($koneksi);
    mysqli_stmt_close($stmt_update);
    mysqli_close($koneksi);
    header("Location: ujian_detail.php?id=" . $id_ujian . "&status=error&msg=" . urlencode("Gagal update: " . $err));
    exit();
}
?>
