<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Lihat Nilai Saya";

// ==========================================================
// PERBAIKAN FINAL DI SINI
// Kita pisah pengecekannya
// ==========================================================
$role_check = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';

// Cek 1: Apakah rolenya siswa? (auth_check.php sudah bantu)
if ($role_check !== 'siswa') {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk siswa.</div></div>';
    require_once 'includes/footer.php';
    exit();
}

// Cek 2: Apakah ID siswanya valid?
if (empty($_SESSION['id_siswa'])) {
    echo '<div class="container-fluid px-4"><div class="alert alert-warning mt-4">Akses Ditolak. Akun Anda terdaftar sebagai siswa, tetapi tidak terhubung dengan data siswa yang valid (ID Siswa kosong). Harap hubungi Administrator.</div></div>';
    require_once 'includes/footer.php';
    exit();
}
// ==========================================================
// AKHIR PERBAIKAN
// ==========================================================

// Aman, id_siswa pasti ada
$id_siswa_login = (int)$_SESSION['id_siswa'];

// Fungsi bantu untuk kalkulasi
function hitungNilaiAkhir($tugas, $uts, $uas, $praktik) {
    $nilai_yang_ada = [];
    if ($tugas > 0) $nilai_yang_ada[] = $tugas;
    if ($uts > 0) $nilai_yang_ada[] = $uts;
    if ($uas > 0) $nilai_yang_ada[] = $uas;
    if ($praktik > 0) $nilai_yang_ada[] = $praktik;
    if (count($nilai_yang_ada) == 0) return 0;
    return array_sum($nilai_yang_ada) / count($nilai_yang_ada);
}
function tentukanPredikat($nilai_akhir) {
    if ($nilai_akhir >= 85) return 'A';
    if ($nilai_akhir >= 75) return 'B';
    if ($nilai_akhir >= 60) return 'C';
    if ($nilai_akhir >= 40) return 'D';
    return 'E';
}


?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Nilai Akademik Saya</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Lihat Nilai</li>
    </ol>

    <!-- Filter dihapus agar nilai langsung muncul -->

    <?php
    // Query untuk mengambil SEMUA nilai siswa (tanpa filter tahun)
    // Diurutkan berdasarkan Tahun Ajaran (Terbaru) -> Semester -> Mapel
    // Query untuk mengambil SEMUA nilai siswa (tanpa filter tahun)
    // Diurutkan berdasarkan Tahun Ajaran (Terbaru) -> Semester -> Mapel
    $query_nilai = "SELECT 
                        ta.id_tahun_ajaran,
                        ta.tahun_ajaran,
                        ta.semester,
                        mp.nama_mapel, 
                        n.jenis_nilai, 
                        n.nilai 
                    FROM nilai n
                    JOIN mengajar m ON n.id_mengajar = m.id_mengajar
                    JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                    JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran
                    WHERE n.id_siswa = ? 
                    ORDER BY ta.tahun_ajaran DESC, ta.semester DESC, mp.nama_mapel ASC";
    
    $stmt_nilai = mysqli_prepare($koneksi, $query_nilai);
    mysqli_stmt_bind_param($stmt_nilai, "i", $id_siswa_login);
    mysqli_stmt_execute($stmt_nilai);
    $result_nilai = mysqli_stmt_get_result($stmt_nilai);

    // Olah data nilai: Grouping berdasarkan [ID Tahun][Label/Mapel]
    $data_nilai_group = [];
    while($row = mysqli_fetch_assoc($result_nilai)) {
        $id_or_key = $row['id_tahun_ajaran'];
        $periode_label = $row['tahun_ajaran'] . ' - ' . $row['semester'];
        
        // Simpan label periode
        if (!isset($data_nilai_group[$id_or_key]['label'])) {
            $data_nilai_group[$id_or_key]['label'] = $periode_label;
        }

        // Simpan data nilai
        $data_nilai_group[$id_or_key]['mapel'][$row['nama_mapel']][$row['jenis_nilai']] = $row['nilai'];
    }
    
    // Jika tidak ada data sama sekali
    if (empty($data_nilai_group)) {
        echo '<div class="alert alert-info">Belum ada data nilai yang tersedia.</div>';
    }

    // Loop setiap periode (Per Tahun Ajaran)
    foreach ($data_nilai_group as $id_tahun => $data_periode) :
        $periode_label = $data_periode['label'];
        $mapel_list = $data_periode['mapel'];
    ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-calendar-alt me-1"></i> Tahun Ajaran: <strong><?php echo htmlspecialchars($periode_label); ?></strong>
            </div>
            <div>
                <a href="download_nilai.php?id_siswa=<?php echo $id_siswa_login; ?>&id_tahun_ajaran=<?php echo $id_tahun; ?>" class="btn btn-light btn-sm text-primary fw-bold">
                    <i class="fas fa-file-download me-1"></i> Download Nilai
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center table-striped">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th class="text-start">Mata Pelajaran</th>
                            <th width="10%">Tugas</th>
                            <th width="10%">UTS</th>
                            <th width="10%">UAS</th>
                            <th width="10%">Praktik</th>
                            <th width="10%">Nilai Akhir</th>
                            <th width="10%">Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $nomor = 1;
                        foreach ($mapel_list as $mapel => $nilai):
                            $tugas = $nilai['Tugas'] ?? 0;
                            $uts = $nilai['UTS'] ?? 0;
                            $uas = $nilai['UAS'] ?? 0;
                            $praktik = $nilai['Praktik'] ?? 0;
                            $nilai_akhir = hitungNilaiAkhir($tugas, $uts, $uas, $praktik);
                            $predikat = tentukanPredikat($nilai_akhir);
                        ?>
                        <tr>
                            <td><?php echo $nomor++; ?></td>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($mapel); ?></td>
                            <td><?php echo $tugas ?: '-'; ?></td>
                            <td><?php echo $uts ?: '-'; ?></td>
                            <td><?php echo $uas ?: '-'; ?></td>
                            <td><?php echo $praktik ?: '-'; ?></td>
                            <td class="fw-bold"><?php echo number_format($nilai_akhir, 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($predikat == 'A' || $predikat == 'B') ? 'success' : (($predikat == 'C') ? 'warning' : 'danger'); ?>">
                                    <?php echo $predikat; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once 'includes/footer.php'; ?>