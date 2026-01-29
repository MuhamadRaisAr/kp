<?php
session_start();

// Ambil username sebelum session dihancurkan
$last_user = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Arahkan ke halaman login
header("Location: login.php?logout=1&u=" . urlencode($last_user));
exit();
?>