<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if ($_SESSION['role'] != 'guru') {
    header("Location: dashboard.php");
    exit();
}

$id_nilai = $_GET['id'] ?? 0;

// Ambil data nilai
$query = "SELECT n.*, s.nama_siswa 
          FROM nilai n 
          JOIN siswa s ON n.id_siswa = s.id_siswa
          JOIN mengajar m ON n.id_mengajar = m.id_mengajar
          WHERE n.id_nilai = '$id_nilai' AND m.id_guru = '" . $_SESSION['id_guru'] . "'";
$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tidak ditemukan.");
}

// Update jika submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nilai = intval($_POST['nilai']);
    mysqli_query($koneksi, "UPDATE nilai SET nilai = '$nilai' WHERE id_nilai = '$id_nilai'");
    header("Location: nilai_input.php?id_mengajar=" . $data['id_mengajar']);
    exit();
}
?>

<form method="POST">
    <h3>Edit Nilai <?php echo $data['nama_siswa']; ?></h3>
    <input type="number" name="nilai" value="<?php echo $data['nilai']; ?>" min="0" max="100" required>
    <button type="submit">Update</button>
</form>
