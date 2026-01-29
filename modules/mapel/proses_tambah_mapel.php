<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_mapel = $_POST['kode_mapel'];
    $nama_mapel = $_POST['nama_mapel'];
    $jenis = $_POST['jenis'];

    $stmt = mysqli_prepare($koneksi, "INSERT INTO mata_pelajaran (kode_mapel, nama_mapel, jenis) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $kode_mapel, $nama_mapel, $jenis);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: mapel.php?status=sukses_tambah");
    } else {
        header("Location: mapel.php?status=gagal_tambah");
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($koneksi);
exit();
?>
