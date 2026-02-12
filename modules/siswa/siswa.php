<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Daftar Siswa";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Daftar Siswa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Daftar Siswa</li>
    </ol>

    <div class="row">
        <?php
        // Ambil data tahun ajaran
        $query = "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC, semester DESC";
        $result = mysqli_query($koneksi, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $status_class = ($row['status_aktif'] == 'Aktif') ? 'border-primary' : '';
                $bg_class = ($row['status_aktif'] == 'Aktif') ? 'bg-primary text-white' : 'bg-light';
                // Jika ingin hanya menampilkan tahun (misal 2024), bisa diparsing
                // Tapi user mungkin ingin label lengkap. Sesuai gambar, hanya tahun (2024, 2023).
                // Kita asumsi tahun_ajaran formatnya "2023/2024". Ambil bagian depan saja?
                // Atau tampilkan apa adanya. Gambar menunjukkan "2024", "2023".
                // Mari ambil 4 digit pertama.
                $tahun_label = substr($row['tahun_ajaran'], 0, 4);
                // ATAU gunakan field tahun_ajaran full jika formatnya pendek.
                // Untuk amannya, tampilkan full dulu, nanti user bisa minta ubah.
                
                ?>
                <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                    <div class="card shadow-sm h-100 <?php echo $status_class; ?>">
                        <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-calendar-alt fa-3x text-secondary"></i>
                            </div>
                            <h4 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($row['tahun_ajaran']); ?></h4>
                            <small class="text-muted"><?php echo htmlspecialchars($row['semester']); ?></small>
                            <?php if ($row['status_aktif'] == 'Aktif'): ?>
                                <span class="badge bg-success mt-2">Aktif</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-top-0 pb-3 text-center">
                            <a href="siswa_kelas.php?id_tahun=<?php echo $row['id_tahun_ajaran']; ?>" class="btn btn-outline-dark w-100 rounded-pill">
                                <i class="fas fa-eye me-2"></i> Lihat Daftar Kelas
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
</div>

<?php
require_once '../../includes/footer.php';
?>
