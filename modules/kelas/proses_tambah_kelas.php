<?php
// Memanggil file-file yang dibutuhkan untuk keamanan dan koneksi database
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Memastikan skrip ini hanya dieksekusi jika ada data yang dikirim melalui metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Mengambil data dari formulir yang dikirim
    $nama_kelas = $_POST['nama_kelas'];
    $tingkat = $_POST['tingkat'];
    $id_jurusan = $_POST['id_jurusan'];
    $id_guru_wali_kelas = $_POST['id_guru_wali_kelas'];

    // ======================================================
    // BAGIAN YANG DIPERBAIKI ADA DI BARIS INI
    // Pastikan ada 4 kolom dan 4 tanda tanya
    // ======================================================
    $query = "INSERT INTO kelas (nama_kelas, tingkat, id_jurusan, id_guru_wali_kelas) VALUES (?, ?, ?, ?)";
    
    // Mempersiapkan statement
    $stmt = mysqli_prepare($koneksi, $query);
    
    // Mengikat 4 parameter ke statement. 'ssii' -> string, string, integer, integer.
    mysqli_stmt_bind_param($stmt, "ssii", $nama_kelas, $tingkat, $id_jurusan, $id_guru_wali_kelas);
    
    // Mengeksekusi statement
    if (mysqli_stmt_execute($stmt)) {
        // Jika eksekusi berhasil, arahkan kembali ke halaman kelas dengan status sukses
        header("Location: kelas.php?status=sukses_tambah");
    } else {
        // Jika eksekusi gagal, arahkan kembali ke halaman kelas dengan status gagal
        header("Location: kelas.php?status=gagal_tambah");
    }
    
    // Menutup statement
    mysqli_stmt_close($stmt);

} else {
    // Jika file ini diakses secara langsung tanpa melalui metode POST, arahkan kembali ke halaman kelas
    header("Location: kelas.php");
}

// Menutup koneksi database
mysqli_close($koneksi);
exit(); // Menghentikan eksekusi skrip lebih lanjut
?>

