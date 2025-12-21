<?php
// Memanggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

// Memastikan skrip dieksekusi dari metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Mengambil data dari form
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    // Mengambil id_user dari session yang sedang aktif
    $id_user = $_SESSION['id_user'];

    // --- VALIDASI ---
    // 1. Cek apakah password kosong
    if (empty($password_baru) || empty($konfirmasi_password)) {
        header("Location: profil.php?status=gagal_password_kosong");
        exit();
    }

    // 2. Cek apakah password baru dan konfirmasinya cocok
    if ($password_baru !== $konfirmasi_password) {
        header("Location: profil.php?status=gagal_password_tidakcocok");
        exit();
    }
    
    // 3. (Opsional) Cek kekuatan password, misalnya minimal 6 karakter
    if (strlen($password_baru) < 6) {
        header("Location: profil.php?status=gagal_password_pendek");
        exit();
    }

    // --- PROSES UPDATE ---
    // Jika semua validasi lolos, enkripsi password baru (WAJIB!)
    $password_hashed = password_hash($password_baru, PASSWORD_DEFAULT);

    // Siapkan query UPDATE dengan prepared statement untuk keamanan
    $query = "UPDATE users SET password = ? WHERE id_user = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    // Binding parameter ('s' untuk string, 'i' untuk integer)
    mysqli_stmt_bind_param($stmt, "si", $password_hashed, $id_user);

    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, arahkan kembali ke profil dengan pesan sukses
        header("Location: profil.php?status=sukses_password");
    } else {
        // Jika gagal, arahkan kembali dengan pesan error
        header("Location: profil.php?status=gagal_db");
    }

    // Tutup statement dan koneksi
    mysqli_stmt_close($stmt);
    mysqli_close($koneksi);
    exit();

} else {
    // Jika file diakses langsung, tendang ke halaman dashboard
    header("Location: dashboard.php");
    exit();
}
?>