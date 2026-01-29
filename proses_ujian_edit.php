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
// Variables initialized later with fallback
$waktu_mulai = $_POST['waktu_mulai'];
$waktu_selesai = $_POST['waktu_selesai'];

// validasi kepemilikan ujian & ambil data lama (fallback jika form belum refresh)
$cek = "SELECT u.id_ujian, u.judul_ujian, u.durasi_menit
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

$data_lama = mysqli_fetch_assoc($res);

// Gunakan data dari POST jika ada, jika tidak (cache lama) gunakan data database
$judul_ujian = isset($_POST['judul_ujian']) ? $_POST['judul_ujian'] : $data_lama['judul_ujian'];
$durasi_menit = isset($_POST['durasi_menit']) ? (int)$_POST['durasi_menit'] : $data_lama['durasi_menit'];

if (mysqli_num_rows($res) !== 1) {
    header("Location: ujian.php");
    exit();
}
mysqli_stmt_close($stmt);

// 5. Proses Update ke Database
$query_update = "UPDATE ujian 
                 SET judul_ujian = ?, durasi_menit = ?, waktu_mulai = ?, waktu_selesai = ?
                 WHERE id_ujian = ?";
$stmt_update = mysqli_prepare($koneksi, $query_update);

if ($stmt_update) {
    mysqli_stmt_bind_param(
        $stmt_update, 
        "sisss", // s = string, i = integer
        $judul_ujian, 
        $durasi_menit, 
        $waktu_mulai, 
        $waktu_selesai, 
        $id_ujian
    );

    if (mysqli_stmt_execute($stmt_update)) {
        // Berhasil
        mysqli_stmt_close($stmt_update);
        mysqli_close($koneksi);
        header("Location: ujian.php?status=sukses_edit");
        exit();
    } else {
        // Gagal eksekusi query
        $error_msg = mysqli_stmt_error($stmt_update);
        mysqli_stmt_close($stmt_update);
        mysqli_close($koneksi);
        header("Location: ujian_edit.php?id=" . $id_ujian . "&status=error&msg=" . urlencode("Gagal menyimpan perubahan: " . $error_msg));
        exit();
    }
} else {
    // Gagal menyiapkan statement
    $error_msg = mysqli_error($koneksi);
    mysqli_close($koneksi);
    header("Location: ujian_edit.php?id=" . $id_ujian . "&status=error&msg=" . urlencode("Gagal menyiapkan statement update: " . $error_msg));
    exit();
}
?>
