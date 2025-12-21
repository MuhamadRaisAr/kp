<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Panggil file koneksi dan auth_check
require_once 'includes/koneksi.php';
require_once 'includes/auth_check.php'; // auth_check akan memastikan user login

// 1. Validasi Akses: Pastikan yang mengakses adalah guru
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    // Jika bukan guru, tendang ke dashboard (atau halaman lain)
    header("Location: dashboard.php?error=Akses ditolak");
    exit();
}
$id_guru_login = (int)$_SESSION['id_guru'];

// 2. Validasi Metode Request: Pastikan data dikirim via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Jika bukan POST, tendang ke halaman daftar ujian
    header("Location: ujian.php");
    exit();
}

// 3. Ambil dan Validasi Data dari Form
// Ambil data, gunakan htmlspecialchars untuk keamanan dasar
$id_mengajar = isset($_POST['id_mengajar']) ? (int)$_POST['id_mengajar'] : 0;
$judul_ujian = isset($_POST['judul_ujian']) ? trim($_POST['judul_ujian']) : '';
$durasi_menit = isset($_POST['durasi_menit']) ? (int)$_POST['durasi_menit'] : 0;
$waktu_mulai_str = isset($_POST['waktu_mulai']) ? trim($_POST['waktu_mulai']) : '';
$waktu_selesai_str = isset($_POST['waktu_selesai']) ? trim($_POST['waktu_selesai']) : '';

// Konversi string waktu ke format DATETIME MySQL (Y-m-d H:i:s)
$waktu_mulai = date('Y-m-d H:i:s', strtotime($waktu_mulai_str));
$waktu_selesai = date('Y-m-d H:i:s', strtotime($waktu_selesai_str));

// Validasi dasar (pastikan data penting tidak kosong)
if ($id_mengajar <= 0 || empty($judul_ujian) || $durasi_menit <= 0 || empty($waktu_mulai_str) || empty($waktu_selesai_str)) {
    // Jika data tidak lengkap, kembali ke form tambah dengan pesan error
    header("Location: ujian_tambah.php?status=error&msg=" . urlencode("Semua field wajib diisi."));
    exit();
}

// Validasi tambahan: Pastikan waktu selesai setelah waktu mulai
if (strtotime($waktu_selesai_str) <= strtotime($waktu_mulai_str)) {
    header("Location: ujian_tambah.php?status=error&msg=" . urlencode("Waktu selesai harus setelah waktu mulai."));
    exit();
}

// Validasi tambahan: Pastikan id_mengajar ini benar-benar milik guru yang login
$stmt_cek = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM mengajar WHERE id_mengajar = ? AND id_guru = ?");
mysqli_stmt_bind_param($stmt_cek, "ii", $id_mengajar, $id_guru_login);
mysqli_stmt_execute($stmt_cek);
mysqli_stmt_bind_result($stmt_cek, $count);
mysqli_stmt_fetch($stmt_cek);
mysqli_stmt_close($stmt_cek);

if ($count == 0) {
    // Jika id_mengajar tidak valid untuk guru ini
    header("Location: ujian_tambah.php?status=error&msg=" . urlencode("Penugasan mengajar tidak valid."));
    exit();
}

// 4. Proses Simpan ke Database
// Status awal ujian adalah 'Draft'
$status_ujian = 'Draft';

// Gunakan prepared statement untuk menyimpan data
$query_insert = "INSERT INTO ujian (id_mengajar, judul_ujian, durasi_menit, waktu_mulai, waktu_selesai, status_ujian) 
                 VALUES (?, ?, ?, ?, ?, ?)";
$stmt_insert = mysqli_prepare($koneksi, $query_insert);

if ($stmt_insert) {
    mysqli_stmt_bind_param(
        $stmt_insert, 
        "isssss", // i = integer, s = string
        $id_mengajar, 
        $judul_ujian, 
        $durasi_menit, 
        $waktu_mulai, 
        $waktu_selesai, 
        $status_ujian
    );

    // Eksekusi query
    if (mysqli_stmt_execute($stmt_insert)) {
        // Jika berhasil disimpan, dapatkan ID ujian yang baru dibuat
        $id_ujian_baru = mysqli_insert_id($koneksi);
        
        // Tutup statement insert
        mysqli_stmt_close($stmt_insert);
        mysqli_close($koneksi);

        // Arahkan ke halaman detail ujian untuk tambah soal
        header("Location: ujian_detail.php?id=" . $id_ujian_baru . "&status=sukses_buat");
        exit();
        
    } else {
        // Jika query gagal dieksekusi
        $error_msg = mysqli_stmt_error($stmt_insert);
        mysqli_stmt_close($stmt_insert);
        mysqli_close($koneksi);
        header("Location: ujian_tambah.php?status=error&msg=" . urlencode("Gagal menyimpan data ujian: " . $error_msg));
        exit();
    }
} else {
    // Jika statement gagal disiapkan
    $error_msg = mysqli_error($koneksi);
    mysqli_close($koneksi);
    header("Location: ujian_tambah.php?status=error&msg=" . urlencode("Gagal menyiapkan statement: " . $error_msg));
    exit();
}
?>