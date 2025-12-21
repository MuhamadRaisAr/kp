<?php
$host = 'localhost';
$username = 'root';
$password = 'root';
$nama_database = 'db_nilai';
$koneksi = mysqli_connect($host, $username, $password, $nama_database);
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}