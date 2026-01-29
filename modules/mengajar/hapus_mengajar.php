<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if (isset($_GET['id'])) {
    $id_mengajar = $_GET['id'];

    // PENTING: Menghapus sebuah jadwal mengajar juga akan 
    // menghapus semua nilai yang sudah diinput untuk jadwal tersebut secara otomatis 
    // (karena aturan ON DELETE CASCADE di database).
    
    $stmt = mysqli_prepare($koneksi, "DELETE FROM mengajar WHERE id_mengajar = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_mengajar);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: mengajar.php?status=sukses_hapus");
    } else {
        header("Location: mengajar.php?status=gagal_hapus");
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($koneksi);
?>
