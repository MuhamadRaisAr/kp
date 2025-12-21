<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil semua data dari form, termasuk ID guru yang tersembunyi
    $id_guru = $_POST['id_guru'];
    $nip = $_POST['nip'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $no_telepon = $_POST['no_telepon'];

    // Query UPDATE dengan prepared statement
    $query = "UPDATE guru SET nip=?, nama_lengkap=?, email=?, no_telepon=? WHERE id_guru=?";
    
    $stmt = mysqli_prepare($koneksi, $query);
    
    // Binding parameter ke statement ('ssssi' -> 4 string, 1 integer)
    mysqli_stmt_bind_param($stmt, "ssssi", $nip, $nama_lengkap, $email, $no_telepon, $id_guru);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: guru.php?status=sukses_edit");
    } else {
        header("Location: guru.php?status=gagal_edit");
    }
    
    mysqli_stmt_close($stmt);

} else {
    header("Location: guru.php");
}

mysqli_close($koneksi);
exit();
?>