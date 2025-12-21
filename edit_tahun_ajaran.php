<?php
require_once 'includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$id = $_GET['id'];
$stmt = mysqli_prepare($koneksi, "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
if (!$data) { die("Data tidak ditemukan."); }

$judul_halaman = "Edit Tahun Ajaran";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Tahun Ajaran</h1>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i>Formulir Edit Tahun Ajaran</div>
        <div class="card-body">
            <form action="proses_edit_tahun_ajaran.php" method="POST">
                <input type="hidden" name="id_tahun_ajaran" value="<?php echo $data['id_tahun_ajaran']; ?>">
                <div class="mb-3">
                    <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                    <input type="text" class="form-control" id="tahun_ajaran" name="tahun_ajaran" value="<?php echo htmlspecialchars($data['tahun_ajaran']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="semester" class="form-label">Semester</label>
                    <select class="form-select" id="semester" name="semester" required>
                        <option value="Ganjil" <?php if($data['semester'] == 'Ganjil') echo 'selected'; ?>>Ganjil</option>
                        <option value="Genap" <?php if($data['semester'] == 'Genap') echo 'selected'; ?>>Genap</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="status_aktif" class="form-label">Status</label>
                    <select class="form-select" id="status_aktif" name="status_aktif" required>
                        <option value="Tidak Aktif" <?php if($data['status_aktif'] == 'Tidak Aktif') echo 'selected'; ?>>Tidak Aktif</option>
                        <option value="Aktif" <?php if($data['status_aktif'] == 'Aktif') echo 'selected'; ?>>Aktif</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="tahun_ajaran.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>