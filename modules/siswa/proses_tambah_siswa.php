<?php
// Panggil file auth_check.php dan koneksi.php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Pastikan request adalah metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil data dari form
    $nis = $_POST['nis'];
    $nisn = $_POST['nisn'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = $_POST['jenis_kelamin'];
    $alamat = $_POST['alamat'];
    $id_kelas = $_POST['id_kelas'];

    // Gunakan prepared statement untuk keamanan
    $query = "INSERT INTO siswa (nis, nisn, nama_lengkap, tanggal_lahir, jenis_kelamin, alamat, id_kelas) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($koneksi, $query);
    
    // Binding parameter ke statement
    mysqli_stmt_bind_param($stmt, "ssssssi", $nis, $nisn, $nama_lengkap, $tanggal_lahir, $jenis_kelamin, $alamat, $id_kelas);
    
    // Eksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika berhasil, redirect ke halaman siswa dengan pesan sukses
        header("Location: siswa.php?status=sukses_tambah");
    } else {
        // Jika gagal, redirect dengan pesan error
        header("Location: siswa.php?status=gagal_tambah");
    }
    
    // Tutup statement
    mysqli_stmt_close($stmt);

} else {
    // Jika file diakses langsung, redirect ke halaman siswa
    header("Location: siswa.php");
}

// Tutup koneksi
mysqli_close($koneksi);
exit();
?>
