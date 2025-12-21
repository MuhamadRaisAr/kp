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

// Ambil filter tahun ajaran dari URL
$selected_tahun = isset($_GET['tahun_ajaran']) ? (int)$_GET['tahun_ajaran'] : '';

// Jika tidak ada filter, ambil tahun ajaran yang aktif sebagai default
if (empty($selected_tahun)) {
    $q_tahun_aktif = mysqli_query($koneksi, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status_aktif = 'Aktif' LIMIT 1");
    if ($q_tahun_aktif && mysqli_num_rows($q_tahun_aktif) > 0) {
        $selected_tahun = mysqli_fetch_assoc($q_tahun_aktif)['id_tahun_ajaran'];
    }
}
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
                            $result_tahun = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC");
                            while($row = mysqli_fetch_assoc($result_tahun)) {
                                $selected = ($row['id_tahun_ajaran'] == $selected_tahun) ? 'selected' : '';
                                echo "<option value='{$row['id_tahun_ajaran']}' $selected>" . htmlspecialchars($row['tahun_ajaran']) . " - " . htmlspecialchars($row['semester']) . "</option>";
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Tampilkan Nilai</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Tampilkan tabel nilai HANYA JIKA tahun ajaran sudah dipilih
    if (!empty($selected_tahun)) :
        // Query untuk mengambil semua nilai siswa pada semester yang dipilih
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

        // Olah data nilai ke dalam format yang mudah digunakan
        $nilai_per_mapel = [];
        while($row = mysqli_fetch_assoc($result_nilai)) {
            $nilai_per_mapel[$row['nama_mapel']][$row['jenis_nilai']] = $row['nilai'];
        }
    ?>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-book-open me-1"></i>Daftar Nilai Semester Ini</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
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
                            <td><?php echo number_format($nilai_akhir, 2); ?></td>
                            <td><strong><?php echo $predikat; ?></strong></td>
                        </tr>
                        <?php 
                            endforeach;
                        } else {
                            echo "<tr><td colspan='8' class='text-center'>Belum ada data nilai untuk semester ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>