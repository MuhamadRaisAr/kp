<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// Cek ID kelas dari URL
if (!isset($_GET['id'])) {
    header("Location: kelas.php");
    exit();
}
$id_kelas = $_GET['id'];

// Query untuk mengambil data kelas yang akan diedit
$sql_kelas = "SELECT * FROM kelas WHERE id_kelas = ?";
$stmt = mysqli_prepare($koneksi, $sql_kelas);
mysqli_stmt_bind_param($stmt, "i", $id_kelas);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data_kelas = mysqli_fetch_assoc($result);

if (!$data_kelas) {
    echo "Data kelas tidak ditemukan.";
    exit();
}

// Query untuk mengambil semua jurusan (untuk dropdown)
$sql_jurusan = "SELECT id_jurusan, nama_jurusan FROM jurusan ORDER BY nama_jurusan ASC";
$result_jurusan = mysqli_query($koneksi, $sql_jurusan);

// Query untuk mengambil semua guru (untuk dropdown)
$sql_guru = "SELECT id_guru, nama_lengkap FROM guru ORDER BY nama_lengkap ASC";
$result_guru = mysqli_query($koneksi, $sql_guru);

$judul_halaman = "Edit Data Kelas";
require_once '../../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Data Kelas</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="kelas.php">Data Kelas</a></li>
        <li class="breadcrumb-item active">Edit Kelas</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Formulir Edit Kelas
        </div>
        <div class="card-body">
            <form action="proses_edit_kelas.php" method="POST">
                <input type="hidden" name="id_kelas" value="<?php echo $data_kelas['id_kelas']; ?>">

                <div class="mb-3">
                    <label for="nama_kelas" class="form-label">Nama Kelas</label>
                    <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" value="<?php echo htmlspecialchars($data_kelas['nama_kelas']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="tingkat" class="form-label">Tingkat</label>
                    <select class="form-select" id="tingkat" name="tingkat" required>
                        <option value="10" <?php if($data_kelas['tingkat'] == '10') echo 'selected'; ?>>10</option>
                        <option value="11" <?php if($data_kelas['tingkat'] == '11') echo 'selected'; ?>>11</option>
                        <option value="12" <?php if($data_kelas['tingkat'] == '12') echo 'selected'; ?>>12</option>
                        <option value="13" <?php if($data_kelas['tingkat'] == '13') echo 'selected'; ?>>13</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_jurusan" class="form-label">Jurusan</label>
                    <select class="form-select" id="id_jurusan" name="id_jurusan" required>
                        <option value="">-- Pilih Jurusan --</option>
                        <?php
                        while ($row = mysqli_fetch_assoc($result_jurusan)) {
                            $selected = ($row['id_jurusan'] == $data_kelas['id_jurusan']) ? 'selected' : '';
                            echo "<option value='{$row['id_jurusan']}' $selected>" . htmlspecialchars($row['nama_jurusan']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="id_guru_wali_kelas" class="form-label">Wali Kelas</label>
                    <select class="form-select" id="id_guru_wali_kelas" name="id_guru_wali_kelas" required>
                        <option value="">-- Pilih Wali Kelas --</option>
                        <?php
                        while ($row = mysqli_fetch_assoc($result_guru)) {
                            $selected = ($row['id_guru'] == $data_kelas['id_guru_wali_kelas']) ? 'selected' : '';
                            echo "<option value='{$row['id_guru']}' $selected>" . htmlspecialchars($row['nama_lengkap']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Update Data</button>
                <a href="kelas.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>
