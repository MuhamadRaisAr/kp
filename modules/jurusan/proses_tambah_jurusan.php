<?php
// Panggil file-file yang dibutuhkan
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Hanya admin yang bisa memproses
if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

// Pastikan request adalah metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil data dari form
    $kode_jurusan = $_POST['kode_jurusan'];
    $nama_jurusan = $_POST['nama_jurusan'];

    // Gunakan prepared statement untuk keamanan
    $query = "INSERT INTO jurusan (kode_jurusan, nama_jurusan) VALUES (?, ?)";
    
    $stmt = mysqli_prepare($koneksi, $query);
    
    // Binding parameter ke statement ('ss' berarti kedua variabel adalah string)
    mysqli_stmt_bind_param($stmt, "ss", $kode_jurusan, $nama_jurusan);
    
    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect ke halaman jurusan dengan pesan sukses
        header("Location: jurusan.php?status=sukses_tambah");
    } else {
        // Jika gagal, redirect dengan pesan error
        header("Location: jurusan.php?status=gagal_tambah");
    }
    
    // Tutup statement
    mysqli_stmt_close($stmt);

} else {
    // Jika file diakses langsung, redirect ke halaman jurusan
    header("Location: jurusan.php");
}

// Tutup koneksi
mysqli_close($koneksi);
exit();
?>
