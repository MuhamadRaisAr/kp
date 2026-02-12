<?php
/**
 * API Endpoint untuk mengambil daftar mata pelajaran (mapel) secara dinamis.
 * File ini dipanggil oleh JavaScript (AJAX) dan merespon dengan data JSON.
 */

// Mulai session untuk membaca data login pengguna.
session_start();

// Atur header agar browser tahu bahwa responnya adalah format JSON.
header('Content-Type: application/json');

// Panggil file-file yang dibutuhkan.
// auth_check.php diasumsikan akan menghentikan skrip jika pengguna belum login.
require_once '../../includes/koneksi.php';
require_once '../../includes/auth_check.php';

// --- 1. Validasi Input & Session ---
// Jika id_kelas tidak dikirim, atau role pengguna tidak ada, kirim array kosong dan hentikan skrip.
if (!isset($_GET['id_kelas']) || empty($_GET['id_kelas']) || !isset($_SESSION['role'])) {
    echo json_encode([]);
    exit();
}

// --- 2. Persiapan Variabel ---
// Ambil data dari input dan session, pastikan tipenya benar (integer) untuk keamanan.
$id_kelas = (int)$_GET['id_kelas'];
$role = $_SESSION['role'];
$id_guru_login = isset($_SESSION['id_guru']) ? (int)$_SESSION['id_guru'] : null;

// Siapkan variabel untuk menampung hasil query.
$mapel_list = [];
$stmt = null;

// --- 3. Logika Pengambilan Data Berdasarkan Role ---
if ($role == 'admin') {
    // Jika user adalah admin, ambil SEMUA mapel yang ada di kelas tersebut.
    $query = "SELECT DISTINCT mp.id_mapel, mp.nama_mapel 
              FROM mata_pelajaran mp 
              JOIN mengajar m ON mp.id_mapel = m.id_mapel 
              WHERE m.id_kelas = ?";
    
    $param_types = "i";
    $params = [$id_kelas];

    if (isset($_GET['id_tahun_ajaran']) && !empty($_GET['id_tahun_ajaran'])) {
        $query .= " AND m.id_tahun_ajaran = ?";
        $param_types .= "i";
        $params[] = (int)$_GET['id_tahun_ajaran'];
    }
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);

} elseif ($role == 'guru' && $id_guru_login) {
    // Cek apakah guru ini adalah Wali Kelas dari kelas yang dipilih
    $is_wali_kelas_of_target = false;
    $stmt_check = mysqli_prepare($koneksi, "SELECT 1 FROM kelas WHERE id_kelas = ? AND id_guru_wali_kelas = ?");
    mysqli_stmt_bind_param($stmt_check, "ii", $id_kelas, $id_guru_login);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);
    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $is_wali_kelas_of_target = true;
    }
    mysqli_stmt_close($stmt_check);

    if ($is_wali_kelas_of_target) {
        // Jika Wali Kelas, ambil SEMUA mapel di kelas tersebut (Sama seperti logic Admin)
        $query = "SELECT DISTINCT mp.id_mapel, mp.nama_mapel 
                  FROM mata_pelajaran mp 
                  JOIN mengajar m ON mp.id_mapel = m.id_mapel 
                  WHERE m.id_kelas = ?";
        
        $param_types = "i";
        $params = [$id_kelas];

        if (isset($_GET['id_tahun_ajaran']) && !empty($_GET['id_tahun_ajaran'])) {
            $query .= " AND m.id_tahun_ajaran = ?";
            $param_types .= "i";
            $params[] = (int)$_GET['id_tahun_ajaran'];
        }
        
    } else {
        // Jika BUKAN Wali Kelas (Guru Biasa di kelas ini), hanya ambil mapel yang DIA AJAR
        $query = "SELECT DISTINCT mp.id_mapel, mp.nama_mapel 
                  FROM mata_pelajaran mp 
                  JOIN mengajar m ON mp.id_mapel = m.id_mapel 
                  WHERE m.id_guru = ? AND m.id_kelas = ?";
                  
        $param_types = "ii";
        $params = [$id_guru_login, $id_kelas];
    
        if (isset($_GET['id_tahun_ajaran']) && !empty($_GET['id_tahun_ajaran'])) {
            $query .= " AND m.id_tahun_ajaran = ?";
            $param_types .= "i";
            $params[] = (int)$_GET['id_tahun_ajaran'];
        }
    }

    $stmt = mysqli_prepare($koneksi, $query);
    // Gunakan variadic function ...$params untuk bind_param dinamis
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}

// --- 4. Eksekusi Query & Ambil Hasil ---
// Cek apakah statement berhasil disiapkan sebelum dieksekusi.
if ($stmt) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        // Ambil setiap baris hasil dan masukkan ke dalam array.
        while ($row = mysqli_fetch_assoc($result)) {
            $mapel_list[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// --- 5. Tutup Koneksi dan Kirim Respon JSON ---
mysqli_close($koneksi);
// Ubah array PHP menjadi format JSON dan kirimkan sebagai output.
echo json_encode($mapel_list);
?>
