<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Daftar Kelas (Penugasan)";
?>

<div class="container-fluid px-4">
    <?php
    $id_tahun = isset($_GET['id_tahun']) ? (int)$_GET['id_tahun'] : 0;
    
    // Get year info
    $nama_tahun = "";
    if ($id_tahun > 0) {
        $q_ta = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = $id_tahun");
        if ($r_ta = mysqli_fetch_assoc($q_ta)) {
            $nama_tahun = $r_ta['tahun_ajaran'] . " (" . $r_ta['semester'] . ")";
        }
    }
    ?>

    <h1 class="mt-4"><?php echo ($_SESSION['role'] == 'guru') ? 'Daftar Kelas Saya' : 'Daftar Wali Kelas'; ?> <?php echo htmlspecialchars($nama_tahun); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="mengajar.php">Penugasan Mengajar</a></li>
        <li class="breadcrumb-item active">Kelas</li>
    </ol>

    <div class="row">
        <?php
        $where_clause = "";
        if ($id_tahun > 0) {
            $where_clause = "WHERE k.id_tahun_ajaran = $id_tahun";
        } else {
            // Default condition if id_tahun is 0 (though unlikely due to previous logic)
            $where_clause = "WHERE 1=1";
        }

        // --- FILTER FOR GURU (Wali Kelas & Pengajar) ---
        // Jika yang login adalah GURU, tampilkan kelas dmn dia adalah WALI KELAS ATAU PENGAJAR.
        $role = $_SESSION['role'] ?? 'guest';
        if ($role == 'guru' && isset($_SESSION['id_guru'])) {
            $id_guru_login = (int)$_SESSION['id_guru'];
            // Subquery untuk mencari kelas dimana guru ini mengajar di tahun ajaran ini
            // KEMBALIKAN LOGIKA: Guru bisa melihat kelas jika dia Wali Kelas ATAU mengajar mapel di sana.
            $subquery_mengajar = "SELECT DISTINCT id_kelas FROM mengajar WHERE id_guru = $id_guru_login AND id_tahun_ajaran = $id_tahun";
            
            $where_clause .= " AND (k.id_guru_wali_kelas = $id_guru_login OR k.id_kelas IN ($subquery_mengajar))";
        }
        // ------------------------------------

        $query = "SELECT k.id_kelas, k.nama_kelas, k.tingkat, g.nama_lengkap as nama_wali, j.nama_jurusan
                  FROM kelas k
                  LEFT JOIN guru g ON k.id_guru_wali_kelas = g.id_guru
                  LEFT JOIN jurusan j ON k.id_jurusan = j.id_jurusan
                  $where_clause
                  ORDER BY k.tingkat ASC, k.nama_kelas ASC";
                  
        $result = mysqli_query($koneksi, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $nama_wali = $row['nama_wali'] ? $row['nama_wali'] : "Belum ada Wali Kelas";
                $kelas_full = $row['nama_kelas']; 
                ?>
                <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                    <div class="card shadow-sm h-100 border-start-lg border-start-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="fas fa-user-tie text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="card-subtitle text-muted small mb-1"><?php echo htmlspecialchars($kelas_full); ?></h6>
                                    <h5 class="card-title fw-bold mb-0 text-dark" style="font-size: 1rem;"><?php echo htmlspecialchars($nama_wali); ?></h5>
                                </div>
                            </div>
                            <hr class="my-2">
                             <div class="d-grid mt-3">
                                <a href="detail_pengajar.php?id_kelas=<?php echo $row['id_kelas']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                    <i class="fas fa-chalkboard-teacher me-2"></i> Lihat Guru Pengajar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-info">Belum ada data kelas untuk tahun ajaran ini.</div></div>';
        }
        ?>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>
