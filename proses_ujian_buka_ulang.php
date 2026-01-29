<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ujian.php");
    exit;
}

$id_ujian = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_guru  = (int)$_SESSION['id_guru'];

// Ambil durasi & pastikan ujian milik guru
$q = "SELECT u.durasi_menit 
      FROM ujian u
      JOIN mengajar m ON u.id_mengajar = m.id_mengajar
      WHERE u.id_ujian = ? AND m.id_guru = ?";

$stmt = mysqli_prepare($koneksi, $q);
mysqli_stmt_bind_param($stmt, "ii", $id_ujian, $id_guru);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($res) == 0) {
    header("Location: ujian.php");
    exit;
}

$data = mysqli_fetch_assoc($res);
$durasi = (int)$data['durasi_menit'];

$now = date('Y-m-d H:i:s');
$selesai = date('Y-m-d H:i:s', strtotime("+{$durasi} minutes"));

// Update ujian
$update = "UPDATE ujian 
           SET waktu_mulai = ?, 
               waktu_selesai = ?, 
               status_ujian = 'Published'
           WHERE id_ujian = ?";

$stmt2 = mysqli_prepare($koneksi, $update);
mysqli_stmt_bind_param($stmt2, "ssi", $now, $selesai, $id_ujian);
mysqli_stmt_execute($stmt2);

header("Location: ujian.php");
exit;
