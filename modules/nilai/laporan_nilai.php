<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Laporan Nilai";

// Fungsi bantu hitung nilai
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

$id_guru_login = isset($_SESSION['id_guru']) ? (int)$_SESSION['id_guru'] : null;
$role = $_SESSION['role'] ?? 'guest';

// --- TENTUKAN STEP BERDASARKAN PARAMETER URL ---
$step = 1;
$selected_tahun = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';
$selected_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$selected_mapel = isset($_GET['mapel']) ? $_GET['mapel'] : '';

if (!empty($selected_tahun) && !empty($selected_kelas) && !empty($selected_mapel)) {
    $step = 4;
} elseif (!empty($selected_tahun) && !empty($selected_kelas)) {
    $step = 3;
} elseif (!empty($selected_tahun)) {
    $step = 2;
}

// Helper untuk nama tahun
$nama_tahun_global = "";
if($selected_tahun) {
    $q_tag = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = '$selected_tahun'");
    if($r_tag = mysqli_fetch_assoc($q_tag)) {
        $nama_tahun_global = $r_tag['tahun_ajaran'] . " (" . $r_tag['semester'] . ")";
    }
}
?>

<div class="container-fluid px-4">
    <?php if ($step == 1): ?>
        <!-- STEP 1: PILIH TAHUN AJARAN -->
        <h1 class="mt-4">Laporan Nilai - Pilih Tahun Ajaran</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Laporan Nilai</li>
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
                                <div class="mb-3"><i class="fas fa-calendar-alt fa-3x text-secondary"></i></div>
                                <h4 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($row['tahun_ajaran']); ?></h4>
                                <small class="text-muted"><?php echo htmlspecialchars($row['semester']); ?></small>
                                <?php if ($row['status_aktif'] == 'Aktif'): ?>
                                    <span class="badge bg-success mt-2">Aktif</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white border-top-0 pb-3 text-center">
                                <a href="laporan_nilai.php?tahun_ajaran=<?php echo $row['id_tahun_ajaran']; ?>" class="btn btn-outline-dark w-100 rounded-pill">
                                    <i class="fas fa-arrow-right me-2"></i> Pilih Tahun
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

    <?php elseif ($step == 2): 
        // STEP 2: PILIH KELAS
    ?>
        <h1 class="mt-4">Pilih Kelas</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="laporan_nilai.php">Laporan Nilai</a></li>
            <li class="breadcrumb-item active">Pilih Kelas (<?php echo htmlspecialchars($nama_tahun_global); ?>)</li>
        </ol>

        <div class="row">
            <?php
            // Filter Kelas
            if ($role == 'admin') {
                $query_kelas = "SELECT DISTINCT k.id_kelas, k.nama_kelas, k.tingkat, g.nama_lengkap as nama_wali 
                                FROM kelas k
                                LEFT JOIN guru g ON k.id_guru_wali_kelas = g.id_guru
                                ORDER BY k.tingkat ASC, k.nama_kelas ASC";
            } else {
                // Guru:
                // 1. Wali Kelas: Lihat kelas yang dia walikan.
                // 2. Guru Pengajar: Lihat kelas yang dia ajar.
                $query_kelas = "SELECT DISTINCT k.id_kelas, k.nama_kelas, k.tingkat, g.nama_lengkap as nama_wali 
                                FROM kelas k
                                LEFT JOIN guru g ON k.id_guru_wali_kelas = g.id_guru
                                LEFT JOIN mengajar m ON k.id_kelas = m.id_kelas
                                WHERE (m.id_guru = $id_guru_login AND m.id_tahun_ajaran = '$selected_tahun') 
                                OR (k.id_guru_wali_kelas = $id_guru_login)
                                ORDER BY k.tingkat ASC, k.nama_kelas ASC";
            }
            
            $result_kelas = mysqli_query($koneksi, $query_kelas);

            if (mysqli_num_rows($result_kelas) > 0) {
                while ($row = mysqli_fetch_assoc($result_kelas)) {
                    $nama_wali = $row['nama_wali'] ? $row['nama_wali'] : "Belum ada Wali Kelas";
                    ?>
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                        <div class="card shadow-sm h-100 border-start-lg border-start-info">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3">
                                        <i class="fas fa-chalkboard text-info fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted small mb-1"><?php echo htmlspecialchars($row['nama_kelas']); ?></h6>
                                        <h5 class="card-title fw-bold mb-0 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($nama_wali); ?></h5>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="d-grid mt-3">
                                    <a href="laporan_nilai.php?tahun_ajaran=<?php echo $selected_tahun; ?>&kelas=<?php echo $row['id_kelas']; ?>" class="btn btn-outline-info btn-sm rounded-pill">
                                        <i class="fas fa-book me-2"></i> Pilih Mapel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-warning">Tidak ada kelas yang tersedia untuk Anda.</div></div>';
            }
            ?>
        </div>

    <?php elseif ($step == 3): 
        // STEP 3: PILIH MATA PELAJARAN
        $q_kls = mysqli_query($koneksi, "SELECT nama_kelas, id_guru_wali_kelas FROM kelas WHERE id_kelas = '$selected_kelas'");
        $r_kls = mysqli_fetch_assoc($q_kls);
        $nama_kelas = $r_kls['nama_kelas'];
        $id_wali_kelas = $r_kls['id_guru_wali_kelas'];
        
        $is_wali_of_this_class = ($id_guru_login == $id_wali_kelas);
    ?>
        <h1 class="mt-4">Pilih Mata Pelajaran</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="laporan_nilai.php">Laporan Nilai</a></li>
            <li class="breadcrumb-item"><a href="laporan_nilai.php?tahun_ajaran=<?php echo $selected_tahun; ?>">Kelas <?php echo htmlspecialchars($nama_kelas); ?></a></li>
            <li class="breadcrumb-item active">Pilih Mapel</li>
        </ol>

        <div class="row">
            <?php
            // Filter Mapel
            if ($role == 'admin') {
                // Admin lihat semua mapel yang ADA DI JADWAL
                $query_mapel = "SELECT m.id_mapel, mp.nama_mapel, g.nama_lengkap as nama_guru
                                FROM mengajar m
                                JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                JOIN guru g ON m.id_guru = g.id_guru
                                WHERE m.id_kelas = '$selected_kelas' AND m.id_tahun_ajaran = '$selected_tahun'
                                ORDER BY mp.nama_mapel ASC";
            } else {
                // Guru
                if ($is_wali_of_this_class) {
                     // Jika Wali Kelas: Lihat SEMUA mapel di kelas ini (untuk monitoring)
                     $query_mapel = "SELECT m.id_mapel, mp.nama_mapel, g.nama_lengkap as nama_guru
                                     FROM mengajar m
                                     JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                     JOIN guru g ON m.id_guru = g.id_guru
                                     WHERE m.id_kelas = '$selected_kelas' AND m.id_tahun_ajaran = '$selected_tahun'
                                     ORDER BY mp.nama_mapel ASC";
                } else {
                    // Jika Guru Biasa: Hanya mapel yang dia ajar di kelas ini
                    $query_mapel = "SELECT m.id_mapel, mp.nama_mapel, g.nama_lengkap as nama_guru
                                    FROM mengajar m
                                    JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                    JOIN guru g ON m.id_guru = g.id_guru
                                    WHERE m.id_kelas = '$selected_kelas' AND m.id_tahun_ajaran = '$selected_tahun' AND m.id_guru = $id_guru_login
                                    ORDER BY mp.nama_mapel ASC";
                }
            }
            
            $result_mapel = mysqli_query($koneksi, $query_mapel);

            if (mysqli_num_rows($result_mapel) > 0) {
                while ($row = mysqli_fetch_assoc($result_mapel)) {
                    ?>
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                        <div class="card shadow-sm h-100 border-start-lg border-start-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                                        <i class="fas fa-book-open text-warning fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted small mb-1">Mapel</h6>
                                        <h5 class="card-title fw-bold mb-0 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['nama_mapel']); ?></h5>
                                    </div>
                                </div>
                                <p class="small text-muted mb-0"><i class="fas fa-user-tie me-1"></i> <?php echo htmlspecialchars($row['nama_guru']); ?></p>
                                <hr class="my-2">
                                <div class="d-grid mt-3">
                                    <a href="laporan_nilai.php?tahun_ajaran=<?php echo $selected_tahun; ?>&kelas=<?php echo $selected_kelas; ?>&mapel=<?php echo $row['id_mapel']; ?>" class="btn btn-outline-warning btn-sm rounded-pill">
                                        <i class="fas fa-list-alt me-2"></i> Lihat Nilai
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-warning">Tidak ada mata pelajaran yang ditemukan.</div></div>';
            }
            ?>
        </div>

    <?php elseif ($step == 4): 
        // STEP 4: VIEW DATA NILAI
        $q_kls = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas = '$selected_kelas'");
        $r_kls = mysqli_fetch_assoc($q_kls);
        $nama_kelas = $r_kls['nama_kelas'];
        
        $q_mpl = mysqli_query($koneksi, "SELECT nama_mapel FROM mata_pelajaran WHERE id_mapel = '$selected_mapel'");
        $r_mpl = mysqli_fetch_assoc($q_mpl);
        $nama_mapel = $r_mpl['nama_mapel'];
        
        // QUERY UTAMA NILAI
        $q_nilai = "SELECT n.id_siswa, n.jenis_nilai, n.nilai 
                    FROM nilai n
                    JOIN mengajar m ON n.id_mengajar = m.id_mengajar
                    WHERE m.id_mapel = {$selected_mapel} 
                      AND m.id_tahun_ajaran = {$selected_tahun}
                      AND n.id_siswa IN (SELECT id_siswa FROM siswa WHERE id_kelas = {$selected_kelas})";
        
        $res_nilai = mysqli_query($koneksi, $q_nilai);
        
        $q_siswa = "SELECT id_siswa, nis, nama_lengkap FROM siswa WHERE id_kelas = {$selected_kelas} ORDER BY nama_lengkap ASC";
        $res_siswa = mysqli_query($koneksi, $q_siswa);

        $nilai_siswa = [];
        if ($res_nilai) {
            while($row = mysqli_fetch_assoc($res_nilai)) {
                $nilai_siswa[$row['id_siswa']][$row['jenis_nilai']] = $row['nilai'];
            }
        }
    ?>
        <h1 class="mt-4">Hasil Laporan Nilai</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="laporan_nilai.php">Laporan Nilai</a></li>
            <li class="breadcrumb-item"><a href="laporan_nilai.php?tahun_ajaran=<?php echo $selected_tahun; ?>&kelas=<?php echo $selected_kelas; ?>">Mapel</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($nama_mapel); ?></li>
        </ol>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-table me-1"></i>
                Laporan Nilai: <?php echo htmlspecialchars($nama_mapel); ?> - Kelas <?php echo htmlspecialchars($nama_kelas); ?>
                <a href="laporan_nilai.php?tahun_ajaran=<?php echo $selected_tahun; ?>&kelas=<?php echo $selected_kelas; ?>" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th rowspan="2" class="align-middle">No</th>
                                <th rowspan="2" class="align-middle text-start">Nama Siswa</th>
                                <th rowspan="2" class="align-middle">NIS</th>
                                <th colspan="4">Rincian Nilai</th>
                                <th rowspan="2" class="align-middle bg-primary text-white">Nilai Akhir</th>
                                <th rowspan="2" class="align-middle bg-secondary text-white">Predikat</th>
                            </tr>
                            <tr>
                                <th>Tugas</th>
                                <th>UTS</th>
                                <th>UAS</th>
                                <th>Praktik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (mysqli_num_rows($res_siswa) > 0) {
                                $nomor = 1;
                                while($siswa = mysqli_fetch_assoc($res_siswa)): 
                                    $id_siswa = $siswa['id_siswa'];
                                    
                                    // Default nilai 0 agar tidak error/warning
                                    $tugas = $nilai_siswa[$id_siswa]['Tugas'] ?? 0;
                                    $uts = $nilai_siswa[$id_siswa]['UTS'] ?? 0;
                                    $uas = $nilai_siswa[$id_siswa]['UAS'] ?? 0;
                                    $praktik = $nilai_siswa[$id_siswa]['Praktik'] ?? 0;

                                    $nilai_akhir = hitungNilaiAkhir($tugas, $uts, $uas, $praktik);
                                    $predikat = tentukanPredikat($nilai_akhir);
                            ?>
                            <tr>
                                <td><?php echo $nomor++; ?></td>
                                <td class="text-start fw-bold"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                                <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                                <td><?php echo $tugas; ?></td>
                                <td><?php echo $uts; ?></td>
                                <td><?php echo $uas; ?></td>
                                <td><?php echo $praktik; ?></td>
                                <td class="fw-bold fs-5 text-primary"><?php echo number_format($nilai_akhir, 2); ?></td>
                                <td><span class="badge bg-<?php echo ($predikat=='A'?'success':($predikat=='B'?'info':($predikat=='C'?'warning':'danger'))); ?>"><?php echo $predikat; ?></span></td>
                            </tr>
                            <?php 
                                endwhile;
                            } else {
                                echo "<tr><td colspan='9' class='text-center py-4'>Tidak ada siswa di kelas ini.</td></tr>";
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
