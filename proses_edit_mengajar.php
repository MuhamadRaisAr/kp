<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_mengajar = $_POST['id_mengajar'];
    $id_guru = $_POST['id_guru'];
    $id_mapel = $_POST['id_mapel'];
    $id_kelas = $_POST['id_kelas'];
    $id_tahun_ajaran = $_POST['id_tahun_ajaran'];

    // Cek duplikasi data, pastikan tidak ada jadwal lain yang sama persis
    $stmt_cek = mysqli_prepare($koneksi, "SELECT id_mengajar FROM mengajar WHERE id_guru=? AND id_mapel=? AND id_kelas=? AND id_tahun_ajaran=? AND id_mengajar != ?");
    mysqli_stmt_bind_param($stmt_cek, "iiiii", $id_guru, $id_mapel, $id_kelas, $id_tahun_ajaran, $id_mengajar);
    mysqli_stmt_execute($stmt_cek);
    if (mysqli_stmt_fetch($stmt_cek)) {
        header("Location: mengajar.php?status=gagal_duplikat");
        exit();
    }
    mysqli_stmt_close($stmt_cek);

    // Jika tidak duplikat, update data
    $stmt_update = mysqli_prepare($koneksi, "UPDATE mengajar SET id_guru=?, id_mapel=?, id_kelas=?, id_tahun_ajaran=? WHERE id_mengajar=?");
    mysqli_stmt_bind_param($stmt_update, "iiiii", $id_guru, $id_mapel, $id_kelas, $id_tahun_ajaran, $id_mengajar);
    
    if (mysqli_stmt_execute($stmt_update)) {
        header("Location: mengajar.php?status=sukses_edit");
    } else {
        header("Location: mengajar.php?status=gagal_edit");
    }
    mysqli_stmt_close($stmt_update);
}
mysqli_close($koneksi);
?>