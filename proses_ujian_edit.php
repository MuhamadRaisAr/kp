<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    header("Location: ujian.php");
    exit();
}

$id_guru = (int)$_SESSION['id_guru'];

$id_ujian = (int)$_POST['id_ujian'];
$mode = $_POST['mode'];
$waktu_mulai = $_POST['waktu_mulai'];
$waktu_selesai = $_POST['waktu_selesai'];

// validasi kepemilikan ujian
$cek = "SELECT u.id_ujian
        FROM ujian u
        JOIN mengajar m ON u.id_mengajar = m.id_mengajar
        WHERE u.id_ujian = ? AND m.id_guru = ?
        LIMIT 1";

$stmt = mysqli_prepare($koneksi, $cek);
mysqli_stmt_bind_param($stmt, "ii", $id_ujian, $id_guru);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($res) !== 1) {
    header("Location: ujian.php");
    exit();
}
mysqli_stmt_close($stmt);

// update ujian
$sql = "UPDATE ujian
        SET waktu_mulai = ?, waktu_selesai = ?, status_ujian = 'Published'
        WHERE id_ujian = ?
        LIMIT 1";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ssi", $waktu_mulai, $waktu_selesai, $id_ujian);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: ujian.php?status=sukses_buka");
exit();
