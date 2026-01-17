<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

/* =================================================
   KONFIGURASI DASAR
================================================= */
date_default_timezone_set('Asia/Jakarta');
mysqli_query($koneksi, "SET time_zone = '+07:00'");

$judul_halaman = "Daftar Ujian Saya";

/* =================================================
   VALIDASI LOGIN SISWA
================================================= */
if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'siswa' ||
    empty($_SESSION['id_siswa'])
) {
    echo '<div class="container-fluid px-4">
            <div class="alert alert-danger mt-4">
                Akses ditolak. Halaman ini hanya untuk Siswa.
            </div>
          </div>';
    require_once 'includes/footer.php';
    exit;
}

$id_siswa = (int) $_SESSION['id_siswa'];

/* =================================================
   AMBIL KELAS SISWA
================================================= */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id_kelas FROM siswa WHERE id_siswa = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id_siswa);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data_siswa = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$data_siswa || empty($data_siswa['id_kelas'])) {
    echo '<div class="container-fluid px-4">
            <div class="alert alert-warning mt-4">
                Data kelas tidak ditemukan. Hubungi Admin.
            </div>
          </div>';
    require_once 'includes/footer.php';
    exit;
}

$id_kelas = (int) $data_siswa['id_kelas'];

/* =================================================
   WAKTU SAAT INI
================================================= */
$now = new DateTime();

/* =================================================
   QUERY UJIAN (SUDAH FIX)
================================================= */
$query = "
SELECT
    u.id_ujian,
    u.judul_ujian,
    u.durasi_menit,
    u.waktu_mulai,
    u.waktu_selesai,
    mp.nama_mapel,
    g.nama_lengkap AS nama_guru,
    uh.status_pengerjaan,
    uh.nilai_akhir
FROM ujian u
JOIN mengajar m ON u.id_mengajar = m.id_mengajar
JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
JOIN guru g ON m.id_guru = g.id_guru
LEFT JOIN ujian_hasil uh
    ON u.id_ujian = uh.id_ujian
    AND uh.id_siswa = ?
WHERE u.status_ujian = 'Published'
  AND m.id_kelas = ?
ORDER BY u.waktu_mulai ASC
";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_siswa, $id_kelas);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Daftar Ujian</h1>

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
                            <th>Judul</th>
                            <th>Guru</th>
                            <th>Durasi</th>
                            <th>Mulai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

<?php
if (mysqli_num_rows($result) > 0) {
    $no = 1;

    while ($row = mysqli_fetch_assoc($result)) {

        $mulai   = new DateTime($row['waktu_mulai']);
        $selesai = new DateTime($row['waktu_selesai']);

        $status_pengerjaan = $row['status_pengerjaan'] ?? 'Belum';
        $status = '';
        $aksi   = '';

        /* =========================================
           LOGIKA STATUS UJIAN (FINAL & AMAN)
        ========================================= */

        if ($now < $mulai) {
            $status = '<span class="badge bg-secondary">Belum Dimulai</span>';
            $aksi   = '<button class="btn btn-secondary btn-sm" disabled>Belum Mulai</button>';

        } elseif ($now >= $mulai && $now <= $selesai) {

            if ($status_pengerjaan === 'Belum') {
                $status = '<span class="badge bg-success">Tersedia</span>';
                $aksi   = '<a href="ujian_mengerjakan.php?id=' . $row['id_ujian'] . '" class="btn btn-primary btn-sm">Mulai</a>';

            } elseif ($status_pengerjaan === 'Mengerjakan') {
                $status = '<span class="badge bg-warning text-dark">Sedang Dikerjakan</span>';
                $aksi   = '<a href="ujian_mengerjakan.php?id=' . $row['id_ujian'] . '" class="btn btn-warning btn-sm">Lanjutkan</a>';

            } else {
                $status = '<span class="badge bg-info text-dark">Sudah Dikerjakan</span>';
                $nilai  = $row['nilai_akhir'] !== null
                          ? number_format($row['nilai_akhir'], 2)
                          : 'Menunggu';
                $aksi   = '<button class="btn btn-info btn-sm" disabled>Nilai: ' . $nilai . '</button>';
            }

        } else {
            if ($status_pengerjaan === 'Selesai' || $status_pengerjaan === 'Dinilai') {
                $status = '<span class="badge bg-dark">Selesai</span>';
                $nilai  = $row['nilai_akhir'] !== null
                          ? number_format($row['nilai_akhir'], 2)
                          : 'Menunggu';
                $aksi   = '<button class="btn btn-dark btn-sm" disabled>Nilai: ' . $nilai . '</button>';
            } else {
                $status = '<span class="badge bg-danger">Terlewat</span>';
                $aksi   = '<button class="btn btn-danger btn-sm" disabled>Terlewat</button>';
            }
        }

        echo '<tr>
            <td>' . $no++ . '</td>
            <td>' . htmlspecialchars($row['nama_mapel']) . '</td>
            <td>' . htmlspecialchars($row['judul_ujian']) . '</td>
            <td>' . htmlspecialchars($row['nama_guru']) . '</td>
            <td>' . $row['durasi_menit'] . ' Menit</td>
            <td>' . $mulai->format('d M Y H:i') . '</td>
            <td>' . $status . '</td>
            <td>' . $aksi . '</td>
        </tr>';
    }
} else {
    echo '<tr><td colspan="8" class="text-center">Tidak ada ujian.</td></tr>';
}

mysqli_stmt_close($stmt);
?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
