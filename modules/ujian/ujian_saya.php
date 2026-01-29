<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

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
    require_once '../../includes/footer.php';
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
    require_once '../../includes/footer.php';
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
    <h1 class="mt-4"><?= $judul_halaman; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Ujian Saya</li>
    </ol>

    <div class="row">
        <?php
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {

                $mulai   = new DateTime($row['waktu_mulai']);
                $selesai = new DateTime($row['waktu_selesai']);

                $status_pengerjaan = $row['status_pengerjaan'] ?? 'Belum';
                $status_label = '';
                $aksi_button = '';
                $card_border = 'border-secondary'; // Default border

                /* =========================================
                   LOGIKA STATUS UJIAN (FINAL & AMAN)
                ========================================= */

                if ($now < $mulai) {
                    $status_label = '<span class="badge bg-secondary"><i class="fas fa-clock me-1"></i> Belum Dimulai</span>';
                    $aksi_button = '<button class="btn btn-outline-secondary w-100" disabled>Belum Mulai</button>';
                    $card_border = 'border-secondary';

                } elseif ($now >= $mulai && $now <= $selesai) {

                    if ($status_pengerjaan === 'Belum') {
                        $status_label = '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Tersedia</span>';
                        $aksi_button = '<a href="ujian_mengerjakan.php?id=' . $row['id_ujian'] . '" class="btn btn-primary w-100"><i class="fas fa-play me-1"></i> Mulai Kerjakan</a>';
                        $card_border = 'border-primary';

                    } elseif ($status_pengerjaan === 'Mengerjakan') {
                        $status_label = '<span class="badge bg-warning text-dark"><i class="fas fa-spinner fa-spin me-1"></i> Sedang Dikerjakan</span>';
                        $aksi_button = '<a href="ujian_mengerjakan.php?id=' . $row['id_ujian'] . '" class="btn btn-warning w-100 text-dark"><strong><i class="fas fa-exclamation-circle me-1"></i> Lanjutkan</strong></a>';
                        $card_border = 'border-warning';

                    } else {
                        $status_label = '<span class="badge bg-info text-dark"><i class="fas fa-clipboard-check me-1"></i> Sudah Dikerjakan</span>';
                        $nilai  = $row['nilai_akhir'] !== null ? number_format($row['nilai_akhir'], 2) : 'Menunggu Nilai';
                        $aksi_button = '<div class="alert alert-info py-2 text-center mb-0 w-100"><strong>Nilai: ' . $nilai . '</strong></div>';
                        $card_border = 'border-info';
                    }

                } else {
                    if ($status_pengerjaan === 'Selesai' || $status_pengerjaan === 'Dinilai') {
                        $status_label = '<span class="badge bg-success"><i class="fas fa-flag-checkered me-1"></i> Selesai</span>';
                        $nilai  = $row['nilai_akhir'] !== null ? number_format($row['nilai_akhir'], 2) : 'Menunggu Nilai';
                        $aksi_button = '<div class="alert alert-success py-2 text-center mb-0 w-100"><strong>Nilai: ' . $nilai . '</strong></div>';
                        $card_border = 'border-success';
                    } else {
                        $status_label = '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Terlewat</span>';
                        $aksi_button = '<button class="btn btn-outline-danger w-100" disabled>Ujian Terlewat</button>';
                        $card_border = 'border-danger';
                    }
                }
                ?>

                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm <?= $card_border; ?>">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold"><i class="fas fa-book me-1"></i> <?= htmlspecialchars($row['nama_mapel']); ?></small>
                            <span class="badge bg-light text-dark border border-secondary">
                                <i class="fas fa-hourglass-half me-1"></i> <?= $row['durasi_menit']; ?> Menit
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title text-primary mb-3"><?= htmlspecialchars($row['judul_ujian']); ?></h5>
                            
                            <div class="mb-2 text-muted small">
                                <i class="fas fa-chalkboard-teacher me-2 text-secondary" style="width:20px;"></i>
                                <?= htmlspecialchars($row['nama_guru']); ?>
                            </div>
                            <hr>
                            <div class="mb-2 text-muted small">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-calendar-alt me-2 text-primary" style="width:20px;"></i>
                                    <span>Mulai: <?= $mulai->format('d M Y, H:i'); ?></span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-calendar-times me-2 text-danger" style="width:20px;"></i>
                                    <span>Selesai: <?= $selesai->format('d M Y, H:i'); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 pt-0 pb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Status:</small>
                                <?= $status_label; ?>
                            </div>
                            <div class="d-grid gap-2">
                                <?= $aksi_button; ?>
                            </div>
                        </div>
                    </div>
                </div>

        <?php
            }
        } else {
            echo '
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-info-circle fa-3x mb-3 text-info"></i>
                    <h4>Belum ada ujian yang tersedia saat ini.</h4>
                    <p>Silakan cek kembali nanti atau hubungi guru mata pelajaran.</p>
                </div>
            </div>';
        }
        ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
