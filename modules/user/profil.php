<?php
// Memanggil file-file yang dibutuhkan
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

// Mengatur judul halaman
$judul_halaman = "Profil Pengguna";
// Panggil file header.php
require_once '../../includes/header.php';

// Mengambil informasi user dari session
$id_user = $_SESSION['id_user'];

// Mengambil semua informasi terbaru user dari database
$query_user = "SELECT username, email, role, foto_profil, created_at FROM users WHERE id_user = ?";
$stmt = mysqli_prepare($koneksi, $query_user);
mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

// Menentukan path foto profil (gambar asli atau gambar default)
$foto_path = 'assets/img/default-avatar.png'; // Path default
if (!empty($user_data['foto_profil']) && file_exists('assets/uploads/profiles/' . $user_data['foto_profil'])) {
    $foto_path = 'assets/uploads/profiles/' . $user_data['foto_profil'];
}
?>
<div class="container-fluid px-4">
    <h1 class="mt-4">Profil Pengguna</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Profil</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user me-1"></i>
            Informasi Akun
            <a href="edit_profil.php" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-edit"></i> Edit Profil & Foto
            </a>
        </div>
        <div class="card-body">
            <div class="row align-items-center mb-4">
                <div class="col-md-3 text-center">
                    <img src="<?php echo $foto_path; ?>" class="img-fluid rounded-circle" alt="Foto Profil" style="width: 150px; height: 150px; object-fit: cover;">
                </div>
                <div class="col-md-9">
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                    <p><strong>Peran (Role):</strong> <?php echo htmlspecialchars(ucfirst($user_data['role'])); ?></p>
                    <p><strong>Akun Dibuat:</strong> <?php echo date('d F Y, H:i', strtotime($user_data['created_at'])); ?></p>
                </div>
            </div>
            <hr>
            
            <h5>Ubah Password</h5>
            <form action="proses_ubah_password.php" method="POST">
                 <div class="mb-3">
                    <label for="password_baru" class="form-label">Password Baru</label>
                    <input type="password" class="form-control" name="password_baru" id="password_baru" required>
                </div>
                 <div class="mb-3">
                    <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" class="form-control" name="konfirmasi_password" id="konfirmasi_password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="tampilkan_password">
                    <label class="form-check-label" for="tampilkan_password">Tampilkan Password</label>
                </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once '../../includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('tampilkan_password');
    const passBaru = document.getElementById('password_baru');
    const konfirmasiPass = document.getElementById('konfirmasi_password');

    checkbox.addEventListener('change', function () {
        const type = this.checked ? 'text' : 'password';
        passBaru.type = type;
        konfirmasiPass.type = type;
    });
});
</script>
