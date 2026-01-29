<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

// pastikan guru login
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    header("Location: ujian.php?status=gagal");
    exit();
}

$id_guru = (int)$_SESSION['id_guru'];

// validasi id ujian
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ujian.php?status=gagal");
    exit();
}

$id_ujian = (int)$_GET['id'];

// ambil data ujian + validasi kepemilikan
$sql = "SELECT 
            u.id_ujian,
            u.durasi_menit
        FROM ujian u
        JOIN mengajar m ON u.id_mengajar = m.id_mengajar
        WHERE u.id_ujian = ?
          AND m.id_guru = ?
          AND u.status_ujian = 'Published'
        LIMIT 1";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id_ujian, $id_guru);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) !== 1) {
    mysqli_stmt_close($stmt);
    header("Location: ujian.php?status=gagal");
    exit();
}

$data = mysqli_fetch_assoc($result);
$durasi = (int)$data['durasi_menit'];

mysqli_stmt_close($stmt);

// hitung waktu baru
$waktu_mulai_baru   = date('Y-m-d H:i:s');
$waktu_selesai_baru = date('Y-m-d H:i:s', strtotime("+$durasi minutes"));

// update ujian
$update = "UPDATE ujian 
           SET 
               waktu_mulai = ?,
               waktu_selesai = ?
           WHERE id_ujian = ?
           LIMIT 1";

$stmt = mysqli_prepare($koneksi, $update);
mysqli_stmt_bind_param(
    $stmt,
    "ssi",
    $waktu_mulai_baru,
    $waktu_selesai_baru,
    $id_ujian
);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    header("Location: ujian.php?status=sukses_buka");
    exit();
} else {
    mysqli_stmt_close($stmt);
    header("Location: ujian.php?status=gagal");
    exit();
}
