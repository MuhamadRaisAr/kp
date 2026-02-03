<?php
// Panggil file-file yang dibutuhkan
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Lihat Nilai Saya";

// --- VALIDASI AKSES ---
$role_check = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';

if ($role_check !== 'siswa') {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk siswa.</div></div>';
    require_once '../../includes/footer.php';
    exit();
}

if (empty($_SESSION['id_siswa'])) {
    echo '<div class="container-fluid px-4"><div class="alert alert-warning mt-4">Akses Ditolak. Akun Anda tidak terhubung dengan data siswa yang valid.</div></div>';
    require_once '../../includes/footer.php';
    exit();
}

$id_siswa_login = (int)$_SESSION['id_siswa'];

// --- FUNGSI PEMBANTU ---
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
        <h1 class="mt-4"><?php echo $judul_halaman; ?></h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Lihat Nilai</li>
        </ol>
        
    <!-- Filter dihapus agar nilai langsung muncul -->

    <?php
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

        // Hitung Rata-rata Semester
        $total_nilai_semester = 0;
        $jumlah_mapel = 0;
        foreach ($mapel_list as $mapel => $nilai) {
            $tugas = $nilai['Tugas'] ?? 0;
            $uts = $nilai['UTS'] ?? 0;
            $uas = $nilai['UAS'] ?? 0;
            $praktik = $nilai['Praktik'] ?? 0;
            $nilai_akhir = hitungNilaiAkhir($tugas, $uts, $uas, $praktik);
            $total_nilai_semester += $nilai_akhir;
            $jumlah_mapel++;
        }
        $rata_rata_semester = $jumlah_mapel > 0 ? $total_nilai_semester / $jumlah_mapel : 0;
        $predikat_ipk = tentukanPredikat($rata_rata_semester);
    ?>
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
            <div>
                <i class="fas fa-calendar-alt me-2"></i> Tahun Ajaran: 
                <span class="fw-bold"><?php echo htmlspecialchars($periode_label); ?></span>
            </div>
            <div class="d-flex align-items-center">
                <div class="me-3 text-end d-none d-md-block border-end pe-3">
                    <small class="d-block text-white-50" style="font-size: 0.75rem;">Rata-rata</small>
                    <span class="fw-bold fs-5"><?php echo number_format($rata_rata_semester, 2); ?></span>
                </div>
                <a href="download_nilai.php?id_siswa=<?php echo $id_siswa_login; ?>&id_tahun_ajaran=<?php echo $id_tahun; ?>" class="btn btn-light btn-sm text-primary fw-bold shadow-sm">
                    <i class="fas fa-file-download me-1"></i> Download
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive rounded border">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase small">
                            <th width="5%" class="text-center">No</th>
                            <th class="text-start"><i class="fas fa-book me-1"></i> Mata Pelajaran</th>
                            <th width="10%" class="text-center">Tugas</th>
                            <th width="10%" class="text-center">UTS</th>
                            <th width="10%" class="text-center">UAS</th>
                            <th width="10%" class="text-center">Praktik</th>
                            <th width="12%" class="text-center text-primary">Nilai Akhir</th>
                            <th width="10%" class="text-center">Predikat</th>
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
                            
                            $badge_color = ($predikat == 'A' || $predikat == 'B') ? 'bg-success' : (($predikat == 'C') ? 'bg-warning text-dark' : 'bg-danger');
                        ?>
                        <tr>
                            <td class="text-center text-muted"><?php echo $nomor++; ?></td>
                            <td class="text-start fw-bold text-dark"><?php echo htmlspecialchars($mapel); ?></td>
                            <td class="text-center"><?php echo $tugas ?: '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-center"><?php echo $uts ?: '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-center"><?php echo $uas ?: '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-center"><?php echo $praktik ?: '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-center fw-bold text-primary fs-6"><?php echo number_format($nilai_akhir, 2); ?></td>
                            <td class="text-center">
                                <span class="badge rounded-pill <?php echo $badge_color; ?> px-3">
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

<?php require_once '../../includes/footer.php'; ?>