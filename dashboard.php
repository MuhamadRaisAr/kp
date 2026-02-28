<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

// Atur judul halaman
$judul_halaman = "Dashboard";

// Ambil role dan ID user dari session
$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'guest';
$id_guru_login = isset($_SESSION['id_guru']) ? (int)$_SESSION['id_guru'] : 0;
$id_siswa_login = isset($_SESSION['id_siswa']) ? (int)$_SESSION['id_siswa'] : 0;
$id_user_login = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0; // Untuk query pengumuman

// =================================================================
// LOGIKA PENGAMBILAN DATA KARTU STATISTIK & GRAFIK (TIDAK BERUBAH)
// =================================================================
$chart_data_nilai = [];
$chart_labels_nilai = [];
$chart_data_jurusan = [];
$chart_labels_jurusan = [];

if ($role == 'admin') {
    // --- DATA KARTU STATISTIK UNTUK ADMIN ---
    $total_siswa = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_siswa) AS total FROM siswa"))['total'];
    $total_guru = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_guru) AS total FROM guru"))['total'];
    $total_kelas = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id_kelas) AS total FROM kelas"))['total'];

    // --- DATA GRAFIK UNTUK ADMIN ---
    $query_chart_nilai = "SELECT mp.nama_mapel, AVG(n.nilai) AS rata_rata
                          FROM nilai n
                          JOIN mengajar m ON n.id_mengajar = m.id_mengajar
                          JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                          GROUP BY mp.nama_mapel ORDER BY rata_rata DESC";
    $result_chart_nilai = mysqli_query($koneksi, $query_chart_nilai);
    while($row = mysqli_fetch_assoc($result_chart_nilai)) {
        $chart_labels_nilai[] = $row['nama_mapel'];
        $chart_data_nilai[] = round($row['rata_rata'], 2);
    }
    $query_chart_jurusan = "SELECT j.nama_jurusan, COUNT(s.id_siswa) AS jumlah_siswa
                            FROM siswa s
                            JOIN kelas k ON s.id_kelas = k.id_kelas
                            JOIN jurusan j ON k.id_jurusan = j.id_jurusan
                            GROUP BY j.nama_jurusan ORDER BY jumlah_siswa DESC";
    $result_chart_jurusan = mysqli_query($koneksi, $query_chart_jurusan);
    while($row = mysqli_fetch_assoc($result_chart_jurusan)) {
        $chart_labels_jurusan[] = $row['nama_jurusan'];
        $chart_data_jurusan[] = $row['jumlah_siswa'];
    }

} elseif ($role == 'guru' && $id_guru_login > 0) {
    // --- DATA KARTU STATISTIK UNTUK GURU ---
    $total_kelas_diajar = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_kelas) AS total FROM mengajar WHERE id_guru = {$id_guru_login}"))['total'];
    $total_siswa_diajar = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_siswa) AS total FROM siswa WHERE id_kelas IN (SELECT DISTINCT id_kelas FROM mengajar WHERE id_guru = {$id_guru_login})"))['total'];
    // --- DATA GRAFIK UNTUK GURU ---
    $query_chart_nilai_guru = "SELECT k.nama_kelas, mp.nama_mapel, AVG(n.nilai) AS rata_rata
                                FROM nilai n
                                JOIN mengajar m ON n.id_mengajar = m.id_mengajar
                                JOIN kelas k ON m.id_kelas = k.id_kelas
                                JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                WHERE m.id_guru = {$id_guru_login}
                                GROUP BY k.nama_kelas, mp.nama_mapel 
                                ORDER BY rata_rata DESC";
    $result_chart_nilai_guru = mysqli_query($koneksi, $query_chart_nilai_guru);
     while($row = mysqli_fetch_assoc($result_chart_nilai_guru)) {
        $chart_labels_nilai[] = $row['nama_kelas'] . ' - ' . $row['nama_mapel'];
        $chart_data_nilai[] = round($row['rata_rata'], 2);
    }

    // --- DATA KHUSUS WALI KELAS ---
    $is_wali = isset($_SESSION['is_wali']) && $_SESSION['is_wali'];
    $nama_kelas_wali = isset($_SESSION['nama_kelas_wali']) ? $_SESSION['nama_kelas_wali'] : '';
    $id_kelas_wali = isset($_SESSION['wali_kelas_id']) ? $_SESSION['wali_kelas_id'] : 0;
    
    $total_siswa_wali = 0;
    $rata_rata_kelas_wali = 0;

    if ($is_wali && $id_kelas_wali > 0) {
        // Total Siswa di Kelas Wali
        $q_siswa_wali = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM siswa WHERE id_kelas = '$id_kelas_wali'");
        $total_siswa_wali = mysqli_fetch_assoc($q_siswa_wali)['total'];

        // Rata-rata Nilai Siswa di Kelas Wali (semua mapel)
        $q_nilai_wali = mysqli_query($koneksi, "SELECT AVG(nilai) as rata FROM nilai n JOIN siswa s ON n.id_siswa = s.id_siswa WHERE s.id_kelas = '$id_kelas_wali'");
        $rata_rata_kelas_wali = round(mysqli_fetch_assoc($q_nilai_wali)['rata'] ?? 0, 2);
    }

} elseif ($role == 'siswa' && $id_siswa_login > 0) {
    // Data kelas dan wali kelas
    $query_siswa_kelas = "SELECT k.nama_kelas, g.nama_lengkap AS nama_wali 
                          FROM siswa s 
                          LEFT JOIN kelas k ON s.id_kelas = k.id_kelas 
                          LEFT JOIN guru g ON k.id_guru_wali_kelas = g.id_guru 
                          WHERE s.id_siswa = {$id_siswa_login}";
    $result_siswa_kelas = mysqli_query($koneksi, $query_siswa_kelas);
    $data_siswa_kelas = mysqli_fetch_assoc($result_siswa_kelas);
}

// Ambil Tahun Ajaran yang Aktif (dibutuhkan oleh semua role)
$data_tahun_aktif = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM tahun_ajaran WHERE status_aktif = 'Aktif' LIMIT 1"));

// ==========================================================
// BARU: Ambil Data Pengumuman Terbaru
// ==========================================================
$pengumuman_list = [];
$query_pengumuman = "SELECT p.judul, p.isi, p.tanggal_posting, u.username AS pembuat
                     FROM pengumuman p
                     LEFT JOIN users u ON p.id_user_pembuat = u.id_user
                     WHERE p.is_aktif = 1
                       AND (p.target_role = 'semua' OR p.target_role = ?)
                     ORDER BY p.tanggal_posting DESC
                     LIMIT 5"; // Ambil 5 terbaru

$stmt_pengumuman = mysqli_prepare($koneksi, $query_pengumuman);
// Bind role user yang login ke placeholder '?'
mysqli_stmt_bind_param($stmt_pengumuman, "s", $role);
mysqli_stmt_execute($stmt_pengumuman);
$result_pengumuman = mysqli_stmt_get_result($stmt_pengumuman);
while($row = mysqli_fetch_assoc($result_pengumuman)) {
    $pengumuman_list[] = $row;
}
mysqli_stmt_close($stmt_pengumuman);
// ==========================================================
?>

<div class="container-fluid px-4">
    <?php if (isset($_GET['login']) && $_GET['login'] == 'success'): ?>
    <div class="alert alert-success mt-3" role="alert">
        <strong>Login Berhasil!</strong> Selamat datang kembali, Anda telah berhasil login.
    </div>
    <?php endif; ?>

    <h1 class="mt-4">Selamat Datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p class="lead">Anda login sebagai <strong><?php echo htmlspecialchars(ucfirst($role)); ?></strong><?php if(isset($is_wali) && $is_wali) echo ' & <strong>Wali Kelas</strong> (' . htmlspecialchars($nama_kelas_wali) . ')'; ?>.</p>

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-bullhorn me-1"></i>
                    Pengumuman Terbaru
                </div>
                <!-- ... content ... -->
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <?php if (!empty($pengumuman_list)): ?>
                        <?php foreach ($pengumuman_list as $pengumuman): ?>
                            <div class="alert alert-light border mb-3">
                                <h5 class="alert-heading"><?php echo htmlspecialchars($pengumuman['judul']); ?></h5>
                                <p><?php echo nl2br(htmlspecialchars($pengumuman['isi'])); ?></p>
                                <hr class="my-2">
                                <p class="mb-0 small text-muted">
                                    Diposting oleh: <?php echo htmlspecialchars($pengumuman['pembuat'] ?? 'Administrator'); ?>
                                    - <?php echo date('d M Y, H:i', strtotime($pengumuman['tanggal_posting'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted">Belum ada pengumuman.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <?php if ($role == 'admin'): ?>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card bg-primary text-white h-100 shadow">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold"><?php echo $total_siswa; ?></div>
                            <div class="text-uppercase small">Total Siswa</div>
                        </div>
                        <i class="fas fa-user-graduate fa-3x opacity-50"></i>
                    </div>
                    <a class="card-footer text-white clearfix small z-1" href="modules/siswa/siswa.php">
                        <span class="float-start">Lihat Detail</span>
                        <span class="float-end"><i class="fas fa-angle-right"></i></span>
                    </a>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                 <div class="card bg-warning text-white h-100 shadow">
                     <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold"><?php echo $total_guru; ?></div>
                            <div class="text-uppercase small">Total Guru</div>
                        </div>
                        <i class="fas fa-chalkboard-teacher fa-3x opacity-50"></i>
                    </div>
                     <a class="card-footer text-white clearfix small z-1" href="modules/guru/guru.php">
                        <span class="float-start">Lihat Detail</span>
                        <span class="float-end"><i class="fas fa-angle-right"></i></span>
                    </a>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card bg-success text-white h-100 shadow">
                     <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold"><?php echo $total_kelas; ?></div>
                            <div class="text-uppercase small">Total Kelas</div>
                        </div>
                        <i class="fas fa-door-open fa-3x opacity-50"></i>
                    </div>
                     <a class="card-footer text-white clearfix small z-1" href="modules/kelas/kelas.php">
                        <span class="float-start">Lihat Detail</span>
                        <span class="float-end"><i class="fas fa-angle-right"></i></span>
                    </a>
                </div>
            </div>

        <?php elseif ($role == 'guru'): ?>
            <?php if (isset($is_wali) && $is_wali): ?>
            <!-- AREA KHUSUS WALI KELAS -->
            <div class="col-12 mb-3">
                <h5 class="text-primary border-bottom pb-2"><i class="fas fa-user-tie me-2"></i>Area Wali Kelas: <?php echo htmlspecialchars($nama_kelas_wali); ?></h5>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card bg-info text-white h-100 shadow">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold"><?php echo $total_siswa_wali; ?></div>
                            <div class="text-uppercase small">Total Siswa Perwalian</div>
                        </div>
                        <i class="fas fa-users-cog fa-3x opacity-50"></i>
                    </div>
                    <a class="card-footer text-white clearfix small z-1" href="modules/nilai/cetak_rapot.php">
                        <span class="float-start">Kelola Rapor Siswa</span>
                        <span class="float-end"><i class="fas fa-angle-right"></i></span>
                    </a>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card bg-warning text-white h-100 shadow">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold"><?php echo $rata_rata_kelas_wali; ?></div>
                            <div class="text-uppercase small">Rata-rata Nilai Kelas</div>
                        </div>
                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                    </div>
                    <div class="card-footer text-white clearfix small z-1">
                        <span class="float-start">Indeks Prestasi Kelas</span>
                    </div>
                </div>
            </div>
            <!-- Spacer/Divider if needed -->
            <div class="col-12 mb-3 mt-2">
                <h5 class="text-secondary border-bottom pb-2"><i class="fas fa-chalkboard-teacher me-2"></i>Area Pengajar</h5>
            </div>
            <?php endif; ?>

            <!-- AREA GURU BIASA -->
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card bg-primary text-white h-100 shadow">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold"><?php echo $total_siswa_diajar ?? 0; ?></div>
                            <div class="text-uppercase small">Total Siswa Diajar</div>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                    <a class="card-footer text-white clearfix small z-1" href="modules/nilai/nilai.php">
                        <span class="float-start">Kelola Nilai</span>
                        <span class="float-end"><i class="fas fa-angle-right"></i></span>
                    </a>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card bg-success text-white h-100 shadow">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold"><?php echo $total_kelas_diajar ?? 0; ?></div>
                            <div class="text-uppercase small">Jumlah Kelas Diajar</div>
                        </div>
                        <i class="fas fa-chalkboard fa-3x opacity-50"></i>
                    </div>
                    <a class="card-footer text-white clearfix small z-1" href="modules/mengajar/mengajar.php">
                        <span class="float-start">Lihat Jadwal</span>
                        <span class="float-end"><i class="fas fa-angle-right"></i></span>
                    </a>
                </div>
            </div>

        <?php elseif ($role == 'siswa'): ?>
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card bg-info text-white h-100 shadow">
                     <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">
                                <?php echo !empty($data_siswa_kelas['nama_kelas']) ? htmlspecialchars($data_siswa_kelas['nama_kelas']) : "Belum ditentukan"; ?>
                            </div>
                            <div class="text-uppercase small">Kelas Saya</div>
                        </div>
                        <i class="fas fa-door-open fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-xl-6 col-md-6 mb-4">
                <div class="card bg-success text-white h-100 shadow">
                     <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-4 fw-bold">
                                <?php echo !empty($data_siswa_kelas['nama_wali']) ? htmlspecialchars($data_siswa_kelas['nama_wali']) : "Belum ditentukan"; ?>
                            </div>
                            <div class="text-uppercase small">Wali Kelas</div>
                        </div>
                        <i class="fas fa-user-tie fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="col-xl-<?php
            if($role == 'admin') echo '3';
            elseif($role == 'guru') echo '4';
            else echo '12';
        ?> col-md-6 mb-4">
            <div class="card bg-danger text-white h-100 shadow">
                 <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-5 fw-bold">
                            <?php echo $data_tahun_aktif ? htmlspecialchars($data_tahun_aktif['tahun_ajaran']) . " (" . htmlspecialchars($data_tahun_aktif['semester']) . ")" : "Tidak Ada"; ?>
                        </div>
                        <div class="text-uppercase small">Tahun Ajaran Aktif</div>
                    </div>
                    <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                </div>
                <?php if ($role == 'admin'): ?>
                 <a class="card-footer text-white clearfix small z-1" href="modules/tahun_ajaran/tahun_ajaran.php">
                    <span class="float-start">Kelola</span>
                    <span class="float-end"><i class="fas fa-angle-right"></i></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <?php if ($role == 'admin'): ?>
            <div class="col-lg-6">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><i class="fas fa-chart-bar me-1"></i>Rata-rata Nilai per Mata Pelajaran</div>
                    <div class="card-body"><canvas id="chartNilaiMapel"></canvas></div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><i class="fas fa-chart-pie me-1"></i>Distribusi Siswa per Jurusan</div>
                    <div class="card-body"><canvas id="chartJurusan"></canvas></div>
                </div>
            </div>
        <?php elseif ($role == 'guru'): ?>
            <div class="col-lg-12">
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><i class="fas fa-chart-bar me-1"></i>Rata-rata Nilai per Kelas yang Diajar</div>
                    <div class="card-body">
                        <?php if (!empty($chart_labels_nilai)): ?>
                            <canvas id="chartNilaiGuru"></canvas>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-chart-bar fa-3x mb-3 d-block opacity-25"></i>
                                Belum ada data nilai yang diinput.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php elseif ($role == 'siswa'): ?>
            <?php endif; ?>
    </div>
</div>

<?php
// Jangan lupa tutup koneksi jika belum ditutup di footer
// mysqli_close($koneksi); // Jika footer.php tidak menutupnya
require_once 'includes/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($role == 'admin'): ?>
        const ctxNilaiMapel = document.getElementById('chartNilaiMapel');
        const ctxJurusan = document.getElementById('chartJurusan');
        
        <?php if (!empty($chart_labels_nilai)): ?>
        if (ctxNilaiMapel) {
            new Chart(ctxNilaiMapel, {
                type: 'bar',
                data: { labels: <?php echo json_encode($chart_labels_nilai); ?>, datasets: [{ label: 'Rata-rata Nilai', data: <?php echo json_encode($chart_data_nilai); ?>, backgroundColor: 'rgba(54, 162, 235, 0.7)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 }] },
                options: { scales: { y: { beginAtZero: true, max: 100 } } }
            });
        }
        <?php endif; ?>

        <?php if (!empty($chart_labels_jurusan)): ?>
        if (ctxJurusan) {
            new Chart(ctxJurusan, {
                type: 'pie',
                data: { labels: <?php echo json_encode($chart_labels_jurusan); ?>, datasets: [{ label: 'Jumlah Siswa', data: <?php echo json_encode($chart_data_jurusan); ?>, backgroundColor: ['rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)','rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)','rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)'] }] }
            });
        }
        <?php endif; ?>

    <?php elseif ($role == 'guru'): ?>
        const ctxNilaiGuru = document.getElementById('chartNilaiGuru');
        
        <?php if (!empty($chart_labels_nilai)): ?>
        if (ctxNilaiGuru) {
             new Chart(ctxNilaiGuru, {
                type: 'bar',
                data: { 
                    labels: <?php echo json_encode($chart_labels_nilai); ?>, 
                    datasets: [{ 
                        label: 'Rata-rata Nilai Kelas', 
                        data: <?php echo json_encode($chart_data_nilai); ?>, 
                        backgroundColor: 'rgba(75, 192, 192, 0.7)', 
                        borderColor: 'rgba(75, 192, 192, 1)', 
                        borderWidth: 1 
                    }] 
                },
                options: { 
                    scales: { y: { beginAtZero: true, max: 100 } },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        <?php endif; ?>
    <?php endif; ?>
});
</script>