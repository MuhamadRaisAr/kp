<?php
require_once '../../includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$id_mapel = $_GET['id'];
$stmt = mysqli_prepare($koneksi, "SELECT * FROM mata_pelajaran WHERE id_mapel = ?");
mysqli_stmt_bind_param($stmt, "i", $id_mapel);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
if (!$data) { die("Data tidak ditemukan."); }

$judul_halaman = "Edit Mata Pelajaran";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Mata Pelajaran</h1>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i>Formulir Edit Mapel</div>
        <div class="card-body">
            <form action="proses_edit_mapel.php" method="POST">
                <input type="hidden" name="id_mapel" value="<?php echo $data['id_mapel']; ?>">
                <div class="mb-3">
                    <label for="kode_mapel" class="form-label">Kode Mapel</label>
                    <input type="text" class="form-control" id="kode_mapel" name="kode_mapel" value="<?php echo htmlspecialchars($data['kode_mapel']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nama_mapel" class="form-label">Nama Mata Pelajaran</label>
                    <input type="text" class="form-control" id="nama_mapel" name="nama_mapel" value="<?php echo htmlspecialchars($data['nama_mapel']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="jenis" class="form-label">Jenis Mapel</label>
                    <select class="form-select" id="jenis" name="jenis" required>
                        <option value="Normatif" <?php if($data['jenis'] == 'Normatif') echo 'selected'; ?>>Normatif</option>
                        <option value="Adaptif" <?php if($data['jenis'] == 'Adaptif') echo 'selected'; ?>>Adaptif</option>
                        <option value="Produktif" <?php if($data['jenis'] == 'Produktif') echo 'selected'; ?>>Produktif</option>
                        <option value="Muatan Lokal" <?php if($data['jenis'] == 'Muatan Lokal') echo 'selected'; ?>>Muatan Lokal</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="mapel.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
