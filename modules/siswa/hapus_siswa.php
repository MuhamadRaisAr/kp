<?php
// Panggil file auth_check.php dan koneksi.php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Cek apakah ada parameter ID di URL
if (isset($_GET['id'])) {
    $id_siswa = $_GET['id'];

    // Gunakan prepared statement untuk keamanan
    $query = "DELETE FROM siswa WHERE id_siswa = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    // Binding parameter
    mysqli_stmt_bind_param($stmt, "i", $id_siswa);

    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect ke halaman siswa dengan pesan sukses
        header("Location: siswa.php?status=sukses_hapus");
    } else {
        // Jika gagal, redirect dengan pesan error
        header("Location: siswa.php?status=gagal_hapus");
    }

    // Tutup statement
    mysqli_stmt_close($stmt);

} else {
    // Jika tidak ada ID, redirect ke halaman siswa
    header("Location: siswa.php");
}

// Tutup koneksi
mysqli_close($koneksi);
exit();
?>
