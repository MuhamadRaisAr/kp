<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil semua data dari form, termasuk ID siswa yang tersembunyi
    $id_siswa = (int)$_POST['id_siswa'];
    $nis = $_POST['nis'];
    $nisn = $_POST['nisn'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $tanggal_lahir = trim($_POST['tanggal_lahir']);
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $alamat = $_POST['alamat'];
    $id_kelas = (int)$_POST['id_kelas'];

    if ($tanggal_lahir === '') {
        // Jika tanggal kosong, set jadi NULL di DB
        $query = "UPDATE siswa SET nis=?, nisn=?, nama_lengkap=?, tanggal_lahir=NULL, jenis_kelamin=?, alamat=?, id_kelas=? WHERE id_siswa=?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssssiii", $nis, $nisn, $nama_lengkap, $jenis_kelamin, $alamat, $id_kelas, $id_siswa);
    } else {
        // Jika tanggal ada, bind seperti biasa
        $query = "UPDATE siswa SET nis=?, nisn=?, nama_lengkap=?, tanggal_lahir=?, jenis_kelamin=?, alamat=?, id_kelas=? WHERE id_siswa=?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssssssii", $nis, $nisn, $nama_lengkap, $tanggal_lahir, $jenis_kelamin, $alamat, $id_kelas, $id_siswa);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: siswa.php?status=sukses_edit");
    } else {
        header("Location: siswa.php?status=gagal_edit");
    }
    
    mysqli_stmt_close($stmt);

} else {
    header("Location: siswa.php");
}

mysqli_close($koneksi);
exit();
?>
