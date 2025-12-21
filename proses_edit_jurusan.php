<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil data dari form
    $id_jurusan = $_POST['id_jurusan'];
    $kode_jurusan = $_POST['kode_jurusan'];
    $nama_jurusan = $_POST['nama_jurusan'];

    // Query UPDATE dengan prepared statement
    $query = "UPDATE jurusan SET kode_jurusan = ?, nama_jurusan = ? WHERE id_jurusan = ?";
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $kode_jurusan, $nama_jurusan, $id_jurusan);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: jurusan.php?status=sukses_edit");
    } else {
        header("Location: jurusan.php?status=gagal_edit");
    }
    
    mysqli_stmt_close($stmt);

} else {
    header("Location: jurusan.php");
}

mysqli_close($koneksi);
exit();
?>