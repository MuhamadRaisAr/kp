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
    // Query GABUNGAN:
    // 1. Ambil semua mapel di KELAS SISWA SAAT INI (Semester Aktif), meskipun belum ada nilai (LEFT JOIN).
    // 2. Ambil semua riwayat nilai di masa lalu (inner join nilai).
    
    // 1. Ambil ID Tahun Ajaran Aktif
    $q_tahun_aktif = mysqli_query($koneksi, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status_aktif = 'Aktif' LIMIT 1");
    $d_tahun_aktif = mysqli_fetch_assoc($q_tahun_aktif);
    $id_tahun_aktif = $d_tahun_aktif['id_tahun_ajaran'] ?? 0;

    // 2. Ambil ID Kelas siswa saat ini
    $q_siswa_info = mysqli_query($koneksi, "SELECT id_kelas FROM siswa WHERE id_siswa = $id_siswa_login");
    $d_siswa_info = mysqli_fetch_assoc($q_siswa_info);
    $id_kelas_act = $d_siswa_info['id_kelas'] ?? 0;

    $query_nilai = "
        SELECT 
            ta.id_tahun_ajaran,
            ta.tahun_ajaran,
            ta.semester,
            mp.nama_mapel, 
            g.nama_lengkap AS nama_guru,
            k.nama_kelas,
            n.jenis_nilai, 
            n.nilai 
        FROM mengajar m
        JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
        JOIN guru g ON m.id_guru = g.id_guru
        JOIN kelas k ON m.id_kelas = k.id_kelas
        JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran
        LEFT JOIN nilai n ON m.id_mengajar = n.id_mengajar AND n.id_siswa = ?
        WHERE m.id_kelas = ? AND m.id_tahun_ajaran = ?
        
        UNION ALL
        
        SELECT 
            ta.id_tahun_ajaran,
            ta.tahun_ajaran,
            ta.semester,
            mp.nama_mapel, 
            g.nama_lengkap AS nama_guru,
            k.nama_kelas,
            n.jenis_nilai, 
            n.nilai 
        FROM nilai n
        JOIN mengajar m ON n.id_mengajar = m.id_mengajar
        JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
        JOIN guru g ON m.id_guru = g.id_guru
        JOIN kelas k ON m.id_kelas = k.id_kelas
        JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran
        WHERE n.id_siswa = ? AND m.id_tahun_ajaran != ?
        
        ORDER BY tahun_ajaran DESC, semester DESC, nama_mapel ASC
    ";
    
    $stmt_nilai = mysqli_prepare($koneksi, $query_nilai);
    // Bind: 
    // Part 1: id_siswa (for Left Join ON), id_kelas (Where), id_tahun_aktif (Where)
    // Part 2: id_siswa (Where), id_tahun_aktif (Where !=)
    mysqli_stmt_bind_param($stmt_nilai, "iiiii", $id_siswa_login, $id_kelas_act, $id_tahun_aktif, $id_siswa_login, $id_tahun_aktif);
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

        // Simpan data mapel & guru
        // Gunakan nama mapel sebagai key unik per periode
        if (!isset($data_nilai_group[$id_or_key]['mapel'][$row['nama_mapel']])) {
            $data_nilai_group[$id_or_key]['mapel'][$row['nama_mapel']] = [
                'guru' => $row['nama_guru'],
                'nilai' => [] // init array nilai
            ];
        }

        // Masukkan nilai jika ada
        if (!empty($row['jenis_nilai'])) {
            $data_nilai_group[$id_or_key]['mapel'][$row['nama_mapel']]['nilai'][$row['jenis_nilai']] = $row['nilai'];
        }
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
        foreach ($mapel_list as $mapel => $data) {
            $nilai = $data['nilai'];
            $tugas = $nilai['Tugas'] ?? 0;
            $uts = $nilai['UTS'] ?? 0;
            $uas = $nilai['UAS'] ?? 0;
            $praktik = $nilai['Praktik'] ?? 0;
            
            // Hanya hitung rata-rata jika ada setidaknya satu nilai
            if ($tugas > 0 || $uts > 0 || $uas > 0 || $praktik > 0) {
                $nilai_akhir = hitungNilaiAkhir($tugas, $uts, $uas, $praktik);
                $total_nilai_semester += $nilai_akhir;
                $jumlah_mapel++;
            }
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
                            <th class="text-start" width="20%"><i class="fas fa-chalkboard-teacher me-1"></i> Guru</th>
                            <th width="8%" class="text-center">Tugas</th>
                            <th width="8%" class="text-center">UTS</th>
                            <th width="8%" class="text-center">UAS</th>
                            <th width="8%" class="text-center">Praktik</th>
                            <th width="10%" class="text-center text-primary">Nilai Akhir</th>
                            <th width="8%" class="text-center">Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $nomor = 1;
                        foreach ($mapel_list as $mapel => $data):
                            // Ambil nama guru & array nilai
                            $nama_guru = $data['guru'] ?? '-';
                            $nilai = $data['nilai'] ?? [];

                            $tugas = $nilai['Tugas'] ?? 0;
                            $uts = $nilai['UTS'] ?? 0;
                            $uas = $nilai['UAS'] ?? 0;
                            $praktik = $nilai['Praktik'] ?? 0;
                            
                            // Hitung nilai akhir hanya jika ada nilai
                            $nilai_akhir = 0;
                            if ($tugas > 0 || $uts > 0 || $uas > 0 || $praktik > 0) {
                                $nilai_akhir = hitungNilaiAkhir($tugas, $uts, $uas, $praktik);
                            }
                            
                            $predikat = tentukanPredikat($nilai_akhir);
                            
                            $badge_color = ($predikat == 'A' || $predikat == 'B') ? 'bg-success' : (($predikat == 'C') ? 'bg-warning text-dark' : 'bg-danger');
                        ?>
                        <tr>
                            <td class="text-center text-muted"><?php echo $nomor++; ?></td>
                            <td class="text-start fw-bold text-dark"><?php echo htmlspecialchars($mapel); ?></td>
                            <td class="text-start text-muted"><small><?php echo htmlspecialchars($nama_guru); ?></small></td>
                            <td class="text-center"><?php echo $tugas ?: '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-center"><?php echo $uts ?: '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-center"><?php echo $uas ?: '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-center"><?php echo $praktik ?: '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-center fw-bold text-primary fs-6"><?php echo ($nilai_akhir > 0) ? number_format($nilai_akhir, 2) : '-'; ?></td>
                            <td class="text-center">
                                <?php if ($nilai_akhir > 0): ?>
                                <span class="badge rounded-pill <?php echo $badge_color; ?> px-3">
                                    <?php echo $predikat; ?>
                                </span>
                                <?php else: ?>
                                <span class="badge bg-light text-muted border">Belum ada</span>
                                <?php endif; ?>
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