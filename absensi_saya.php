<?php
// Memulai sesi dan menyertakan file-file yang diperlukan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Sesuaikan path jika perlu
require_once 'includes/koneksi.php'; 
require_once 'includes/header.php';  

// =================================================================
// BLOK KEAMANAN (PENTING: Hanya Siswa)
// =================================================================
$role_login = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : ''; // Ambil role, ubah ke huruf kecil
$id_siswa_login = isset($_SESSION['id_siswa']) ? (int)$_SESSION['id_siswa'] : 0; // Ambil id_siswa, jadikan angka

// Cek apakah bukan siswa ATAU id_siswa kosong
if (($role_login !== 'siswa' && $role_login !== 'murid') || empty($id_siswa_login)) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk siswa yang valid.</div></div>';
    require_once 'includes/footer.php';
    exit(); // Hentikan eksekusi halaman
}
// =================================================================
// AKHIR BLOK KEAMANAN
// =================================================================

// Ambil filter tahun ajaran jika ada (dari URL)
$selected_tahun = isset($_GET['tahun_ajaran']) ? (int)$_GET['tahun_ajaran'] : 0; // 0 artinya tampilkan semua

$judul_halaman = "Riwayat Absensi Saya";

// =================================================================
// BLOK PHP: MENGHITUNG REKAP ABSENSI
// =================================================================
$rekap = [
    'Hadir' => 0,
    'Sakit' => 0,
    'Izin'  => 0,
    'Alpha' => 0 // Gunakan 'Alpha' di sini
];

// Query dasar untuk rekap
// Gunakan CASE WHEN untuk menormalkan 'Alfa' menjadi 'Alpha'
$sql_rekap = "SELECT 
                CASE 
                    WHEN a.status = 'Alfa' THEN 'Alpha' 
                    ELSE a.status 
                END AS status_normal, 
                COUNT(*) as jumlah 
              FROM absensi a";

// Tambahkan JOIN jika perlu filter tahun ajaran
if ($selected_tahun > 0) {
    $sql_rekap .= " JOIN mengajar m ON a.id_mengajar = m.id_mengajar";
}

$sql_rekap .= " WHERE a.id_siswa = ?"; // Filter berdasarkan siswa yang login

// Tambahkan filter tahun ajaran jika dipilih
if ($selected_tahun > 0) {
    $sql_rekap .= " AND m.id_tahun_ajaran = ?";
}

$sql_rekap .= " GROUP BY status_normal"; // Group berdasarkan alias

// Persiapkan dan jalankan statement
$stmt_rekap = mysqli_prepare($koneksi, $sql_rekap);

if ($stmt_rekap) {
    if ($selected_tahun > 0) {
        mysqli_stmt_bind_param($stmt_rekap, "ii", $id_siswa_login, $selected_tahun);
    } else {
        mysqli_stmt_bind_param($stmt_rekap, "i", $id_siswa_login);
    }
    
    mysqli_stmt_execute($stmt_rekap);
    $result_rekap = mysqli_stmt_get_result($stmt_rekap);
    
    // Isi array $rekap dengan data dari database
    while ($row_rekap = mysqli_fetch_assoc($result_rekap)) {
        // Gunakan $row_rekap['status_normal'] sebagai kunci
        if (isset($rekap[$row_rekap['status_normal']])) { 
            $rekap[$row_rekap['status_normal']] = $row_rekap['jumlah'];
        }
    }
    mysqli_stmt_close($stmt_rekap);
} else {
    // Handle error jika prepare statement gagal
    echo "Error preparing statement: " . mysqli_error($koneksi);
}
// =================================================================
// AKHIR BLOK REKAP PHP
// =================================================================
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $judul_halaman; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Absensi Saya</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i>Filter Berdasarkan Tahun Ajaran</div>
        <div class="card-body">
            <form method="GET" action="absensi_saya.php">
                <div class="row">
                    <div class="col-md-6">
                        <select name="tahun_ajaran" class="form-select" onchange="this.form.submit()">
                            <option value="0">-- Tampilkan Semua Tahun Ajaran --</option>
                            <?php 
                            // Ambil semua tahun ajaran untuk filter
                            $result_tahun = mysqli_query($koneksi, "SELECT id_tahun_ajaran, tahun_ajaran, semester FROM tahun_ajaran ORDER BY tahun_ajaran DESC, semester DESC");
                            while($row = mysqli_fetch_assoc($result_tahun)) {
                                $selected = ($row['id_tahun_ajaran'] == $selected_tahun) ? 'selected' : '';
                                echo "<option value='{$row['id_tahun_ajaran']}' $selected>" . htmlspecialchars($row['tahun_ajaran'] . " - " . $row['semester']) . "</option>";
                            } 
                            ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-chart-pie me-1"></i>Rekapitulasi Absensi <?php echo ($selected_tahun > 0) ? '(Tahun Ajaran Dipilih)' : '(Semua)'; ?></div>
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card bg-success text-white shadow">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-1">Hadir</div>
                                    <div class="h5 mb-0 fw-bold"><?php echo $rekap['Hadir']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white shadow">
                        <div class="card-body">
                             <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-1">Sakit</div>
                                    <div class="h5 mb-0 fw-bold"><?php echo $rekap['Sakit']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-notes-medical fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card bg-info text-white shadow">
                        <div class="card-body">
                             <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-1">Izin</div>
                                    <div class="h5 mb-0 fw-bold"><?php echo $rekap['Izin']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-envelope-open-text fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card bg-danger text-white shadow">
                        <div class="card-body">
                             <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-1">Alpha</div>
                                    <div class="h5 mb-0 fw-bold"><?php echo $rekap['Alpha']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-table me-1"></i>Detail Absensi <?php echo ($selected_tahun > 0) ? '(Tahun Ajaran Dipilih)' : '(Semua)'; ?></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Mata Pelajaran</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query dasar untuk mengambil data detail absensi
                        $sql_detail = "SELECT a.tanggal, mp.nama_mapel, a.status, a.keterangan 
                                FROM absensi a
                                JOIN mengajar m ON a.id_mengajar = m.id_mengajar 
                                JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                WHERE a.id_siswa = ?";

                        // Tambahkan filter tahun ajaran jika dipilih
                        if ($selected_tahun > 0) {
                            $sql_detail .= " AND m.id_tahun_ajaran = ?";
                        }

                        $sql_detail .= " ORDER BY a.tanggal DESC";
                        
                        // Gunakan prepared statement untuk keamanan
                        $stmt_detail = mysqli_prepare($koneksi, $sql_detail);
                        
                        if ($stmt_detail) {
                            if ($selected_tahun > 0) {
                                mysqli_stmt_bind_param($stmt_detail, "ii", $id_siswa_login, $selected_tahun);
                            } else {
                                mysqli_stmt_bind_param($stmt_detail, "i", $id_siswa_login);
                            }
                            
                            mysqli_stmt_execute($stmt_detail);
                            $result_detail = mysqli_stmt_get_result($stmt_detail);
                            
                            if (mysqli_num_rows($result_detail) > 0) {
                                $nomor = 1;
                                while ($row = mysqli_fetch_assoc($result_detail)) {
                                    // Memberi warna sesuai status
                                    $status_class = '';
                                    $status_display = $row['status']; // Default
                                    switch ($row['status']) {
                                        case 'Sakit': $status_class = 'table-warning'; break;
                                        case 'Izin': $status_class = 'table-info'; break;
                                        case 'Alfa': 
                                        case 'Alpha': $status_class = 'table-danger'; $status_display = 'Alpha'; break; // Normalisasi tampilan
                                        case 'Hadir': $status_class = 'table-success'; break;
                                    }

                                    echo "<tr>";
                                    echo "<td>" . $nomor++ . "</td>";
                                    echo "<td>" . date('d F Y', strtotime($row['tanggal'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nama_mapel']) . "</td>";
                                    echo "<td class='" . $status_class . "'>" . htmlspecialchars($status_display) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['keterangan'] ?: '-') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>Data absensi belum tersedia.</td></tr>";
                            }
                            mysqli_stmt_close($stmt_detail);
                        } else {
                             echo "<tr><td colspan='5' class='text-center'>Error preparing statement: " . mysqli_error($koneksi) . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Sesuaikan path jika perlu
require_once 'includes/footer.php'; 
?>