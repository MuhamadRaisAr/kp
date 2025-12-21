<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

// Cek apakah ada parameter ID di URL
if (isset($_GET['id'])) {
    $id_kelas = $_GET['id'];

    // Gunakan prepared statement untuk keamanan
    $query = "DELETE FROM kelas WHERE id_kelas = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    // Binding parameter
    mysqli_stmt_bind_param($stmt, "i", $id_kelas);

    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect ke halaman kelas dengan pesan sukses
        header("Location: kelas.php?status=sukses_hapus");
    } else {
        // Jika gagal, redirect dengan pesan error
        header("Location: kelas.php?status=gagal_hapus");
    }

    // Tutup statement
    mysqli_stmt_close($stmt);

} else {
    // Jika tidak ada ID, redirect ke halaman kelas
    header("Location: kelas.php");
}

// Tutup koneksi
mysqli_close($koneksi);
exit();
?>