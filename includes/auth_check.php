<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cek apakah user sudah login atau belum
if (!isset($_SESSION['id_user'])) {
    // Jika belum login, tendang ke halaman login
    header("Location: /sistem-penilaian/login.php");
    exit();
}

// ==========================================================
// INI BAGIAN PALING PENTING YANG HILANG DARI KODEMU
// Standarkan role 'murid' menjadi 'siswa' di SETIAP halaman
// ==========================================================
if (isset($_SESSION['role']) && $_SESSION['role'] == 'murid') {
    $_SESSION['role'] = 'siswa';
}
// ==========================================================
?>