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
    
    // Cek Duplikat NIS
    $cek_nis = mysqli_query($koneksi, "SELECT nis FROM siswa WHERE nis = '$nis'");
    if (mysqli_num_rows($cek_nis) > 0) {
        header("Location: tambah_siswa.php?kelas=" . $id_kelas . "&status=gagal_nis_ada");
        exit();
    }

    // Cek Duplikat NISN
    $cek_nisn = mysqli_query($koneksi, "SELECT nisn FROM siswa WHERE nisn = '$nisn'");
    if (mysqli_num_rows($cek_nisn) > 0) {
        header("Location: tambah_siswa.php?kelas=" . $id_kelas . "&status=gagal_nisn_ada");
        exit();
    }

    // Binding parameter ke statement
    mysqli_stmt_bind_param($stmt, "ssssssi", $nis, $nisn, $nama_lengkap, $tanggal_lahir, $jenis_kelamin, $alamat, $id_kelas);
    
    // Eksekusi statement dengan Try-Catch untuk menangani error SQL (Duplicate entry dll)
    try {
        if (mysqli_stmt_execute($stmt)) {
            // Jika berhasil, redirect ke halaman daftar siswa KELAS TERSEBUT dengan pesan sukses
            header("Location: siswa_list.php?kelas=" . $id_kelas . "&status=sukses_tambah");
        } else {
            throw new Exception("Gagal mengeksekusi query.");
        }
    } catch (mysqli_sql_exception $e) {
        // Tangkap error MySQL (misal Duplicate Entry jika lolos cek manual)
        if ($e->getCode() == 1062) { // Kode error duplicate entry
            header("Location: tambah_siswa.php?kelas=" . $id_kelas . "&status=gagal_duplikat");
        } else {
            header("Location: tambah_siswa.php?kelas=" . $id_kelas . "&status=gagal_tambah");
        }
    } catch (Exception $e) {
        header("Location: tambah_siswa.php?kelas=" . $id_kelas . "&status=gagal_tambah");
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
