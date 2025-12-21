<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Hasil Ujian Siswa";

// Pastikan yang login adalah guru
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk Guru.</div></div>';
    require_once 'includes/footer.php';
    exit();
}
$id_guru_login = (int)$_SESSION['id_guru'];

// 1. Ambil ID Ujian dari URL
$id_ujian = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_ujian <= 0) {
    header("Location: ujian.php?error=Ujian tidak valid");
    exit();
}

// 2. Query Detail Ujian & Validasi Kepemilikan
$query_detail = "SELECT
                    u.id_ujian, u.judul_ujian, u.status_ujian,
                    mp.nama_mapel,
                    k.nama_kelas, k.id_kelas,
                    ta.tahun_ajaran, ta.semester
                FROM ujian u
                JOIN mengajar m ON u.id_mengajar = m.id_mengajar
                JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                JOIN kelas k ON m.id_kelas = k.id_kelas
                JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran
                WHERE u.id_ujian = ? AND m.id_guru = ?";

$stmt_detail = mysqli_prepare($koneksi, $query_detail);
mysqli_stmt_bind_param($stmt_detail, "ii", $id_ujian, $id_guru_login);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);

if (mysqli_num_rows($result_detail) == 0) {
    // Jika ujian tidak ditemukan ATAU bukan milik guru ini
    header("Location: ujian.php?error=Akses ditolak atau ujian tidak ditemukan");
    exit();
}
$ujian_data = mysqli_fetch_assoc($result_detail);
$id_kelas_ujian = $ujian_data['id_kelas']; // Ambil ID kelas untuk query siswa
mysqli_stmt_close($stmt_detail);

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Hasil Ujian: <?php echo htmlspecialchars($ujian_data['judul_ujian']); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="ujian.php">Manajemen Ujian</a></li>
        <li class="breadcrumb-item active">Hasil Ujian</li>
    </ol>

    <div class="card mb-4">
         <div class="card-header"><i class="fas fa-info-circle me-1"></i>Informasi Ujian</div>
         <div class="card-body">
            <p><strong>Mapel:</strong> <?php echo htmlspecialchars($ujian_data['nama_mapel']); ?></p>
            <p><strong>Kelas:</strong> <?php echo htmlspecialchars($ujian_data['nama_kelas']); ?></p>
            <p><strong>Tahun Ajaran:</strong> <?php echo htmlspecialchars($ujian_data['tahun_ajaran'] . " (" . $ujian_data['semester'] . ")"); ?></p>
            <p><strong>Status Ujian:</strong>
                <?php
                    $status = $ujian_data['status_ujian'];
                    if ($status == 'Draft') echo '<span class="badge bg-secondary">Draft</span>';
                    elseif ($status == 'Published') echo '<span class="badge bg-success">Published</span>';
                    elseif ($status == 'Selesai') echo '<span class="badge bg-dark">Selesai</span>';
                ?>
            </p>
         </div>
    </div>


    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-users me-1"></i>
            Hasil Pengerjaan Siswa Kelas <?php echo htmlspecialchars($ujian_data['nama_kelas']); ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Waktu Mulai</th>
                            <th>Waktu Selesai</th>
                            <th>Status Pengerjaan</th>
                            <th>Nilai Akhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query: Ambil semua siswa di kelas ini, lalu LEFT JOIN ke hasil ujian
                        $query_hasil = "SELECT
                                            s.id_siswa, s.nama_lengkap,
                                            uh.waktu_mulai_mengerjakan, uh.waktu_selesai_mengerjakan, uh.status_pengerjaan, uh.nilai_akhir, uh.id_hasil
                                        FROM siswa s
                                        LEFT JOIN ujian_hasil uh ON s.id_siswa = uh.id_siswa AND uh.id_ujian = ?
                                        WHERE s.id_kelas = ?
                                        ORDER BY s.nama_lengkap ASC";

                        $stmt_hasil = mysqli_prepare($koneksi, $query_hasil);
                        mysqli_stmt_bind_param($stmt_hasil, "ii", $id_ujian, $id_kelas_ujian);
                        mysqli_stmt_execute($stmt_hasil);
                        $result_hasil = mysqli_stmt_get_result($stmt_hasil);

                        if (mysqli_num_rows($result_hasil) > 0) {
                            $nomor = 1;
                            while ($row = mysqli_fetch_assoc($result_hasil)) {
                                $status_pengerjaan = $row['status_pengerjaan'] ?? 'Belum'; // Default jika NULL (belum pernah mulai)
                                $waktu_mulai = $row['waktu_mulai_mengerjakan'] ? date('d M Y, H:i:s', strtotime($row['waktu_mulai_mengerjakan'])) : '-';
                                $waktu_selesai = $row['waktu_selesai_mengerjakan'] ? date('d M Y, H:i:s', strtotime($row['waktu_selesai_mengerjakan'])) : '-';
                                $nilai_akhir = ($status_pengerjaan == 'Selesai' || $status_pengerjaan == 'Dinilai') && $row['nilai_akhir'] !== null ? number_format($row['nilai_akhir'], 2) : '-';

                                // Tentukan warna badge status
                                $status_badge = 'bg-secondary';
                                if ($status_pengerjaan == 'Mengerjakan') $status_badge = 'bg-warning text-dark';
                                if ($status_pengerjaan == 'Selesai') $status_badge = 'bg-info text-dark';
                                if ($status_pengerjaan == 'Dinilai') $status_badge = 'bg-primary'; // Misal, jika ada proses review manual

                                echo "<tr>";
                                echo "<td>" . $nomor++ . "</td>";
                                echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                                echo "<td>" . $waktu_mulai . "</td>";
                                echo "<td>" . $waktu_selesai . "</td>";
                                echo "<td><span class='badge " . $status_badge . "'>" . $status_pengerjaan . "</span></td>";
                                echo "<td><strong>" . $nilai_akhir . "</strong></td>";
                                echo "<td>";
                                // Tambahkan tombol aksi jika diperlukan, misal lihat detail jawaban
                                if ($row['id_hasil']) {
                                   // echo "<a href='ujian_jawaban_detail.php?id_hasil=" . $row['id_hasil'] . "' class='btn btn-light btn-sm' title='Lihat Jawaban'><i class='fas fa-eye'></i></a>";
                                   echo "-"; // Sementara belum ada aksi
                                } else {
                                   echo "-";
                                }
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>Tidak ada siswa di kelas ini.</td></tr>";
                        }
                        mysqli_stmt_close($stmt_hasil);
                        mysqli_close($koneksi);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>