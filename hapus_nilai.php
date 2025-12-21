<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if ($_SESSION['role'] != 'guru') {
    header("Location: dashboard.php");
    exit();
}

$id_nilai = $_GET['id'] ?? 0;
$id_mengajar = $_GET['id_mengajar'] ?? 0;

// Hapus hanya jika nilai dari kelas guru ini
$query = "DELETE n FROM nilai n 
          JOIN mengajar m ON n.id_mengajar = m.id_mengajar
          WHERE n.id_nilai = '$id_nilai' AND m.id_guru = '" . $_SESSION['id_guru'] . "'";
mysqli_query($koneksi, $query);

header("Location: nilai_input.php?id_mengajar=$id_mengajar");
exit();
