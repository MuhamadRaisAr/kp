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
    $query_chart_nilai_guru = "SELECT k.nama_kelas, AVG(n.nilai) AS rata_rata
                                FROM nilai n
                                JOIN mengajar m ON n.id_mengajar = m.id_mengajar
                                JOIN kelas k ON m.id_kelas = k.id_kelas
                                WHERE m.id_guru = {$id_guru_login}
                                GROUP BY k.nama_kelas ORDER BY rata_rata DESC";
    $result_chart_nilai_guru = mysqli_query($koneksi, $query_chart_nilai_guru);
     while($row = mysqli_fetch_assoc($result_chart_nilai_guru)) {
        $chart_labels_nilai[] = $row['nama_kelas'];
        $chart_data_nilai[] = round($row['rata_rata'], 2);
    }

} elseif ($role == 'siswa' && $id_siswa_login > 0) {
    // Tidak ada data kartu spesifik untuk siswa di dashboard ini
    // Data absensi sudah dihapus sebelumnya
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
    <p class="lead">Anda login sebagai <strong><?php echo htmlspecialchars(ucfirst($role)); ?></strong>.</p>

    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-bullhorn me-1"></i>
                    Pengumuman Terbaru
                </div>
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
                    <a class="card-footer text-white clearfix small z-1" href="siswa.php">
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
                     <a class="card-footer text-white clearfix small z-1" href="guru.php">
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
                     <a class="card-footer text-white clearfix small z-1" href="kelas.php">
                        <span class="float-start">Lihat Detail</span>
                        <span class="float-end"><i class="fas fa-angle-right"></i></span>
                    </a>
                </div>
            </div>

        <?php elseif ($role == 'guru'): ?>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card bg-primary text-white h-100 shadow">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3 fw-bold"><?php echo $total_siswa_diajar ?? 0; ?></div>
                            <div class="text-uppercase small">Total Siswa Diajar</div>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                    <a class="card-footer text-white clearfix small z-1" href="nilai.php">
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
                    <a class="card-footer text-white clearfix small z-1" href="mengajar.php">
                        <span class="float-start">Lihat Jadwal</span>
                        <span class="float-end"><i class="fas fa-angle-right"></i></span>
                    </a>
                </div>
            </div>

        <?php elseif ($role == 'siswa'): ?>
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
                 <a class="card-footer text-white clearfix small z-1" href="tahun_ajaran.php">
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
                    <div class="card-body"><canvas id="chartNilaiGuru"></canvas></div>
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
        if (ctxNilaiMapel) { /* ... kode chart admin ... */ }
        const ctxJurusan = document.getElementById('chartJurusan');
        if (ctxJurusan) { /* ... kode chart admin ... */ }
    <?php elseif ($role == 'guru'): ?>
        const ctxNilaiGuru = document.getElementById('chartNilaiGuru');
        if (ctxNilaiGuru) { /* ... kode chart guru ... */ }
    <?php endif; ?>
    // Pastikan kode chart yang lama masih ada di sini
    // Saya singkat di sini agar tidak terlalu panjang
    <?php if ($role == 'admin' && !empty($chart_labels_nilai)): ?>
        new Chart(ctxNilaiMapel, {
            type: 'bar',
            data: { labels: <?php echo json_encode($chart_labels_nilai); ?>, datasets: [{ label: 'Rata-rata Nilai', data: <?php echo json_encode($chart_data_nilai); ?>, backgroundColor: 'rgba(54, 162, 235, 0.7)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 }] },
            options: { scales: { y: { beginAtZero: true, max: 100 } } }
        });
    <?php endif; ?>
     <?php if ($role == 'admin' && !empty($chart_labels_jurusan)): ?>
        new Chart(ctxJurusan, {
            type: 'pie',
            data: { labels: <?php echo json_encode($chart_labels_jurusan); ?>, datasets: [{ label: 'Jumlah Siswa', data: <?php echo json_encode($chart_data_jurusan); ?>, backgroundColor: ['rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)','rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)','rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)'] }] }
        });
     <?php endif; ?>
     <?php if ($role == 'guru' && !empty($chart_labels_nilai)): ?>
         new Chart(ctxNilaiGuru, {
            type: 'bar',
            data: { labels: <?php echo json_encode($chart_labels_nilai); ?>, datasets: [{ label: 'Rata-rata Nilai Kelas', data: <?php echo json_encode($chart_data_nilai); ?>, backgroundColor: 'rgba(75, 192, 192, 0.7)', borderColor: 'rgba(75, 192, 192, 1)', borderWidth: 1 }] },
            options: { scales: { y: { beginAtZero: true, max: 100 } } }
        });
     <?php endif; ?>
});
</script>