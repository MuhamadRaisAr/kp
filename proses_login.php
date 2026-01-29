<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Panggil file koneksi
require_once 'includes/koneksi.php';

// Cek apakah form sudah disubmit melalui metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password_input = $_POST['password'];

    $query = "SELECT id_user, username, password, role, status, id_guru, id_siswa, foto_profil 
              FROM users 
              WHERE username = ?";
    
    $stmt = mysqli_prepare($koneksi, $query);
    if ($stmt === false) {
        header("Location: login.php?error=2"); 
        exit();
    }
    
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifikasi password dan status
        if (password_verify($password_input, $user['password']) && $user['status'] == 'aktif') {
            
            // Standarkan Role
            $role = $user['role'];
            if ($role == 'murid') {
                $role = 'siswa';
            }

            // Simpan ke session
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $role; 
            $_SESSION['id_guru'] = $user['id_guru'];
            $_SESSION['id_siswa'] = $user['id_siswa']; // Ini sekarang akan terisi dengan benar
            $_SESSION['foto_profil'] = $user['foto_profil'];
            $_SESSION['logged_in'] = true; 

            // Update login terakhir
            $update_stmt = mysqli_prepare($koneksi, "UPDATE users SET last_login = NOW() WHERE id_user = ?");
            mysqli_stmt_bind_param($update_stmt, "i", $user['id_user']);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);

            // Arahkan ke dashboard
            header("Location: dashboard.php?login=success");
            exit();
        }
    }

    // Jika user/pass salah atau tidak aktif
    header("Location: login.php?error=1");
    exit();
    
    mysqli_stmt_close($stmt);
    mysqli_close($koneksi);

} else {
    // Jika diakses langsung
    header("Location: login.php");
    exit();
}
?>