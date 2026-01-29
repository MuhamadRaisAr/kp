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
// $id_user_pembuat = (int)$_SESSION['id_user']; // Tidak perlu update pembuat

// 2. Validasi Metode: POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pengumuman.php");
    exit();
}

// 3. Ambil dan Validasi Data dari Form
$id_pengumuman = isset($_POST['id_pengumuman']) ? (int)$_POST['id_pengumuman'] : 0;
$judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
$isi = isset($_POST['isi']) ? trim($_POST['isi']) : '';
$target_role = isset($_POST['target_role']) ? $_POST['target_role'] : 'semua';

// Validasi dasar
if ($id_pengumuman <= 0 || empty($judul) || empty($isi)) {
    header("Location: pengumuman_edit.php?id=" . $id_pengumuman . "&status=gagal_edit&msg=" . urlencode("ID, Judul, dan Isi wajib diisi."));
    exit();
}

// Pastikan target_role valid
$allowed_roles = ['semua', 'guru', 'siswa'];
if (!in_array($target_role, $allowed_roles)) {
    $target_role = 'semua';
}

// 4. Proses Update ke Database
$query_update = "UPDATE pengumuman SET judul = ?, isi = ?, target_role = ? WHERE id_pengumuman = ? AND is_aktif = 1"; // Hanya update yg aktif
$stmt_update = mysqli_prepare($koneksi, $query_update);

if ($stmt_update) {
    mysqli_stmt_bind_param(
        $stmt_update,
        "sssi", // s = string, i = integer
        $judul,
        $isi,
        $target_role,
        $id_pengumuman
    );

    // Eksekusi query
    if (mysqli_stmt_execute($stmt_update)) {
        // Cek apakah ada baris yang terpengaruh (berhasil diupdate)
        if (mysqli_stmt_affected_rows($stmt_update) > 0) {
            mysqli_stmt_close($stmt_update);
            mysqli_close($koneksi);
            // Redirect ke halaman daftar pengumuman dengan pesan sukses
            header("Location: pengumuman.php?status=sukses_edit");
            exit();
        } else {
             // Pengumuman tidak ditemukan atau tidak aktif
            mysqli_stmt_close($stmt_update);
            mysqli_close($koneksi);
            header("Location: pengumuman.php?status=gagal_edit&msg=" . urlencode("Pengumuman tidak ditemukan atau tidak dapat diedit."));
            exit();
        }
    } else {
        // Jika query gagal
        $error_msg = mysqli_stmt_error($stmt_update);
        mysqli_stmt_close($stmt_update);
        mysqli_close($koneksi);
        header("Location: pengumuman_edit.php?id=" . $id_pengumuman . "&status=gagal_edit&msg=" . urlencode("Gagal menyimpan perubahan: " . $error_msg));
        exit();
    }
} else {
    // Jika statement gagal
    $error_msg = mysqli_error($koneksi);
    mysqli_close($koneksi);
    header("Location: pengumuman_edit.php?id=" . $id_pengumuman . "&status=gagal_edit&msg=" . urlencode("Gagal menyiapkan statement update: " . $error_msg));
    exit();
}
?>
