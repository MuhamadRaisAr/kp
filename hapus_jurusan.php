<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

// Hanya admin yang bisa menghapus
if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

// Cek apakah ada parameter ID di URL
if (isset($_GET['id'])) {
    $id_jurusan = $_GET['id'];

    // Gunakan prepared statement untuk keamanan
    $query = "DELETE FROM jurusan WHERE id_jurusan = ?";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_jurusan);

    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect ke halaman jurusan dengan pesan sukses
        header("Location: jurusan.php?status=sukses_hapus");
    } else {
        // Jika gagal (karena masih dipakai tabel kelas), redirect dengan pesan error
        header("Location: jurusan.php?status=gagal_hapus_terhubung");
    }
    mysqli_stmt_close($stmt);
} else {
    // Jika tidak ada ID, redirect ke halaman jurusan
    header("Location: jurusan.php");
}

mysqli_close($koneksi);
exit();
?>