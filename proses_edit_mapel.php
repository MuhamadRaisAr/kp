<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_mapel = $_POST['id_mapel'];
    $kode_mapel = $_POST['kode_mapel'];
    $nama_mapel = $_POST['nama_mapel'];
    $jenis = $_POST['jenis'];

    $stmt = mysqli_prepare($koneksi, "UPDATE mata_pelajaran SET kode_mapel = ?, nama_mapel = ?, jenis = ? WHERE id_mapel = ?");
    mysqli_stmt_bind_param($stmt, "sssi", $kode_mapel, $nama_mapel, $jenis, $id_mapel);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: mapel.php?status=sukses_edit");
    } else {
        header("Location: mapel.php?status=gagal_edit");
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($koneksi);
exit();
?>