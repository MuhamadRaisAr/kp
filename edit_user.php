<?php
require_once 'includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }

require_once 'includes/header.php';
require_once 'includes/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id_user_edit = (int) $_GET['id'];

// Ambil data user
$stmt_user = mysqli_prepare($koneksi, "SELECT * FROM users WHERE id_user = ?");
mysqli_stmt_bind_param($stmt_user, "i", $id_user_edit);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$data_user = mysqli_fetch_assoc($result_user);
if (!$data_user) {
    die("User tidak ditemukan.");
}

$role_user = $data_user['role'];

// Ambil id_guru atau id_siswa yang terkait dengan user (jika ada)
$id_guru_terkait = null;
$id_siswa_terkait = null;

if ($role_user === 'guru') {
    $stmt_guru_terkait = mysqli_prepare($koneksi, "SELECT id_guru FROM guru WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt_guru_terkait, "i", $id_user_edit);
    mysqli_stmt_execute($stmt_guru_terkait);
    $result_guru_terkait = mysqli_stmt_get_result($stmt_guru_terkait);
    $row_guru_terkait = mysqli_fetch_assoc($result_guru_terkait);
    if ($row_guru_terkait) {
        $id_guru_terkait = $row_guru_terkait['id_guru'];
    }
} elseif ($role_user === 'siswa') {
    $stmt_siswa_terkait = mysqli_prepare($koneksi, "SELECT id_siswa FROM siswa WHERE id_user = ?");
    mysqli_stmt_bind_param($stmt_siswa_terkait, "i", $id_user_edit);
    mysqli_stmt_execute($stmt_siswa_terkait);
    $result_siswa_terkait = mysqli_stmt_get_result($stmt_siswa_terkait);
    $row_siswa_terkait = mysqli_fetch_assoc($result_siswa_terkait);
    if ($row_siswa_terkait) {
        $id_siswa_terkait = $row_siswa_terkait['id_siswa'];
    }
}

// Ambil daftar guru/siswa yang bisa ditautkan
$guru_list = [];
$siswa_list = [];

if ($role_user === 'guru') {
    $stmt_guru = mysqli_prepare($koneksi, "SELECT id_guru, nama_lengkap FROM guru WHERE id_user IS NULL OR id_user = ?");
    mysqli_stmt_bind_param($stmt_guru, "i", $id_user_edit);
    mysqli_stmt_execute($stmt_guru);
    $result_guru = mysqli_stmt_get_result($stmt_guru);
    while ($guru = mysqli_fetch_assoc($result_guru)) {
        $guru_list[] = $guru;
    }
} elseif ($role_user === 'siswa') {
    $stmt_siswa = mysqli_prepare($koneksi, "SELECT id_siswa, nama_lengkap FROM siswa WHERE id_user IS NULL OR id_user = ?");
    mysqli_stmt_bind_param($stmt_siswa, "i", $id_user_edit);
    mysqli_stmt_execute($stmt_siswa);
    $result_siswa = mysqli_stmt_get_result($stmt_siswa);
    while ($siswa = mysqli_fetch_assoc($result_siswa)) {
        $siswa_list[] = $siswa;
    }
}

$judul_halaman = "Edit User";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit User</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="users.php">Manajemen User</a></li>
        <li class="breadcrumb-item active">Edit User</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i>Formulir Edit User</div>
        <div class="card-body">
            <form action="proses_edit_user.php" method="POST">
                <input type="hidden" name="id_user" value="<?= $data_user['id_user']; ?>">

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($data_user['username']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($data_user['email']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role" required disabled>
                        <option value="admin" <?= $role_user == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="guru" <?= $role_user == 'guru' ? 'selected' : ''; ?>>Guru</option>
                        <option value="siswa" <?= $role_user == 'siswa' ? 'selected' : ''; ?>>Siswa</option>
                    </select>
                    <div class="form-text text-muted">Role tidak bisa diubah dari halaman ini.</div>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="aktif" <?= $data_user['status'] == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="nonaktif" <?= $data_user['status'] == 'nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>

                <?php if ($role_user === 'guru'): ?>
                    <div class="mb-3">
                        <label for="id_guru" class="form-label">Tautkan ke Guru</label>
                        <select class="form-select" id="id_guru" name="id_guru">
                            <option value="">-- Tidak ditautkan --</option>
                            <?php foreach ($guru_list as $guru): ?>
                                <option value="<?= $guru['id_guru']; ?>" <?= ($guru['id_guru'] == $id_guru_terkait) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($guru['nama_lengkap']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($role_user === 'siswa'): ?>
                    <div class="mb-3">
                        <label for="id_siswa" class="form-label">Tautkan ke Siswa</label>
                        <select class="form-select" id="id_siswa" name="id_siswa">
                            <option value="">-- Tidak ditautkan --</option>
                            <?php foreach ($siswa_list as $siswa): ?>
                                <option value="<?= $siswa['id_siswa']; ?>" <?= ($siswa['id_siswa'] == $id_siswa_terkait) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($siswa['nama_lengkap']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-text mb-3">Catatan: Pengubahan password dilakukan oleh pengguna sendiri melalui halaman profil.</div>

                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="users.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
