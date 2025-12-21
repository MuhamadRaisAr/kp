<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if (isset($_GET['id'])) {
    $id_mapel = $_GET['id'];

    $stmt = mysqli_prepare($koneksi, "DELETE FROM mata_pelajaran WHERE id_mapel = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_mapel);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: mapel.php?status=sukses_hapus");
    } else {
        // Error ini akan muncul jika mapel masih digunakan di tabel `mengajar`
        header("Location: mapel.php?status=gagal_hapus");
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($koneksi);
exit();
?>