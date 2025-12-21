<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data-data penting dari form
    $id_mengajar = $_POST['id_mengajar'];
    $daftar_nilai = $_POST['nilai'];

    // Ambil juga data filter untuk redirect
    $tahun_ajaran = $_POST['tahun_ajaran'];
    $kelas = $_POST['kelas'];
    $mapel = $_POST['mapel'];

    // Siapkan DUA query: satu untuk simpan/update, satu lagi untuk hapus
    
    // 1. Query untuk SIMPAN atau UPDATE (UPSERT)
    $query_upsert = "INSERT INTO nilai (id_siswa, id_mengajar, jenis_nilai, nilai) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)";
    $stmt_upsert = mysqli_prepare($koneksi, $query_upsert);

    // 2. Query untuk HAPUS
    $query_delete = "DELETE FROM nilai WHERE id_siswa = ? AND id_mengajar = ? AND jenis_nilai = ?";
    $stmt_delete = mysqli_prepare($koneksi, $query_delete);


    // Looping untuk setiap siswa yang nilainya dikirim
    foreach ($daftar_nilai as $id_siswa => $penilaian) {
        // Looping untuk setiap jenis penilaian (Tugas, UTS, UAS, Praktik)
        foreach ($penilaian as $jenis_nilai => $nilai) {
            
            // JIKA KOTAK NILAI DIISI (tidak kosong)
            if ($nilai !== '' && is_numeric($nilai)) {
                // Gunakan statement simpan/update
                mysqli_stmt_bind_param($stmt_upsert, "iisd", $id_siswa, $id_mengajar, $jenis_nilai, $nilai);
                mysqli_stmt_execute($stmt_upsert);
            } 
            // JIKA KOTAK NILAI DIKOSONGKAN
            else {
                // Gunakan statement hapus
                mysqli_stmt_bind_param($stmt_delete, "iis", $id_siswa, $id_mengajar, $jenis_nilai);
                mysqli_stmt_execute($stmt_delete);
            }
        }
    }

    // Tutup semua statement
    mysqli_stmt_close($stmt_upsert);
    mysqli_stmt_close($stmt_delete);
    mysqli_close($koneksi);

    // Buat URL untuk redirect kembali, lengkap dengan filter
    $redirect_url = "nilai.php?tahun_ajaran={$tahun_ajaran}&kelas={$kelas}&mapel={$mapel}&status=sukses_simpan";
    
    header("Location: " . $redirect_url);
    exit();

} else {
    // Jika file diakses langsung, tendang ke halaman nilai
    header("Location: nilai.php");
    exit();
}
?>