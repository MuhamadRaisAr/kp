<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

// Cek ID guru dari URL, jika tidak ada, redirect
if (!isset($_GET['id'])) {
    header("Location: guru.php");
    exit();
}
$id_guru = $_GET['id'];

// Query untuk mengambil data guru yang akan diedit
$sql_guru = "SELECT * FROM guru WHERE id_guru = ?";
$stmt = mysqli_prepare($koneksi, $sql_guru);
mysqli_stmt_bind_param($stmt, "i", $id_guru);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data_guru = mysqli_fetch_assoc($result);

// Jika data tidak ditemukan, tampilkan pesan dan hentikan script
if (!$data_guru) {
    echo "Data guru tidak ditemukan.";
    exit();
}

$judul_halaman = "Edit Data Guru";
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Data Guru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="guru.php">Data Guru</a></li>
        <li class="breadcrumb-item active">Edit Guru</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Formulir Edit Guru
        </div>
        <div class="card-body">
            
            <form action="proses_edit_guru.php" method="POST">
                <input type="hidden" name="id_guru" value="<?php echo $data_guru['id_guru']; ?>">

                <div class="mb-3">
                    <label for="nip" class="form-label">NIP</label>
                    <input type="text" class="form-control" id="nip" name="nip" value="<?php echo htmlspecialchars($data_guru['nip']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($data_guru['nama_lengkap']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($data_guru['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="no_telepon" class="form-label">No. Telepon</label>
                    <input type="text" class="form-control" id="no_telepon" name="no_telepon" value="<?php echo htmlspecialchars($data_guru['no_telepon']); ?>">
                </div>

                <button type="submit" class="btn btn-primary">Update Data</button>
                <a href="guru.php" class="btn btn-secondary">Batal</a>
            </form>

        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>