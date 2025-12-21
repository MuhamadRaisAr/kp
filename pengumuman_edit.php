<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Edit Pengumuman";

// Pastikan yang login adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk Admin.</div></div>';
    require_once 'includes/footer.php';
    exit();
}

// 1. Ambil ID Pengumuman dari URL
$id_pengumuman = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_pengumuman <= 0) {
    header("Location: pengumuman.php?status=gagal_edit&msg=" . urlencode("ID Pengumuman tidak valid."));
    exit();
}

// 2. Ambil data pengumuman yang akan diedit
$stmt_data = mysqli_prepare($koneksi, "SELECT * FROM pengumuman WHERE id_pengumuman = ? AND is_aktif = 1"); // Hanya bisa edit yg aktif
mysqli_stmt_bind_param($stmt_data, "i", $id_pengumuman);
mysqli_stmt_execute($stmt_data);
$result_data = mysqli_stmt_get_result($stmt_data);

if (mysqli_num_rows($result_data) == 0) {
    header("Location: pengumuman.php?status=gagal_edit&msg=" . urlencode("Pengumuman tidak ditemukan atau sudah tidak aktif."));
    exit();
}
$data = mysqli_fetch_assoc($result_data);
mysqli_stmt_close($stmt_data);

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Pengumuman</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="pengumuman.php">Pengumuman</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>

     <?php if (isset($_GET['status']) && str_starts_with($_GET['status'], 'gagal_')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Gagal!</strong> <?php echo isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'Terjadi kesalahan.'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Formulir Edit Pengumuman
        </div>
        <div class="card-body">
            <form action="proses_pengumuman_edit.php" method="POST">
                <input type="hidden" name="id_pengumuman" value="<?php echo $id_pengumuman; ?>">

                <div class="mb-3">
                    <label for="judul" class="form-label">Judul Pengumuman</label>
                    <input type="text" class="form-control" id="judul" name="judul" value="<?php echo htmlspecialchars($data['judul']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="isi" class="form-label">Isi Pengumuman</label>
                    <textarea class="form-control" id="isi" name="isi" rows="5" required><?php echo htmlspecialchars($data['isi']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="target_role" class="form-label">Tampilkan Untuk</label>
                    <select class="form-select" id="target_role" name="target_role" required>
                        <option value="semua" <?php echo ($data['target_role'] == 'semua') ? 'selected' : ''; ?>>Semua Pengguna</option>
                        <option value="guru" <?php echo ($data['target_role'] == 'guru') ? 'selected' : ''; ?>>Hanya Guru</option>
                        <option value="siswa" <?php echo ($data['target_role'] == 'siswa') ? 'selected' : ''; ?>>Hanya Siswa</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="pengumuman.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>