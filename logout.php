<?php
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Arahkan ke halaman login dengan pesan silahkan log in kembali
header("Location: login.php?logout=1");
exit();
?>