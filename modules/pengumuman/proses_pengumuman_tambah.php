<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/koneksi.php';
require_once '../../includes/auth_check.php'; // Memastikan login

// 1. Validasi Akses: Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['id_user'])) {
    header("Location: dashboard.php?error=Akses ditolak");
    exit();
}
$id_user_pembuat = (int)$_SESSION['id_user'];

// 2. Validasi Metode: POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pengumuman.php");
    exit();
}

// 3. Ambil dan Validasi Data dari Form
$judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
$isi = isset($_POST['isi']) ? trim($_POST['isi']) : '';
$target_role = isset($_POST['target_role']) ? $_POST['target_role'] : 'semua';
$tanggal_posting = date('Y-m-d H:i:s'); // Waktu saat ini
$is_aktif = 1; // Default aktif saat dibuat

// Validasi dasar
if (empty($judul) || empty($isi)) {
    header("Location: pengumuman_tambah.php?status=gagal_tambah&msg=" . urlencode("Judul dan Isi wajib diisi."));
    exit();
}

// Pastikan target_role valid
$allowed_roles = ['semua', 'guru', 'siswa'];
if (!in_array($target_role, $allowed_roles)) {
    $target_role = 'semua'; // Default ke 'semua' jika tidak valid
}

// 4. Proses Simpan ke Database
$query_insert = "INSERT INTO pengumuman (judul, isi, tanggal_posting, id_user_pembuat, target_role, is_aktif)
                 VALUES (?, ?, ?, ?, ?, ?)";
$stmt_insert = mysqli_prepare($koneksi, $query_insert);

if ($stmt_insert) {
    mysqli_stmt_bind_param(
        $stmt_insert,
        "sssisi", // s = string, i = integer
        $judul,
        $isi,
        $tanggal_posting,
        $id_user_pembuat,
        $target_role,
        $is_aktif
    );

    // Eksekusi query
    if (mysqli_stmt_execute($stmt_insert)) {
        mysqli_stmt_close($stmt_insert);
        mysqli_close($koneksi);
        // Redirect ke halaman daftar pengumuman dengan pesan sukses
        header("Location: pengumuman.php?status=sukses_tambah");
        exit();
    } else {
        // Jika query gagal
        $error_msg = mysqli_stmt_error($stmt_insert);
        mysqli_stmt_close($stmt_insert);
        mysqli_close($koneksi);
        header("Location: pengumuman_tambah.php?status=gagal_tambah&msg=" . urlencode("Gagal menyimpan: " . $error_msg));
        exit();
    }
} else {
    // Jika statement gagal
    $error_msg = mysqli_error($koneksi);
    mysqli_close($koneksi);
    header("Location: pengumuman_tambah.php?status=gagal_tambah&msg=" . urlencode("Gagal menyiapkan statement: " . $error_msg));
    exit();
}
?>
