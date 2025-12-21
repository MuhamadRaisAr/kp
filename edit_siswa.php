<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

// Cek ID siswa dari URL
if (!isset($_GET['id'])) {
    header("Location: siswa.php");
    exit();
}
$id_siswa = $_GET['id'];

// Query untuk mengambil data siswa yang akan diedit
$sql_siswa = "SELECT * FROM siswa WHERE id_siswa = ?";
$stmt_siswa = mysqli_prepare($koneksi, $sql_siswa);
mysqli_stmt_bind_param($stmt_siswa, "i", $id_siswa);
mysqli_stmt_execute($stmt_siswa);
$result_siswa = mysqli_stmt_get_result($stmt_siswa);
$data_siswa = mysqli_fetch_assoc($result_siswa);

if (!$data_siswa) {
    echo "Data siswa tidak ditemukan.";
    exit();
}

// Query untuk mengambil data kelas
$sql_kelas = "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC";
$result_kelas = mysqli_query($koneksi, $sql_kelas);

$judul_halaman = "Edit Data Siswa";
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Edit Data Siswa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="siswa.php">Data Siswa</a></li>
        <li class="breadcrumb-item active">Edit Siswa</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Formulir Edit Siswa
        </div>
        <div class="card-body">
            
            <form action="proses_edit_siswa.php" method="POST">
                <input type="hidden" name="id_siswa" value="<?php echo $data_siswa['id_siswa']; ?>">

                <div class="mb-3">
                    <label for="nis" class="form-label">NIS</label>
                    <input type="text" class="form-control" id="nis" name="nis" value="<?php echo htmlspecialchars($data_siswa['nis']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nisn" class="form-label">NISN</label>
                    <input type="text" class="form-control" id="nisn" name="nisn" value="<?php echo htmlspecialchars($data_siswa['nisn']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($data_siswa['nama_lengkap']); ?>" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir" value="<?php echo $data_siswa['tanggal_lahir']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                        <select class="form-select" id="jenis_kelamin" name="jenis_kelamin" required>
                            <option value="Laki-laki" <?php if($data_siswa['jenis_kelamin'] == 'Laki-laki') echo 'selected'; ?>>Laki-laki</option>
                            <option value="Perempuan" <?php if($data_siswa['jenis_kelamin'] == 'Perempuan') echo 'selected'; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="id_kelas" class="form-label">Kelas</label>
                    <select class="form-select" id="id_kelas" name="id_kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php
                        if (mysqli_num_rows($result_kelas) > 0) {
                            while ($row_kelas = mysqli_fetch_assoc($result_kelas)) {
                                $selected = ($row_kelas['id_kelas'] == $data_siswa['id_kelas']) ? 'selected' : '';
                                echo "<option value='" . $row_kelas['id_kelas'] . "' $selected>" . htmlspecialchars($row_kelas['nama_kelas']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat</label>
                    <textarea class="form-control" id="alamat" name="alamat" rows="3"><?php echo htmlspecialchars($data_siswa['alamat']); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
                <a href="siswa.php" class="btn btn-secondary">Batal</a>
            </form>

        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>