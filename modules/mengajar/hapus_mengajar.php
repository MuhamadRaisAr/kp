<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';
// Role check moved below

if (isset($_GET['id'])) {
    $id_mengajar = $_GET['id'];

    // PENTING: Menghapus sebuah jadwal mengajar juga akan 
    // menghapus semua nilai yang sudah diinput untuk jadwal tersebut secara otomatis 
    // (karena aturan ON DELETE CASCADE di database).
    
    // Cek dulu ini kelas mana dan siapa wali kelasnya
    $stmt_cek = mysqli_prepare($koneksi, "SELECT m.id_kelas, k.id_guru_wali_kelas 
                                          FROM mengajar m 
                                          JOIN kelas k ON m.id_kelas = k.id_kelas 
                                          WHERE m.id_mengajar = ?");
    mysqli_stmt_bind_param($stmt_cek, "i", $id_mengajar);
    mysqli_stmt_execute($stmt_cek);
    $res_cek = mysqli_stmt_get_result($stmt_cek);
    $data_cek = mysqli_fetch_assoc($res_cek);

    if (!$data_cek) {
        die("Data penugasan tidak ditemukan.");
    }

    $can_delete = false;
    $role = $_SESSION['role'] ?? 'guest';

    if ($role == 'admin') {
        $can_delete = true;
    } elseif ($role == 'guru' && isset($_SESSION['id_guru']) && $data_cek['id_guru_wali_kelas'] == $_SESSION['id_guru']) {
        $can_delete = true;
    }

    if (!$can_delete) {
        die("Akses ditolak. Anda bukan Wali Kelas dari kelas ini.");
    }

    $id_kelas_target = $data_cek['id_kelas'];

    $stmt = mysqli_prepare($koneksi, "DELETE FROM mengajar WHERE id_mengajar = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_mengajar);
    
    if (mysqli_stmt_execute($stmt)) {
        if (isset($_GET['ref']) && $_GET['ref'] == 'kelas' && isset($_GET['id_kelas'])) {
            header("Location: detail_pengajar.php?id_kelas=" . $_GET['id_kelas'] . "&status=sukses_hapus");
        } else {
            header("Location: mengajar.php?status=sukses_hapus");
        }
    } else {
        if (isset($_GET['ref']) && $_GET['ref'] == 'kelas' && isset($_GET['id_kelas'])) {
            header("Location: detail_pengajar.php?id_kelas=" . $_GET['id_kelas'] . "&status=gagal_hapus");
        } else {
            header("Location: mengajar.php?status=gagal_hapus");
        }
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($koneksi);
?>
