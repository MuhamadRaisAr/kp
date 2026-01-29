<?php
// Memulai sesi dan menyertakan file-file yang diperlukan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Sesuaikan path jika perlu
require_once '../../includes/koneksi.php'; 
require_once '../../includes/header.php';  

// =================================================================
// BLOK KEAMANAN (PENTING: Hanya Siswa)
// =================================================================
$role_login = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : ''; // Ambil role, ubah ke huruf kecil
$id_siswa_login = isset($_SESSION['id_siswa']) ? (int)$_SESSION['id_siswa'] : 0; // Ambil id_siswa, jadikan angka

// Cek apakah bukan siswa ATAU id_siswa kosong
if (($role_login !== 'siswa' && $role_login !== 'murid') || empty($id_siswa_login)) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk siswa yang valid.</div></div>';
    require_once '../../includes/footer.php';
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
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
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
    
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-primary text-white d-flex align-items-center">
            <i class="fas fa-calendar-alt me-2"></i> 
            <span class="fw-bold">Periode: <?php echo htmlspecialchars($periode_label); ?></span>
        </div>
        <div class="card-body">
            
            <!-- Mini Dashboard Rekap Per Semester -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="card bg-success text-white h-100 border-0 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                            <div>
                                <small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.75rem;">Hadir</small>
                                <div class="fs-4 fw-bold"><?php echo $rekap_periode['Hadir']; ?></div>
                            </div>
                            <i class="fas fa-check-circle fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card bg-warning text-dark h-100 border-0 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                            <div>
                                <small class="text-dark-50 text-uppercase fw-bold" style="font-size: 0.75rem;">Sakit</small>
                                <div class="fs-4 fw-bold"><?php echo $rekap_periode['Sakit']; ?></div>
                            </div>
                            <i class="fas fa-notes-medical fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card bg-info text-white h-100 border-0 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                            <div>
                                <small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.75rem;">Izin</small>
                                <div class="fs-4 fw-bold"><?php echo $rekap_periode['Izin']; ?></div>
                            </div>
                            <i class="fas fa-envelope-open-text fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="card bg-danger text-white h-100 border-0 shadow-sm">
                        <div class="card-body d-flex justify-content-between align-items-center p-3">
                            <div>
                                <small class="text-white-50 text-uppercase fw-bold" style="font-size: 0.75rem;">Alpha</small>
                                <div class="fs-4 fw-bold"><?php echo $rekap_periode['Alpha']; ?></div>
                            </div>
                            <i class="fas fa-times-circle fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Detail Absensi -->
            <div class="table-responsive rounded border">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase small">
                            <th width="5%" class="text-center">No</th>
                            <th width="15%"><i class="fas fa-calendar-day me-1"></i> Tanggal</th>
                            <th><i class="fas fa-book me-1"></i> Mata Pelajaran</th>
                            <th width="12%" class="text-center"><i class="fas fa-info-circle me-1"></i> Status</th>
                            <th><i class="fas fa-comment-alt me-1"></i> Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $nomor = 1;
                        foreach ($rows as $row_detail) {
                            $status_badge = '';
                            $status_text = $row_detail['status'];
                            
                            // Tentukan warna badge
                            switch ($row_detail['status']) {
                                case 'Hadir': 
                                    $status_badge = 'bg-success'; 
                                    break;
                                case 'Sakit': 
                                    $status_badge = 'bg-warning text-dark'; 
                                    break;
                                case 'Izin': 
                                    $status_badge = 'bg-info text-dark'; 
                                    break;
                                case 'Alfa': 
                                case 'Alpha': 
                                    $status_badge = 'bg-danger'; 
                                    $status_text = 'Alpha'; 
                                    break;
                                default:
                                    $status_badge = 'bg-secondary';
                            }
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $nomor++; ?></td>
                                <td class="fw-bold"><?php echo date('d M Y', strtotime($row_detail['tanggal'])); ?></td>
                                <td><?php echo htmlspecialchars($row_detail['nama_mapel']); ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill <?php echo $status_badge; ?> px-3 py-2">
                                        <?php echo htmlspecialchars($status_text); ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?php echo htmlspecialchars($row_detail['keterangan'] ?: '-'); ?></td>
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
require_once '../../includes/footer.php'; 
?>
