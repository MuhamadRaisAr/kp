<?php
// Memanggil file-file yang dibutuhkan
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

// =================================================================
// BAGIAN YANG HILANG ADA DI SINI
// Mengambil data user yang sedang login untuk ditampilkan di form
// =================================================================
$id_user = $_SESSION['id_user'];

// Query untuk mengambil data user yang akan diedit
$sql_user = "SELECT username, email FROM users WHERE id_user = ?";
$stmt = mysqli_prepare($koneksi, $sql_user);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data_user = mysqli_fetch_assoc($result);

// Jika karena alasan tertentu data tidak ditemukan, hentikan script
if (!$data_user) {
    die("Data pengguna tidak ditemukan."); // die() akan menghentikan eksekusi
}

// Mengatur judul halaman
$judul_halaman = "Edit Profil";
// Memanggil header setelah semua logika PHP selesai
require_once '../../includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Profil</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="profil.php">Profil</a></li>
        <li class="breadcrumb-item active">Edit Profil</li>
    </ol>
    
    <?php
    // Notifikasi jika ada error dari proses edit
    if (isset($_GET['status']) && $_GET['status'] == 'gagal_username_ada') {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Gagal!</strong> Username atau Email sudah digunakan oleh akun lain.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
    ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Formulir Edit Profil
        </div>
        <div class="card-body">
            <form action="proses_edit_profil.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($data_user['username']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($data_user['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="foto_profil" class="form-label">Ganti Foto Profil</label>
                    <input class="form-control" type="file" id="foto_profil" name="foto_profil">
                    <div class="form-text">Kosongkan jika tidak ingin mengganti foto. Max: 1MB (JPG, PNG).</div>
                </div>

                <button type="submit" class="btn btn-primary">Update Profil</button>
                <a href="profil.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once '../../includes/footer.php';
?>
