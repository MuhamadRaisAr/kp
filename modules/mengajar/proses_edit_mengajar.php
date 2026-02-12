<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';
// Role check moved inside
// if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_mengajar = $_POST['id_mengajar'];
    $id_guru = $_POST['id_guru'];
    $id_mapel = $_POST['id_mapel'];
    $id_kelas = $_POST['id_kelas'];
    $id_tahun_ajaran = $_POST['id_tahun_ajaran'];

    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    // --- CEK OTORISASI ---
    $can_edit = false;
    $role = $_SESSION['role'] ?? 'guest';

    if ($role == 'admin') {
        $can_edit = true;
    } else {
        // Cek apakah user adalah Wali Kelas dari kelas target
        // Kita harus cek ID Kelas yang DIKIRIM (target), bukan ID Kelas yang lama.
        // Konsepnya: Wali kelas hanya boleh mengatur jadwal di KELAS DIA SENDIRI.
        // Jadi kita cek apakah $_POST['id_kelas'] ini wali kelasnya adalah user yang sedang login.
        
        $ck = mysqli_prepare($koneksi, "SELECT id_guru_wali_kelas FROM kelas WHERE id_kelas = ?");
        mysqli_stmt_bind_param($ck, "i", $id_kelas);
        mysqli_stmt_execute($ck);
        $res_ck = mysqli_stmt_get_result($ck);
        $dat_ck = mysqli_fetch_assoc($res_ck);
        
        if ($dat_ck && isset($_SESSION['id_guru']) && $dat_ck['id_guru_wali_kelas'] == $_SESSION['id_guru']) {
            $can_edit = true;
        }
    }

    if (!$can_edit) {
        die("Akses ditolak. Anda bukan Wali Kelas dari kelas target.");
    }
    // ---------------------

    // Cek duplikasi data, pastikan tidak ada jadwal lain yang sama persis
    $stmt_cek = mysqli_prepare($koneksi, "SELECT id_mengajar FROM mengajar WHERE id_guru=? AND id_mapel=? AND id_kelas=? AND id_tahun_ajaran=? AND hari=? AND jam_mulai=? AND id_mengajar != ?");
    mysqli_stmt_bind_param($stmt_cek, "iiiiisi", $id_guru, $id_mapel, $id_kelas, $id_tahun_ajaran, $hari, $jam_mulai, $id_mengajar);
    mysqli_stmt_execute($stmt_cek);
    if (mysqli_stmt_fetch($stmt_cek)) {
        header("Location: edit_mengajar.php?id=$id_mengajar&status=gagal_duplikat");
        exit();
    }
    mysqli_stmt_close($stmt_cek);

    // Jika tidak duplikat, update data
    $stmt_update = mysqli_prepare($koneksi, "UPDATE mengajar SET id_guru=?, id_mapel=?, id_kelas=?, id_tahun_ajaran=?, hari=?, jam_mulai=?, jam_selesai=? WHERE id_mengajar=?");
    mysqli_stmt_bind_param($stmt_update, "iiiisssi", $id_guru, $id_mapel, $id_kelas, $id_tahun_ajaran, $hari, $jam_mulai, $jam_selesai, $id_mengajar);
    
    if (mysqli_stmt_execute($stmt_update)) {
        // Redirect kembali ke detail kelas jika ID Kelas ada
        header("Location: detail_pengajar.php?id_kelas=$id_kelas&status=sukses_edit");
    } else {
        header("Location: edit_mengajar.php?id=$id_mengajar&status=gagal_edit");
    }
    mysqli_stmt_close($stmt_update);
}
mysqli_close($koneksi);
?>
