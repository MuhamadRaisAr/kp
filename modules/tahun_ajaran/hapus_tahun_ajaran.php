<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = mysqli_prepare($koneksi, "DELETE FROM tahun_ajaran WHERE id_tahun_ajaran = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: tahun_ajaran.php?status=sukses_hapus");
    } else {
        // Error ini akan muncul jika tahun ajaran masih digunakan di tabel `mengajar`
        header("Location: tahun_ajaran.php?status=gagal_hapus");
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($koneksi);
exit();
?>
