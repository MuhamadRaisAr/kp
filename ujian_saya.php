<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php'; // Memastikan login & set role
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Daftar Ujian Saya";

// Pastikan yang login adalah siswa dan ID siswa ada
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa' || !isset($_SESSION['id_siswa']) || empty($_SESSION['id_siswa'])) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk Siswa.</div></div>';
    require_once 'includes/footer.php';
    exit();
}
$id_siswa_login = (int)$_SESSION['id_siswa'];

// Waktu server saat ini
$waktu_sekarang_dt = new DateTime('now', new DateTimeZone('Asia/Jakarta')); // Sesuaikan timezone jika perlu
$waktu_sekarang_sql = $waktu_sekarang_dt->format('Y-m-d H:i:s');

// Ambil id_kelas siswa yang login
$stmt_kelas_siswa = mysqli_prepare($koneksi, "SELECT id_kelas FROM siswa WHERE id_siswa = ?");
mysqli_stmt_bind_param($stmt_kelas_siswa, "i", $id_siswa_login);
mysqli_stmt_execute($stmt_kelas_siswa);
$result_kelas_siswa = mysqli_stmt_get_result($stmt_kelas_siswa);
$data_siswa = mysqli_fetch_assoc($result_kelas_siswa);
$id_kelas_siswa = $data_siswa ? $data_siswa['id_kelas'] : null;
mysqli_stmt_close($stmt_kelas_siswa);

// Jika ID kelas tidak ditemukan, beri pesan
if (!$id_kelas_siswa) {
    echo '<div class="container-fluid px-4"><div class="alert alert-warning mt-4">Data kelas Anda tidak ditemukan. Hubungi Administrator.</div></div>';
    require_once 'includes/footer.php';
    exit();
}

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Daftar Ujian</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Ujian</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-list me-1"></i>
            Ujian yang Tersedia
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Mata Pelajaran</th>
                            <th>Judul Ujian</th>
                            <th>Guru</th>
                            <th>Durasi</th>
                            <th>Jadwal Mulai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ============================================
                        // PERBAIKAN QUERY DI SINI
                        // ============================================
                        $query_ujian = "SELECT
                                            u.id_ujian, u.judul_ujian, u.durasi_menit, u.waktu_mulai, u.waktu_selesai,
                                            mp.nama_mapel,
                                            g.nama_lengkap AS nama_guru, -- <<< Ubah g.nama_guru menjadi g.nama_lengkap AS nama_guru
                                            uh.status_pengerjaan, uh.nilai_akhir
                                        FROM ujian u
                                        JOIN mengajar m ON u.id_mengajar = m.id_mengajar
                                        JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                        JOIN guru g ON m.id_guru = g.id_guru
                                        LEFT JOIN ujian_hasil uh ON u.id_ujian = uh.id_ujian AND uh.id_siswa = ?
                                        WHERE u.status_ujian = 'Published'
                                          AND m.id_kelas = ?
                                          AND u.waktu_selesai > ?
                                        ORDER BY u.waktu_mulai ASC";
                        // ============================================
                        // AKHIR PERBAIKAN QUERY
                        // ============================================

                        $stmt = mysqli_prepare($koneksi, $query_ujian);
                        mysqli_stmt_bind_param($stmt, "iis", $id_siswa_login, $id_kelas_siswa, $waktu_sekarang_sql);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        if (mysqli_num_rows($result) > 0) {
                            $nomor = 1;
                            while ($row = mysqli_fetch_assoc($result)) {
                                $waktu_mulai_ujian_dt = new DateTime($row['waktu_mulai']);
                                $waktu_selesai_ujian_dt = new DateTime($row['waktu_selesai']);

                                $status_pengerjaan = $row['status_pengerjaan'] ?? 'Belum'; // Default jika NULL
                                $status_ujian = '';
                                $aksi_button = '';

                                // Cek status ujian berdasarkan waktu & status pengerjaan siswa
                                if ($waktu_sekarang_dt < $waktu_mulai_ujian_dt) {
                                    $status_ujian = '<span class="badge bg-secondary">Belum Dimulai</span>';
                                    $aksi_button = '<button class="btn btn-secondary btn-sm" disabled>Belum Mulai</button>';
                                } elseif ($waktu_sekarang_dt >= $waktu_mulai_ujian_dt && $waktu_sekarang_dt <= $waktu_selesai_ujian_dt) {
                                    if ($status_pengerjaan == 'Belum') {
                                        $status_ujian = '<span class="badge bg-success">Tersedia</span>';
                                        $aksi_button = '<a href="ujian_konfirmasi.php?id=' . $row['id_ujian'] . '" class="btn btn-primary btn-sm">Mulai Kerjakan</a>';
                                    } elseif ($status_pengerjaan == 'Mengerjakan') {
                                        $status_ujian = '<span class="badge bg-warning text-dark">Sedang Dikerjakan</span>';
                                         // Idealnya tombol 'Lanjutkan', tapi kita buat 'Mulai' dulu
                                        $aksi_button = '<a href="ujian_konfirmasi.php?id=' . $row['id_ujian'] . '" class="btn btn-warning btn-sm text-dark">Lanjutkan</a>';
                                    } elseif ($status_pengerjaan == 'Selesai' || $status_pengerjaan == 'Dinilai') {
                                        $status_ujian = '<span class="badge bg-info text-dark">Sudah Dikerjakan</span>';
                                        $nilai_tampil = ($row['nilai_akhir'] !== null) ? number_format($row['nilai_akhir'], 2) : 'Menunggu Dinilai';
                                        $aksi_button = '<button class="btn btn-info btn-sm text-dark" disabled>Nilai: ' . $nilai_tampil . '</button>';
                                    }
                                } else { // Sudah lewat waktu selesai
                                     if ($status_pengerjaan == 'Selesai' || $status_pengerjaan == 'Dinilai') {
                                        $status_ujian = '<span class="badge bg-dark">Sudah Selesai</span>';
                                        $nilai_tampil = ($row['nilai_akhir'] !== null) ? number_format($row['nilai_akhir'], 2) : 'Menunggu Dinilai';
                                        $aksi_button = '<button class="btn btn-dark btn-sm" disabled>Nilai: ' . $nilai_tampil . '</button>';
                                     } else {
                                        $status_ujian = '<span class="badge bg-danger">Terlewat</span>';
                                        $aksi_button = '<button class="btn btn-danger btn-sm" disabled>Terlewat</button>';
                                     }
                                }

                                echo "<tr>";
                                echo "<td>" . $nomor++ . "</td>";
                                echo "<td>" . htmlspecialchars($row['nama_mapel']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['judul_ujian']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['nama_guru']) . "</td>"; // Ini tetap pakai nama_guru karena alias
                                echo "<td>" . $row['durasi_menit'] . " Menit</td>";
                                echo "<td>" . $waktu_mulai_ujian_dt->format('d M Y, H:i') . "</td>";
                                echo "<td>" . $status_ujian . "</td>";
                                echo "<td>" . $aksi_button . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center'>Belum ada ujian yang tersedia untuk Anda saat ini.</td></tr>";
                        }
                        mysqli_stmt_close($stmt);
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