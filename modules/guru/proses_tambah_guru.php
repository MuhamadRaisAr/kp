<?php
// Panggil file auth_check.php dan koneksi.php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Pastikan request adalah metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil data dari form
    $nip = $_POST['nip'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $no_telepon = $_POST['no_telepon'];

    // Gunakan prepared statement untuk keamanan
    $query = "INSERT INTO guru (nip, nama_lengkap, email, no_telepon) VALUES (?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($koneksi, $query);
    
    // Binding parameter ke statement ('ssss' berarti semua variabel adalah string)
    mysqli_stmt_bind_param($stmt, "ssss", $nip, $nama_lengkap, $email, $no_telepon);
    
    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect ke halaman guru dengan pesan sukses
        header("Location: guru.php?status=sukses_tambah");
    } else {
        // Jika gagal, redirect dengan pesan error
        header("Location: guru.php?status=gagal_tambah");
    }
    
    // Tutup statement
    mysqli_stmt_close($stmt);

} else {
    // Jika file diakses langsung, redirect ke halaman guru
    header("Location: guru.php");
}

// Tutup koneksi
mysqli_close($koneksi);
exit();
?>
