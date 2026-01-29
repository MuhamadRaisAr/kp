<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil semua data dari form (sekarang manual)
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Ambil ID tautan (jika dipilih)
    $id_guru = !empty($_POST['id_guru']) ? (int)$_POST['id_guru'] : null;
    $id_siswa = !empty($_POST['id_siswa']) ? (int)$_POST['id_siswa'] : null;

    // Validasi dasar
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        header("Location: tambah_user.php?status=gagal_kosong");
        exit();
    }

    // Enkripsi password
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert ke tabel users
    $stmt_user = mysqli_prepare($koneksi, "INSERT INTO users (username, email, password, role, id_guru, id_siswa) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt_user, "ssssii", $username, $email, $password_hashed, $role, $id_guru, $id_siswa);
    
    if (mysqli_stmt_execute($stmt_user)) {
        $id_user_baru = mysqli_insert_id($koneksi);
        
        // Tautkan id_user baru ke tabel guru atau siswa jika dipilih
        if ($id_guru) {
            $stmt_update = mysqli_prepare($koneksi, "UPDATE guru SET id_user = ? WHERE id_guru = ?");
            mysqli_stmt_bind_param($stmt_update, "ii", $id_user_baru, $id_guru);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
        }
        if ($id_siswa) {
            // Pastikan Anda sudah menambahkan kolom `id_user` di tabel siswa
            $stmt_update = mysqli_prepare($koneksi, "UPDATE siswa SET id_user = ? WHERE id_siswa = ?");
            mysqli_stmt_bind_param($stmt_update, "ii", $id_user_baru, $id_siswa);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
        }
        
        header("Location: users.php?status=sukses_tambah");
    } else {
        if (mysqli_errno($koneksi) == 1062) { // Error untuk duplikat data
             header("Location: tambah_user.php?status=gagal_duplikat");
        } else {
             header("Location: users.php?status=gagal_tambah");
        }
    }
    mysqli_stmt_close($stmt_user);
    mysqli_close($koneksi);
    exit();
}
?>
