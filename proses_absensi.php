<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

// Pastikan skrip dieksekusi dari metode POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Ambil data-data penting dari form
    $id_mengajar = (int)$_POST['id_mengajar'];
    $tanggal = $_POST['tanggal'];
    $daftar_status = $_POST['status'];

    // Menyiapkan query UPSERT (UPDATE or INSERT) yang efisien
    // Ini membutuhkan UNIQUE KEY pada (id_mengajar, id_siswa, tanggal) di tabel `absensi`
    $query = "INSERT INTO absensi (id_mengajar, id_siswa, tanggal, status) VALUES (?, ?, ?, ?)
              ON DUPLICATE KEY UPDATE status = VALUES(status)";
    
    $stmt = mysqli_prepare($koneksi, $query);
    if($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($koneksi->error));
    }

    // Looping untuk setiap siswa yang datanya dikirim dari form
    foreach ($daftar_status as $id_siswa => $status) {
        $id_siswa_int = (int)$id_siswa;
        
        // Hanya proses jika statusnya valid
        if (!empty($status)) {
            // Mengikat parameter baru di setiap iterasi loop
            mysqli_stmt_bind_param($stmt, "iiss", $id_mengajar, $id_siswa_int, $tanggal, $status);
            
            // Eksekusi query untuk setiap siswa
            mysqli_stmt_execute($stmt);
        }
    }

    mysqli_stmt_close($stmt);
    mysqli_close($koneksi);

    // ======================================================
    // BAGIAN REDIRECT YANG DIPERBAIKI
    // ======================================================
    // Ambil data filter yang juga dikirim dari form absensi harian
    $filter_tahun_ajaran = $_POST['filter_tahun_ajaran'];
    $filter_kelas = $_POST['filter_kelas'];
    $filter_mapel = $_POST['filter_mapel'];

    // Buat URL untuk redirect kembali ke halaman absensi harian dengan semua filter tetap terpilih
    // Buat URL untuk redirect kembali ke halaman absensi harian (TAMPILAN AWAL / Reset Filter)
    $redirect_url = "absensi.php?status=sukses_simpan";
    
    header("Location: " . $redirect_url);
    exit();

} else {
    // Jika file ini diakses langsung, arahkan ke halaman dashboard
    header("Location: dashboard.php");
    exit();
}
?>