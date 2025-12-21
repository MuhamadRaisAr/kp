<?php
session_start();
include 'koneksi.php';

// Pastikan hanya guru yang bisa akses
if (!isset($_SESSION['id_guru'])) {
    header("Location: login.php");
    exit;
}

$id_guru = $_SESSION['id_guru'];

// Ambil data kelas yang diajar guru ini
$query = "SELECT m.nama_mapel, k.nama_kelas, p.tahun_ajaran, p.semester
          FROM penugasan p
          JOIN mapel m ON p.id_mapel = m.id_mapel
          JOIN kelas k ON p.id_kelas = k.id_kelas
          WHERE p.id_guru = '$id_guru'";
$result = mysqli_query($conn, $query);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelas Saya</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Kelas Saya</h2>
    <hr>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>No</th>
                <th>Mata Pelajaran</th>
                <th>Kelas</th>
                <th>Tahun Ajaran</th>
                <th>Semester</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $no = 1;
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>".$no++."</td>
                        <td>".$row['nama_mapel']."</td>
                        <td>".$row['nama_kelas']."</td>
                        <td>".$row['tahun_ajaran']."</td>
                        <td>".$row['semester']."</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='5' class='text-center'>Belum ada kelas yang diajar</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>
</body>
</html>
