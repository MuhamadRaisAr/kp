<?php
require_once 'includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

// Ambil ID dari URL
$id_mengajar = $_GET['id'];
if (!$id_mengajar) { header('Location: mengajar.php'); exit(); }

// Ambil data penugasan yang akan diedit
$stmt_mengajar = mysqli_prepare($koneksi, "SELECT * FROM mengajar WHERE id_mengajar = ?");
mysqli_stmt_bind_param($stmt_mengajar, "i", $id_mengajar);
mysqli_stmt_execute($stmt_mengajar);
$result_mengajar = mysqli_stmt_get_result($stmt_mengajar);
$data_mengajar = mysqli_fetch_assoc($result_mengajar);

// Ambil semua data untuk dropdown
$result_guru = mysqli_query($koneksi, "SELECT * FROM guru ORDER BY nama_lengkap");
$result_mapel = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran ORDER BY nama_mapel");
$result_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas");
$result_tahun = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC");

$judul_halaman = "Edit Penugasan Mengajar";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Penugasan Mengajar</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="mengajar.php">Penugasan Mengajar</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i>Formulir Edit Penugasan</div>
        <div class="card-body">
            <form action="proses_edit_mengajar.php" method="POST">
                <input type="hidden" name="id_mengajar" value="<?php echo $data_mengajar['id_mengajar']; ?>">

                <div class="mb-3">
                    <label for="id_guru" class="form-label">Guru</label>
                    <select class="form-select" name="id_guru" required>
                        <?php while ($row = mysqli_fetch_assoc($result_guru)) {
                            $selected = ($row['id_guru'] == $data_mengajar['id_guru']) ? 'selected' : '';
                            echo "<option value='{$row['id_guru']}' $selected>" . htmlspecialchars($row['nama_lengkap']) . "</option>";
                        } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_mapel" class="form-label">Mata Pelajaran</label>
                    <select class="form-select" name="id_mapel" required>
                        <?php while ($row = mysqli_fetch_assoc($result_mapel)) {
                            $selected = ($row['id_mapel'] == $data_mengajar['id_mapel']) ? 'selected' : '';
                            echo "<option value='{$row['id_mapel']}' $selected>" . htmlspecialchars($row['nama_mapel']) . "</option>";
                        } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_kelas" class="form-label">Kelas</label>
                    <select class="form-select" name="id_kelas" required>
                        <?php while ($row = mysqli_fetch_assoc($result_kelas)) {
                            $selected = ($row['id_kelas'] == $data_mengajar['id_kelas']) ? 'selected' : '';
                            echo "<option value='{$row['id_kelas']}' $selected>" . htmlspecialchars($row['nama_kelas']) . "</option>";
                        } ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_tahun_ajaran" class="form-label">Tahun Ajaran</label>
                    <select class="form-select" name="id_tahun_ajaran" required>
                        <?php while ($row = mysqli_fetch_assoc($result_tahun)) {
                            $selected = ($row['id_tahun_ajaran'] == $data_mengajar['id_tahun_ajaran']) ? 'selected' : '';
                            echo "<option value='{$row['id_tahun_ajaran']}' $selected>" . htmlspecialchars($row['tahun_ajaran']) . " - " . htmlspecialchars($row['semester']) . "</option>";
                        } ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
                <a href="mengajar.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>