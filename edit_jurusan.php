<?php
require_once 'includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

// Cek ID dari URL
if (!isset($_GET['id'])) {
    header("Location: jurusan.php");
    exit();
}
$id_jurusan = $_GET['id'];

// Ambil data jurusan dari database
$sql = "SELECT * FROM jurusan WHERE id_jurusan = ?";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_jurusan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "Data jurusan tidak ditemukan.";
    exit();
}

$judul_halaman = "Edit Data Jurusan";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Data Jurusan</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="jurusan.php">Data Jurusan</a></li>
        <li class="breadcrumb-item active">Edit Jurusan</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i>Formulir Edit Jurusan</div>
        <div class="card-body">
            <form action="proses_edit_jurusan.php" method="POST">
                <input type="hidden" name="id_jurusan" value="<?php echo $data['id_jurusan']; ?>">

                <div class="mb-3">
                    <label for="kode_jurusan" class="form-label">Kode Jurusan</label>
                    <input type="text" class="form-control" id="kode_jurusan" name="kode_jurusan" value="<?php echo htmlspecialchars($data['kode_jurusan']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nama_jurusan" class="form-label">Nama Jurusan</label>
                    <input type="text" class="form-control" id="nama_jurusan" name="nama_jurusan" value="<?php echo htmlspecialchars($data['nama_jurusan']); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
                <a href="jurusan.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>