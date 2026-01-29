<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tahun_ajaran = $_POST['tahun_ajaran'];
    $semester = $_POST['semester'];
    $status_aktif = $_POST['status_aktif'];

    $stmt = mysqli_prepare($koneksi, "INSERT INTO tahun_ajaran (tahun_ajaran, semester, status_aktif) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $tahun_ajaran, $semester, $status_aktif);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: tahun_ajaran.php?status=sukses_tambah");
    } else {
        header("Location: tahun_ajaran.php?status=gagal_tambah");
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($koneksi);
exit();
?>
