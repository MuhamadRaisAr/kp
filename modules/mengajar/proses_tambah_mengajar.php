<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Batasi akses hanya untuk admin
if ($_SESSION['role'] != 'admin') { 
    die("Akses ditolak."); 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form dengan null coalescing operator untuk keamanan
    $id_guru = $_POST['id_guru'] ?? '';
    $id_mapel = $_POST['id_mapel'] ?? '';
    $id_kelas = $_POST['id_kelas'] ?? '';
    $id_tahun_ajaran = $_POST['id_tahun_ajaran'] ?? '';
    $hari = $_POST['hari'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';

    // 1. Validasi field wajib diisi
    if (empty($id_guru) || empty($id_mapel) || empty($id_kelas) || empty($id_tahun_ajaran) || empty($hari) || empty($jam_mulai) || empty($jam_selesai)) {
        header("Location: mengajar.php?status=field_kosong");
        exit();
    }

    // 2. Validasi format dan urutan jam
    if (strtotime($jam_mulai) === false || strtotime($jam_selesai) === false || strtotime($jam_mulai) >= strtotime($jam_selesai)) {
        header("Location: mengajar.php?status=jam_invalid");
        exit();
    }

    // 3. Cek bentrok jadwal untuk guru, hari, dan jam yang sama
    $stmt_cek = mysqli_prepare($koneksi, 
        "SELECT id_mengajar FROM mengajar WHERE id_guru = ? AND hari = ? AND 
        ((jam_mulai < ? AND jam_selesai > ?) OR (jam_mulai >= ? AND jam_mulai < ?))"
    );
    mysqli_stmt_bind_param($stmt_cek, "isssss", $id_guru, $hari, $jam_selesai, $jam_mulai, $jam_mulai, $jam_selesai);
    mysqli_stmt_execute($stmt_cek);
    mysqli_stmt_store_result($stmt_cek);

    if (mysqli_stmt_num_rows($stmt_cek) > 0) {
        mysqli_stmt_close($stmt_cek);
        header("Location: mengajar.php?status=duplikat");
        exit();
    }
    mysqli_stmt_close($stmt_cek);

    // 4. Jika semua validasi lolos, insert data
    $stmt_insert = mysqli_prepare($koneksi, 
        "INSERT INTO mengajar (id_guru, id_mapel, id_kelas, id_tahun_ajaran, hari, jam_mulai, jam_selesai) 
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt_insert, "iiiisss", $id_guru, $id_mapel, $id_kelas, $id_tahun_ajaran, $hari, $jam_mulai, $jam_selesai);

    if (mysqli_stmt_execute($stmt_insert)) {
        header("Location: mengajar.php?status=sukses_tambah");
    } else {
        $error = mysqli_error($koneksi);
        header("Location: mengajar.php?status=gagal_tambah&error=" . urlencode($error));
    }

    mysqli_stmt_close($stmt_insert);
}

mysqli_close($koneksi);
exit();
?>
