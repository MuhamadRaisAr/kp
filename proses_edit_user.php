<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = (int) $_POST['id_user'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $status = $_POST['status'];

    $id_guru = isset($_POST['id_guru']) && $_POST['id_guru'] !== '' ? (int) $_POST['id_guru'] : null;
    $id_siswa = isset($_POST['id_siswa']) && $_POST['id_siswa'] !== '' ? (int) $_POST['id_siswa'] : null;

    // Validasi dasar
    if (empty($id_user) || empty($username) || empty($email) || empty($status)) {
        die("Data tidak lengkap.");
    }

    // Ambil role user dari DB
    // Ambil data user lama dari DB untuk mengetahui role sebelumnya
    $query_user = "SELECT role FROM users WHERE id_user = ?";
    $stmt = mysqli_prepare($koneksi, $query_user);
    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data_user = mysqli_fetch_assoc($result);

    if (!$data_user) {
        die("User tidak ditemukan.");
    }

    $old_role = $data_user['role'];
    $new_role = $_POST['role']; // Ambil role baru dari form

    // Update data users termasuk ROLE
    $stmt_update = mysqli_prepare($koneksi, "UPDATE users SET username = ?, email = ?, status = ?, role = ? WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt_update, "ssssi", $username, $email, $status, $new_role, $id_user);
    $update_success = mysqli_stmt_execute($stmt_update);

    if (!$update_success) {
        die("Gagal update user: " . mysqli_error($koneksi));
    }

    // Jika role berubah, bersihkan relasi lama
    if ($old_role != $new_role) {
        if ($old_role == 'guru') {
            $stmt_clear = mysqli_prepare($koneksi, "UPDATE guru SET id_user = NULL WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt_clear, "i", $id_user);
            mysqli_stmt_execute($stmt_clear);
        } elseif ($old_role == 'siswa') {
            $stmt_clear = mysqli_prepare($koneksi, "UPDATE siswa SET id_user = NULL WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt_clear, "i", $id_user);
            mysqli_stmt_execute($stmt_clear);
        }
    }

    // Handle penautan guru atau siswa untuk ROLE BARU
    if ($new_role === 'guru') {
        // Hapus tautan lama dengan prepared statement supaya aman (bersihkan jika ada duplikat)
        $stmt_clear = mysqli_prepare($koneksi, "UPDATE guru SET id_user = NULL WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_clear, "i", $id_user);
        mysqli_stmt_execute($stmt_clear);

        if (!empty($id_guru)) {
            // Tautkan guru baru
            $stmt_guru = mysqli_prepare($koneksi, "UPDATE guru SET id_user = ? WHERE id_guru = ?");
            mysqli_stmt_bind_param($stmt_guru, "ii", $id_user, $id_guru);
            mysqli_stmt_execute($stmt_guru);
        }
    } elseif ($new_role === 'siswa') {
        // Hapus tautan lama dengan prepared statement supaya aman
        $stmt_clear = mysqli_prepare($koneksi, "UPDATE siswa SET id_user = NULL WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt_clear, "i", $id_user);
        mysqli_stmt_execute($stmt_clear);

        if (!empty($id_siswa)) {
            // Tautkan siswa baru
            $stmt_siswa = mysqli_prepare($koneksi, "UPDATE siswa SET id_user = ? WHERE id_siswa = ?");
            mysqli_stmt_bind_param($stmt_siswa, "ii", $id_user, $id_siswa);
            mysqli_stmt_execute($stmt_siswa);
        }
    }

    // Redirect sukses
    header("Location: users.php?status=sukses_edit");
    exit();
} else {
    header("Location: users.php");
    exit();
}
