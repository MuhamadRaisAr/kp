<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Hanya admin yang bisa menghapus
if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

// Cek apakah ada parameter ID di URL
if (isset($_GET['id'])) {
    $id_guru = $_GET['id'];

    // Gunakan prepared statement untuk keamanan
    $query = "DELETE FROM guru WHERE id_guru = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_guru);

    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect ke halaman guru dengan pesan sukses
        header("Location: guru.php?status=sukses_hapus");
    } else {
        // Jika gagal (karena masih jadi wali kelas atau ada di jadwal mengajar), redirect dengan pesan error
        header("Location: guru.php?status=gagal_hapus_terhubung");
    }
    mysqli_stmt_close($stmt);

} else {
    // Jika tidak ada ID, redirect ke halaman guru
    header("Location: guru.php");
}

mysqli_close($koneksi);
exit();
?>
