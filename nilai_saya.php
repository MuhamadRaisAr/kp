<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Lihat Nilai Saya";

// --- VALIDASI AKSES ---
$role_check = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';

if ($role_check !== 'siswa') {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk siswa.</div></div>';
    require_once 'includes/footer.php';
    exit();
}

if (empty($_SESSION['id_siswa'])) {
    echo '<div class="container-fluid px-4"><div class="alert alert-warning mt-4">Akses Ditolak. Akun Anda tidak terhubung dengan data siswa yang valid.</div></div>';
    require_once 'includes/footer.php';
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

// --- LOGIKA FILTER ---
// Mengambil ID tahun ajaran hanya jika form dikirim (tombol diklik)
$selected_tahun = isset($_GET['tahun_ajaran']) ? (int)$_GET['tahun_ajaran'] : null;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Nilai Akademik Saya</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Lihat Nilai</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i>Pilih Periode</div>
        <div class="card-body">
            <form method="GET" action="nilai_saya.php">
                <div class="row">
                    <div class="col-md-8">
                        <select name="tahun_ajaran" id="tahun_ajaran" class="form-select" required>
                            <option value="">-- Pilih Tahun Ajaran & Semester --</option>
                            <?php 
                            $result_tahun = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC, semester DESC");
                            while($row = mysqli_fetch_assoc($result_tahun)) {
                                $selected = ($row['id_tahun_ajaran'] == $selected_tahun) ? 'selected' : '';
                                echo "<option value='{$row['id_tahun_ajaran']}' $selected>" . htmlspecialchars($row['tahun_ajaran']) . " - " . htmlspecialchars($row['semester']) . "</option>";
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Tampilkan Nilai
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    // --- TAMPILKAN TABEL HANYA JIKA TOMBOL SUDAH DIKLIK ---
    if ($selected_tahun) :
        $query_nilai = "SELECT 
                            mp.nama_mapel, 
                            n.jenis_nilai, 
                            n.nilai 
                        FROM nilai n
                        JOIN mengajar m ON n.id_mengajar = m.id_mengajar
                        JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                        WHERE n.id_siswa = ? AND m.id_tahun_ajaran = ?";
        
        $stmt_nilai = mysqli_prepare($koneksi, $query_nilai);
        mysqli_stmt_bind_param($stmt_nilai, "ii", $id_siswa_login, $selected_tahun);
        mysqli_stmt_execute($stmt_nilai);
        $result_nilai = mysqli_stmt_get_result($stmt_nilai);

        $nilai_per_mapel = [];
        while($row = mysqli_fetch_assoc($result_nilai)) {
            $nilai_per_mapel[$row['nama_mapel']][$row['jenis_nilai']] = $row['nilai'];
        }
    ?>
        <div class="card mb-4">
            <div class="card-header"><i class="fas fa-book-open me-1"></i>Daftar Nilai</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th class="text-start">Mata Pelajaran</th>
                                <th>Tugas</th>
                                <th>UTS</th>
                                <th>UAS</th>
                                <th>Praktik</th>
                                <th>Nilai Akhir</th>
                                <th>Predikat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (count($nilai_per_mapel) > 0) {
                                $nomor = 1;
                                foreach ($nilai_per_mapel as $mapel => $nilai):
                                    $tugas = $nilai['Tugas'] ?? 0;
                                    $uts = $nilai['UTS'] ?? 0;
                                    $uas = $nilai['UAS'] ?? 0;
                                    $praktik = $nilai['Praktik'] ?? 0;
                                    $nilai_akhir = hitungNilaiAkhir($tugas, $uts, $uas, $praktik);
                                    $predikat = tentukanPredikat($nilai_akhir);
                            ?>
                            <tr>
                                <td><?php echo $nomor++; ?></td>
                                <td class="text-start"><?php echo htmlspecialchars($mapel); ?></td>
                                <td><?php echo $tugas; ?></td>
                                <td><?php echo $uts; ?></td>
                                <td><?php echo $uas; ?></td>
                                <td><?php echo $praktik; ?></td>
                                <td><strong><?php echo number_format($nilai_akhir, 2); ?></strong></td>
                                <td><span class="badge bg-primary"><?php echo $predikat; ?></span></td>
                            </tr>
                            <?php 
                                endforeach;
                            } else {
                                echo "<tr><td colspan='8' class='py-4'>Belum ada data nilai untuk periode yang dipilih.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Silakan pilih Tahun Ajaran dan Semester, kemudian klik <strong>Tampilkan Nilai</strong> untuk melihat hasil belajar Anda.
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>