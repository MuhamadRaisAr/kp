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
require_once 'includes/koneksi.php';
require_once 'includes/auth_check.php';

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
    
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_kelas);

} elseif ($role == 'guru' && $id_guru_login) {
    // Jika user adalah guru, hanya ambil mapel yang DIA AJAR di kelas tersebut.
    $query = "SELECT DISTINCT mp.id_mapel, mp.nama_mapel 
              FROM mata_pelajaran mp 
              JOIN mengajar m ON mp.id_mapel = m.id_mapel 
              WHERE m.id_guru = ? AND m.id_kelas = ?";
              
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ii", $id_guru_login, $id_kelas);
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