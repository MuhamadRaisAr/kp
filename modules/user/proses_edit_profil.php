<?php
// Panggil auth_check.php dulu untuk memastikan session_start() sudah ada
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Pastikan session benar-benar ada (auth_check.php seharusnya sudah memanggil session_start())
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $id_user = $_SESSION['id_user'];
    $nama_file_foto_baru = '';

    // --- PROSES UPLOAD FOTO ---
    // Cek apakah ada file yang diupload dan tidak ada error
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] == 0 && $_FILES['foto_profil']['size'] > 0) {
        $file = $_FILES['foto_profil'];
        $target_dir = "assets/uploads/profiles/";
        
        // Buat nama file unik untuk menghindari konflik
        $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $nama_file_foto_baru = "user_" . $id_user . "_" . time() . "." . $ekstensi;
        $target_file = $target_dir . $nama_file_foto_baru;

        // Validasi tipe dan ukuran file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ekstensi, $allowed_types) && $file['size'] <= 5000000) { // Max 5MB
            
            // Hapus foto lama dari server jika ada
            // Gunakan prepared statement untuk keamanan
            $stmt_old_foto = mysqli_prepare($koneksi, "SELECT foto_profil FROM users WHERE id_user = ?");
            mysqli_stmt_bind_param($stmt_old_foto, "i", $id_user);
            mysqli_stmt_execute($stmt_old_foto);
            $result_old_foto = mysqli_stmt_get_result($stmt_old_foto);
            $data_lama = mysqli_fetch_assoc($result_old_foto);
            
            if (!empty($data_lama['foto_profil']) && file_exists($target_dir . $data_lama['foto_profil'])) {
                unlink($target_dir . $data_lama['foto_profil']);
            }
            mysqli_stmt_close($stmt_old_foto);
            
            // Pindahkan file yang baru diupload ke folder tujuan
            if (!move_uploaded_file($file['tmp_name'], $target_file)) {
                header("Location: edit_profil.php?status=gagal_upload");
                exit();
            }
        } else {
            // Jika validasi gagal
            header("Location: edit_profil.php?status=gagal_upload_tipe_ukuran");
            exit();
        }
    }

    // --- PROSES UPDATE DATABASE ---
    // ... kode validasi username/email duplikat Anda ...
    
    // Siapkan query update.
    if (!empty($nama_file_foto_baru)) {
        // Jika ada foto baru, update kolom foto_profil
        $query_update = "UPDATE users SET username = ?, email = ?, foto_profil = ? WHERE id_user = ?";
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "sssi", $username, $email, $nama_file_foto_baru, $id_user);
    } else {
        // Jika tidak ada foto baru, hanya update username dan email
        $query_update = "UPDATE users SET username = ?, email = ? WHERE id_user = ?";
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "ssi", $username, $email, $id_user);
    }
    
    if (mysqli_stmt_execute($stmt_update)) {
        // Update session username (selalu)
        $_SESSION['username'] = $username;
        
        // ==========================================================
        // INI SOLUSINYA, BRO!
        // Jika nama file foto baru TIDAK KOSONG, update session-nya!
        // ==========================================================
        if (!empty($nama_file_foto_baru)) {
            $_SESSION['foto_profil'] = $nama_file_foto_baru;
        }
        // ==========================================================
        
        header("Location: profil.php?status=sukses_edit_profil");
    } else {
        header("Location: edit_profil.php?status=gagal_db");
    }
    
    mysqli_stmt_close($stmt_update);
    mysqli_close($koneksi);
    exit();
}
?>
