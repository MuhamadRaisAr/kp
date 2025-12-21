<?php
require_once 'includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Buat Akun untuk Siswa / Guru";
$tipe_user = $_POST['tipe_user'] ?? '';
?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['id_user_target'], $_POST['password'], $_POST['role'], $_POST['tipe_user'])) {

    $id_user_target = mysqli_real_escape_string($koneksi, $_POST['id_user_target']);
    $password = $_POST['password'];
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    $tipe_user = $_POST['tipe_user'];

    if (empty($id_user_target) || empty($password) || empty($role)) {
        echo "<div class='alert alert-danger'>Semua field harus diisi.</div>";
    } else {
        if ($tipe_user === 'siswa') {
            $query = "SELECT * FROM siswa WHERE id_siswa = '$id_user_target' AND id_user IS NULL";
        } elseif ($tipe_user === 'guru') {
            $query = "SELECT * FROM guru WHERE id_guru = '$id_user_target' AND id_user IS NULL";
        } else {
            echo "<div class='alert alert-danger'>Tipe user tidak valid.</div>";
            exit;
        }

        $result = mysqli_query($koneksi, $query);
        if (!$result) {
            echo "<div class='alert alert-danger'>Query error: " . mysqli_error($koneksi) . "</div>";
            exit;
        }

        if (mysqli_num_rows($result) === 1) {
            $data = mysqli_fetch_assoc($result);
            $username = $tipe_user === 'siswa' ? $data['nis'] : $data['nip'];
            $email = isset($data['email']) && !empty($data['email']) ? $data['email'] : $username . '@mail.com';
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $username_safe = mysqli_real_escape_string($koneksi, $username);
            $email_safe = mysqli_real_escape_string($koneksi, $email);

            $insert_user = "INSERT INTO users (username, email, password, role) 
                            VALUES ('$username_safe', '$email_safe', '$password_hash', '$role')";
            if (mysqli_query($koneksi, $insert_user)) {
                $id_user_baru = mysqli_insert_id($koneksi);

                if ($tipe_user === 'siswa') {
                    $update = "UPDATE siswa SET id_user = '$id_user_baru' WHERE id_siswa = '$id_user_target'";
                } else {
                    $update = "UPDATE guru SET id_user = '$id_user_baru' WHERE id_guru = '$id_user_target'";
                }

                mysqli_query($koneksi, $update);
                echo "<div class='alert alert-success'>Akun berhasil dibuat.</div>";
            } else {
                echo "<div class='alert alert-danger'>Gagal membuat akun: " . mysqli_error($koneksi) . "</div>";
            }
        } else {
            echo "<div class='alert alert-warning'>Data tidak ditemukan atau sudah punya akun.</div>";
        }
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= htmlspecialchars($judul_halaman); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="users.php">Manajemen User</a></li>
        <li class="breadcrumb-item active">Buat Akun</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus-circle me-1"></i>Formulir Akun Baru</div>
        <div class="card-body">
            <form action="" method="POST" id="formAkunBaru">
                <div class="mb-3">
                    <label for="tipe_user" class="form-label">Tipe User</label>
                    <select class="form-select" id="tipe_user" name="tipe_user" required>
                        <option value="">-- Pilih Tipe --</option>
                        <option value="siswa" <?= $tipe_user === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                        <option value="guru" <?= $tipe_user === 'guru' ? 'selected' : '' ?>>Guru</option>
                    </select>
                </div>

                <?php if ($tipe_user === 'siswa' || $tipe_user === 'guru'): ?>
                    <div class="mb-3">
                        <label for="id_user_target" class="form-label">Pilih <?= $tipe_user === 'siswa' ? 'Siswa' : 'Guru'; ?></label>
                        <select class="form-select" id="id_user_target" name="id_user_target" required>
                            <option value="">-- Pilih <?= ucfirst($tipe_user); ?> --</option>
                            <?php
                            $query = $tipe_user === 'siswa' ?
                                "SELECT id_siswa AS id, nama_lengkap, nis AS kode FROM siswa WHERE id_user IS NULL ORDER BY nama_lengkap ASC" :
                                "SELECT id_guru AS id, nama_lengkap, nip AS kode FROM guru WHERE id_user IS NULL ORDER BY nama_lengkap ASC";
                            $result = mysqli_query($koneksi, $query);
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<option value='" . htmlspecialchars($row['id']) . "'>" .
                                     htmlspecialchars($row['nama_lengkap']) . " (" . htmlspecialchars($row['kode']) . ")</option>";
                            }
                            ?>
                        </select>
                        <div class="form-text">Username dan Email akan otomatis diambil dari data <?= htmlspecialchars($tipe_user); ?>.</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="siswa" <?= $tipe_user === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                            <option value="guru" <?= $tipe_user === 'guru' ? 'selected' : '' ?>>Guru</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Buat Akun</button>
                    <a href="users.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('tipe_user').addEventListener('change', function() {
    // Reload form tanpa submit otomatis agar user bisa memilih tipe dulu
    this.form.submit();
});
</script>

<?php require_once 'includes/footer.php'; ?>
