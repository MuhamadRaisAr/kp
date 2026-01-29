<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id_tahun_ajaran'];
    $tahun_ajaran = $_POST['tahun_ajaran'];
    $semester = $_POST['semester'];
    $status_aktif = $_POST['status_aktif'];

    $stmt = mysqli_prepare($koneksi, "UPDATE tahun_ajaran SET tahun_ajaran = ?, semester = ?, status_aktif = ? WHERE id_tahun_ajaran = ?");
    mysqli_stmt_bind_param($stmt, "sssi", $tahun_ajaran, $semester, $status_aktif, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: tahun_ajaran.php?status=sukses_edit");
    } else {
        header("Location: tahun_ajaran.php?status=gagal_edit");
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($koneksi);
exit();
?>
