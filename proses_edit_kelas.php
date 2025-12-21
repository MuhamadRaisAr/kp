<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil semua data dari form, termasuk ID kelas yang tersembunyi
    $id_kelas = $_POST['id_kelas'];
    $nama_kelas = $_POST['nama_kelas'];
    $tingkat = $_POST['tingkat'];
    $id_jurusan = $_POST['id_jurusan'];
    $id_guru_wali_kelas = $_POST['id_guru_wali_kelas'];

    // Query UPDATE dengan prepared statement
    $query = "UPDATE kelas SET nama_kelas=?, tingkat=?, id_jurusan=?, id_guru_wali_kelas=? WHERE id_kelas=?";
    
    $stmt = mysqli_prepare($koneksi, $query);
    
    // Binding parameter ke statement ('ssiii' -> 2 string, 3 integer)
    mysqli_stmt_bind_param($stmt, "ssiii", $nama_kelas, $tingkat, $id_jurusan, $id_guru_wali_kelas, $id_kelas);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: kelas.php?status=sukses_edit");
    } else {
        header("Location: kelas.php?status=gagal_edit");
    }
    
    mysqli_stmt_close($stmt);

} else {
    header("Location: kelas.php");
}

mysqli_close($koneksi);
exit();
?>