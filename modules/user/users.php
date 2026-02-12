<?php
require_once '../../includes/auth_check.php';

// Hanya admin yang bisa mengakses halaman ini
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../dashboard.php");
    exit;
}

require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Manajemen User";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= $judul_halaman; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?= $judul_halaman; ?></li>
    </ol>

    <?php
    if (isset($_GET['status'])) {
        // ... (Keep existing alert logic) ...
        $status = $_GET['status'];
        $pesan = '';
        $tipe_alert = 'success';
        switch ($status) {
            case 'sukses_tambah': $pesan = 'User baru berhasil ditambahkan.'; break;
            case 'sukses_edit': $pesan = 'Data user berhasil diperbarui.'; break;
            case 'sukses_hapus': $pesan = 'User berhasil dihapus.'; break;
            case 'gagal_hapus_sendiri': $pesan = '<strong>Gagal!</strong> Anda tidak dapat menghapus akun Anda sendiri.'; $tipe_alert = 'danger'; break;
            default: $pesan = '<strong>Gagal!</strong> Terjadi kesalahan pada proses data.'; $tipe_alert = 'danger'; break;
        }
        echo '<div class="alert alert-' . $tipe_alert . ' alert-dismissible fade show" role="alert">' . $pesan . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }

    // Filter Role
    $selected_role = isset($_GET['role']) ? $_GET['role'] : 'all';
    ?>

    <!-- Role Selection Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <a href="users.php?role=all" class="text-decoration-none">
                <div class="card <?php echo ($selected_role == 'all') ? 'border-primary shadow' : ''; ?> h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-3 text-secondary"></i>
                        <h5 class="card-title text-dark">Semua User</h5>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="users.php?role=admin" class="text-decoration-none">
                <div class="card <?php echo ($selected_role == 'admin') ? 'border-danger shadow' : ''; ?> h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-shield fa-3x mb-3 text-danger"></i>
                        <h5 class="card-title text-dark">Administrator</h5>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="users.php?role=guru" class="text-decoration-none">
                <div class="card <?php echo ($selected_role == 'guru') ? 'border-success shadow' : ''; ?> h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-chalkboard-teacher fa-3x mb-3 text-success"></i>
                        <h5 class="card-title text-dark">Guru</h5>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="users.php?role=siswa" class="text-decoration-none">
                <div class="card <?php echo ($selected_role == 'siswa') ? 'border-info shadow' : ''; ?> h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-graduate fa-3x mb-3 text-info"></i>
                        <h5 class="card-title text-dark">Siswa</h5>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-table me-2"></i> Data User <?php echo ($selected_role != 'all') ? '(' . ucfirst($selected_role) . ')' : ''; ?></span>
            <a href="tambah_user.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah User Baru
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Terhubung ke</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT 
                                    u.id_user, u.username, u.email, u.role, u.status, 
                                    g.nama_lengkap AS nama_guru, 
                                    s.nama_lengkap AS nama_siswa
                                  FROM users u 
                                  LEFT JOIN guru g ON g.id_user = u.id_user
                                  LEFT JOIN siswa s ON s.id_user = u.id_user";
                        
                        // Apply Filter
                        if ($selected_role != 'all') {
                            $role_safe = mysqli_real_escape_string($koneksi, $selected_role);
                            $query .= " WHERE u.role = '$role_safe'";
                        }
                        
                        $query .= " ORDER BY u.username ASC";

                        $result = mysqli_query($koneksi, $query);
                        $nomor = 1;
                        
                        if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td class='text-center'>" . $nomor++ . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";

                                // Badge role
                                $roleBadge = match($row['role']) {
                                    'admin' => 'bg-danger',
                                    'guru' => 'bg-success', // Changed to success (green) to match card
                                    'siswa' => 'bg-info',   // Changed to info (cyan) to match card
                                    default => 'bg-secondary'
                                };
                                echo "<td class='text-center'><span class='badge $roleBadge'>" . htmlspecialchars(ucfirst($row['role'])) . "</span></td>";

                                // Badge status
                                $statusBadge = ($row['status'] == 'aktif')
                                    ? "<span class='badge bg-success'>Aktif</span>"
                                    : "<span class='badge bg-danger'>Nonaktif</span>";
                                echo "<td class='text-center'>$statusBadge</td>";

                                // Menampilkan nama guru atau siswa yang terhubung
                                $terhubung_ke = '-';
                                if (!empty($row['nama_guru'])) {
                                    $terhubung_ke = htmlspecialchars($row['nama_guru']) . " <br><small class='text-muted'>(Guru)</small>";
                                } elseif (!empty($row['nama_siswa'])) {
                                    $terhubung_ke = htmlspecialchars($row['nama_siswa']) . " <br><small class='text-muted'>(Siswa)</small>";
                                }
                                echo "<td>" . $terhubung_ke . "</td>";

                                // Tombol aksi
                                echo "<td class='text-center'>";
                                echo "<div class='btn-group'>";
                                echo "<a href='edit_user.php?id=" . $row['id_user'] . "' class='btn btn-warning btn-sm' title='Edit'><i class='fas fa-pencil-alt'></i></a>";
                                echo "<a href='hapus_user.php?id=" . $row['id_user'] . "' 
                                         onclick=\"return confirm('Yakin ingin menghapus user ini?');\" 
                                         class='btn btn-danger btn-sm' title='Hapus'>
                                        <i class='fas fa-trash-alt'></i>
                                      </a>";
                                echo "</div>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center py-4 text-muted'>Tidak ada data user untuk kategori ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
