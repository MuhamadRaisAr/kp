<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Cetak Rapor Siswa";

// Ambil data session
$id_guru_login = isset($_SESSION['id_guru']) ? $_SESSION['id_guru'] : null;
$role = strtolower($_SESSION['role']);

// --- PROTEKSI AKSES DASAR ---
// Hanya Admin dan Guru yang boleh masuk ke halaman ini
if ($role !== 'admin' && $role !== 'guru') {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses Ditolak! Halaman ini hanya untuk Admin atau Wali Kelas.</div></div>';
    require_once '../../includes/footer.php';
    exit();
}

// Ambil data Tahun Ajaran Aktif
$query_tahun = "SELECT * FROM tahun_ajaran WHERE status_aktif = 'Aktif' ORDER BY tahun_ajaran DESC";
$result_tahun = mysqli_query($koneksi, $query_tahun);

// --- LOGIKA FILTER KELAS ---
if ($role == 'admin') {
    // Admin bisa melihat semua kelas
    $query_kelas = "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC";
} else {
    // Guru HANYA bisa melihat kelas di mana dia menjadi WALI KELAS
    $query_kelas = "SELECT id_kelas, nama_kelas FROM kelas WHERE id_guru_wali_kelas = '$id_guru_login' ORDER BY nama_kelas ASC";
}
$result_kelas = mysqli_query($koneksi, $query_kelas);

$selected_tahun = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';
$selected_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
?>

<div class="container-fluid px-4">
    <?php
    $step = 1;
    $selected_tahun = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';
    $selected_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
    
    // Determine current step
    if (!empty($selected_tahun) && !empty($selected_kelas)) {
        $step = 3;
    } elseif (!empty($selected_tahun)) {
        $step = 2;
    }

    // --- STEP 1: PILIH TAHUN AJARAN ---
    if ($step == 1):
    ?>
        <h1 class="mt-4">Cetak Rapor Siswa</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Cetak Rapor - Pilih Tahun Ajaran</li>
        </ol>

        <div class="row">
            <?php
            $query_tahun = "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC, semester DESC";
            $result_tahun = mysqli_query($koneksi, $query_tahun);

            if (mysqli_num_rows($result_tahun) > 0) {
                while ($row = mysqli_fetch_assoc($result_tahun)) {
                    $status_class = ($row['status_aktif'] == 'Aktif') ? 'border-primary' : '';
                    ?>
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                        <div class="card shadow-sm h-100 <?php echo $status_class; ?>">
                            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-calendar-alt fa-3x text-secondary"></i>
                                </div>
                                <h4 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($row['tahun_ajaran']); ?></h4>
                                <small class="text-muted"><?php echo htmlspecialchars($row['semester']); ?></small>
                                <?php if ($row['status_aktif'] == 'Aktif'): ?>
                                    <span class="badge bg-success mt-2">Aktif</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white border-top-0 pb-3 text-center">
                                <a href="cetak_rapot.php?tahun_ajaran=<?php echo $row['id_tahun_ajaran']; ?>" class="btn btn-outline-dark w-100 rounded-pill">
                                    <i class="fas fa-eye me-2"></i> Lihat Kelas
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-info">Belum ada data tahun ajaran.</div></div>';
            }
            ?>
        </div>

    <?php 
    // --- STEP 2: PILIH KELAS ---
    elseif ($step == 2): 
        // Get Year Info for display
        $q_ta = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = '$selected_tahun'");
        $r_ta = mysqli_fetch_assoc($q_ta);
        $nama_tahun = $r_ta['tahun_ajaran'] . " (" . $r_ta['semester'] . ")";
    ?>
        <h1 class="mt-4">Pilih Kelas</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="cetak_rapot.php">Cetak Rapor</a></li>
            <li class="breadcrumb-item active">Pilih Kelas (<?php echo htmlspecialchars($nama_tahun); ?>)</li>
        </ol>

        <div class="row">
            <?php
            // Query Filter Logic
            $where_clause = "";
            if ($role == 'admin') {
                // Admin sees all classes
                $where_clause = "WHERE 1=1"; 
            } else {
                // Guru ONLY sees classes where they are Wali Kelas
                $where_clause = "WHERE k.id_guru_wali_kelas = '$id_guru_login'";
            }

            // Note: Currently not filtering by Year in the JOIN because 'kelas' table usually doesn't have 'id_tahun_ajaran' directly 
            // unless your schema updated it. Based on previous files, 'kelas' seems static or linked via 'mengajar'.
            // However, 'wali kelas' is usually a property of the class itself in simple schemas.
            // Let's stick to the logic used in the original select dropdown: 
            // "SELECT id_kelas, nama_kelas FROM kelas WHERE id_guru_wali_kelas = '$id_guru_login'"
            
            $query_kelas = "SELECT k.id_kelas, k.nama_kelas, k.tingkat, g.nama_lengkap as nama_wali, j.nama_jurusan
                            FROM kelas k
                            LEFT JOIN guru g ON k.id_guru_wali_kelas = g.id_guru
                            LEFT JOIN jurusan j ON k.id_jurusan = j.id_jurusan
                            $where_clause
                            ORDER BY k.tingkat ASC, k.nama_kelas ASC";
            
            $result_kelas = mysqli_query($koneksi, $query_kelas);

            if (mysqli_num_rows($result_kelas) > 0) {
                while ($row = mysqli_fetch_assoc($result_kelas)) {
                    $nama_wali = $row['nama_wali'] ? $row['nama_wali'] : "Belum ada Wali Kelas";
                    ?>
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                        <div class="card shadow-sm h-100 border-start-lg border-start-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                        <i class="fas fa-chalkboard-teacher text-primary fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted small mb-1"><?php echo htmlspecialchars($row['nama_kelas']); ?></h6>
                                        <h5 class="card-title fw-bold mb-0 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($nama_wali); ?></h5>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="d-grid mt-3">
                                    <a href="cetak_rapot.php?tahun_ajaran=<?php echo $selected_tahun; ?>&kelas=<?php echo $row['id_kelas']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                        <i class="fas fa-users me-2"></i> Lihat Siswa
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-warning">Anda tidak memiliki kelas perwalian pada tahun ajaran ini (atau data kelas kosong).</div></div>';
            }
            ?>
        </div>

    <?php 
    // --- STEP 3: DAFTAR SISWA ---
    elseif ($step == 3): 
        // Get Year and Class Info
        $q_ta = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = '$selected_tahun'");
        $r_ta = mysqli_fetch_assoc($q_ta);
        $nama_tahun = $r_ta['tahun_ajaran'] . " (" . $r_ta['semester'] . ")";

        $q_kls = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas = '$selected_kelas'");
        $r_kls = mysqli_fetch_assoc($q_kls);
        $nama_kelas = $r_kls['nama_kelas'];

        // Security Check for Guru
        if ($role == 'guru') {
            $check_wali = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE id_kelas = '$selected_kelas' AND id_guru_wali_kelas = '$id_guru_login'");
            if (mysqli_num_rows($check_wali) == 0) {
                echo '<div class="alert alert-danger">Anda tidak memiliki otoritas sebagai Wali Kelas untuk melihat data ini.</div>';
                exit();
            }
        }
    ?>
        <h1 class="mt-4">Daftar Siswa</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="cetak_rapot.php">Cetak Rapor</a></li>
            <li class="breadcrumb-item"><a href="cetak_rapot.php?tahun_ajaran=<?php echo $selected_tahun; ?>">Kelas <?php echo htmlspecialchars($nama_kelas); ?></a></li>
            <li class="breadcrumb-item active">Siswa</li>
        </ol>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-users me-1"></i>
                Daftar Siswa - Kelas <?php echo htmlspecialchars($nama_kelas); ?> - T.A. <?php echo htmlspecialchars($nama_tahun); ?>
                <a href="cetak_rapot.php?tahun_ajaran=<?php echo $selected_tahun; ?>" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <?php
                $query_siswa = "SELECT id_siswa, nis, nama_lengkap FROM siswa WHERE id_kelas = '$selected_kelas' ORDER BY nama_lengkap ASC";
                $result_siswa = mysqli_query($koneksi, $query_siswa);
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="50">No</th>
                                <th>NIS</th>
                                <th>Nama Lengkap</th>
                                <th width="200" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($result_siswa) > 0) {
                                $nomor = 1;
                                while($siswa = mysqli_fetch_assoc($result_siswa)) {
                                    echo "<tr>";
                                    echo "<td class='text-center'>" . $nomor++ . "</td>";
                                    echo "<td>" . htmlspecialchars($siswa['nis']) . "</td>";
                                    echo "<td>" . htmlspecialchars($siswa['nama_lengkap']) . "</td>";
                                    echo "<td class='text-center'>";
                                    echo "<a href='generate_pdf.php?id_siswa={$siswa['id_siswa']}&id_tahun_ajaran={$selected_tahun}' class='btn btn-success btn-sm' target='_blank'><i class='fas fa-print me-2'></i>Cetak Rapor</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center'>Tidak ada siswa di kelas ini.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
