<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Hanya admin yang bisa menghapus user
if ($_SESSION['role'] != 'admin') { 
    die("Akses ditolak."); 
}

// Cek apakah ID user ada di URL
if (isset($_GET['id'])) {
    $id_user_hapus = (int)$_GET['id'];
    $id_user_login = (int)$_SESSION['id_user'];

    // 1. Validasi: Mencegah admin menghapus akunnya sendiri
    if ($id_user_hapus === $id_user_login) {
        header("Location: users.php?status=gagal_hapus_sendiri");
        exit();
    }

    // Sebelum menghapus user, kita harus melepaskan tautannya dari tabel guru (jika ada)
    // Ini untuk mencegah error foreign key
    $stmt_guru = mysqli_prepare($koneksi, "UPDATE guru SET id_user = NULL WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt_guru, "i", $id_user_hapus);
    mysqli_stmt_execute($stmt_guru);
    mysqli_stmt_close($stmt_guru);

    // CRITICAL FIX: Lepaskan juga tautan dari tabel SISWA
    $stmt_siswa = mysqli_prepare($koneksi, "UPDATE siswa SET id_user = NULL WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt_siswa, "i", $id_user_hapus);
    mysqli_stmt_execute($stmt_siswa);
    mysqli_stmt_close($stmt_siswa);

    // 2. Lanjutkan proses hapus dari tabel users
    $stmt_user = mysqli_prepare($koneksi, "DELETE FROM users WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt_user, "i", $id_user_hapus);

    if (mysqli_stmt_execute($stmt_user)) {
        header("Location: users.php?status=sukses_hapus");
    } else {
        $error = mysqli_error($koneksi);
        header("Location: users.php?status=gagal_hapus&error=" . urlencode($error));
    }

    mysqli_stmt_close($stmt_user);

} else {
    // Jika tidak ada ID, redirect
    header("Location: users.php");
}

mysqli_close($koneksi);
exit();
?>
