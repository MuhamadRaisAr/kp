<?php
require_once __DIR__ . '/config.php';

$koneksi = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}