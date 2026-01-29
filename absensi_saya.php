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

// =================================================================
// BLOK PHP: MENGAMBIL SEMUA DATA & GROUPING
// =================================================================

// Query tunggal untuk mengambil semua data absensi + info tahun ajaran
$query_all = "SELECT 
                a.tanggal, a.status, a.keterangan, 
                mp.nama_mapel, 
                ta.tahun_ajaran, ta.semester
              FROM absensi a
              JOIN mengajar m ON a.id_mengajar = m.id_mengajar 
              JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
              JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran
              WHERE a.id_siswa = ?
              ORDER BY ta.tahun_ajaran DESC, ta.semester DESC, a.tanggal DESC";

$stmt = mysqli_prepare($koneksi, $query_all);
mysqli_stmt_bind_param($stmt, "i", $id_siswa_login);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data_grouped = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Buat Key Periode
    $periode = $row['tahun_ajaran'] . ' - ' . $row['semester'];
    
    // Inisialisasi Group jika belum ada
    if (!isset($data_grouped[$periode])) {
        $data_grouped[$periode] = [
            'rows' => [],
            'rekap' => ['Hadir' => 0, 'Sakit' => 0, 'Izin' => 0, 'Alpha' => 0]
        ];
    }

    // Masukkan data row
    $data_grouped[$periode]['rows'][] = $row;

    // Hitung Rekap
    $status_raw = $row['status'];
    // Normalisasi Alfa/Alpha
    if ($status_raw == 'Alfa') $status_raw = 'Alpha';
    
    if (isset($data_grouped[$periode]['rekap'][$status_raw])) {
        $data_grouped[$periode]['rekap'][$status_raw]++;
    }
}
// =================================================================
// AKHIR BLOK DATA
// =================================================================
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $judul_halaman; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Absensi Saya</li>
    </ol>

    <!-- Jika tidak ada data -->
    <?php if (empty($data_grouped)): ?>
        <div class="alert alert-info">Belum ada data absensi.</div>
    <?php else: ?>

    <!-- Loop Setiap Periode -->
    <?php foreach ($data_grouped as $periode_label => $group): 
        $rows = $group['rows'];
        $rekap_periode = $group['rekap'];
    ?>
    
    <div class="card mb-4">
        <div class="card-header bg-dark text-white">
            <i class="fas fa-calendar-alt me-1"></i> Periode: <strong><?php echo htmlspecialchars($periode_label); ?></strong>
        </div>
        <div class="card-body">
            
            <!-- Mini Dashboard Rekap Per Semester -->
            <div class="row mb-3">
                <div class="col-md-3 col-6 mb-2">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><small>Hadir</small><div class="h5 mb-0 fw-bold"><?php echo $rekap_periode['Hadir']; ?></div></div>
                                <i class="fas fa-check-circle fa-lg opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><small>Sakit</small><div class="h5 mb-0 fw-bold"><?php echo $rekap_periode['Sakit']; ?></div></div>
                                <i class="fas fa-notes-medical fa-lg opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><small>Izin</small><div class="h5 mb-0 fw-bold"><?php echo $rekap_periode['Izin']; ?></div></div>
                                <i class="fas fa-envelope-open-text fa-lg opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><small>Alpha</small><div class="h5 mb-0 fw-bold"><?php echo $rekap_periode['Alpha']; ?></div></div>
                                <i class="fas fa-times-circle fa-lg opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Detail Absensi -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="15%">Tanggal</th>
                            <th>Mata Pelajaran</th>
                            <th width="10%">Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $nomor = 1;
                        foreach ($rows as $row_detail) {
                            $status_class = '';
                            $status_display = $row_detail['status'];
                            switch ($row_detail['status']) {
                                case 'Sakit': $status_class = 'table-warning'; break;
                                case 'Izin': $status_class = 'table-info'; break;
                                case 'Alfa': 
                                case 'Alpha': $status_class = 'table-danger'; $status_display = 'Alpha'; break;
                                case 'Hadir': $status_class = 'table-success'; break;
                            }
                            ?>
                            <tr>
                                <td><?php echo $nomor++; ?></td>
                                <td><?php echo date('d M Y', strtotime($row_detail['tanggal'])); ?></td>
                                <td><?php echo htmlspecialchars($row_detail['nama_mapel']); ?></td>
                                <td class="<?php echo $status_class; ?> text-center fw-bold"><?php echo htmlspecialchars($status_display); ?></td>
                                <td><?php echo htmlspecialchars($row_detail['keterangan'] ?: '-'); ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
// Sesuaikan path jika perlu
require_once 'includes/footer.php'; 
?>