<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar">
    <a href="/sistem-penilaian/dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="fas fa-school fa-2x me-2"></i>
        <span class="fs-4">SI Penilaian</span>
    </a>
    <hr>

    <?php
    $current = basename($_SERVER['PHP_SELF']);
    $role    = $_SESSION['role'] ?? '';
    $is_wali = $_SESSION['is_wali'] ?? false;
    ?>

    <ul class="nav nav-pills flex-column mb-auto">

        <!-- DASHBOARD -->
        <li class="nav-item">
            <a href="/sistem-penilaian/dashboard.php" class="nav-link text-white <?= ($current == 'dashboard.php') ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>

        <!-- ================= ADMIN ================= -->
        <!-- ================= ADMIN ================= -->
        <?php
        // ================= ADMIN =================
        if ($role == 'admin') : ?>
            <li><a href="/sistem-penilaian/modules/guru/guru.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/guru/') !== false) ? 'active' : '' ?>"><i class="fas fa-user-graduate me-2"></i>Guru</a></li>
            <li><a href="/sistem-penilaian/modules/siswa/siswa.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/siswa/') !== false && strpos($_SERVER['PHP_SELF'], 'nilai') === false && strpos($_SERVER['PHP_SELF'], 'absensi') === false) ? 'active' : '' ?>"><i class="fas fa-chalkboard-teacher me-2"></i>Siswa</a></li>
            <li><a href="/sistem-penilaian/modules/kelas/kelas.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/kelas/') !== false) ? 'active' : '' ?>"><i class="fas fa-door-open me-2"></i>Kelas</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">DATA MASTER</div>
            <li><a href="/sistem-penilaian/modules/jurusan/jurusan.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/jurusan/') !== false) ? 'active' : '' ?>"><i class="fas fa-book-reader me-2"></i>Jurusan</a></li>
            <li><a href="/sistem-penilaian/modules/mapel/mapel.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/mapel/') !== false) ? 'active' : '' ?>"><i class="fas fa-book me-2"></i>Mata Pelajaran</a></li>
            <li><a href="/sistem-penilaian/modules/tahun_ajaran/tahun_ajaran.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/tahun_ajaran/') !== false) ? 'active' : '' ?>"><i class="fas fa-calendar-alt me-2"></i>Tahun Ajaran</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">RELASI</div>
            <li><a href="/sistem-penilaian/modules/mengajar/mengajar.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/mengajar/') !== false) ? 'active' : '' ?>"><i class="fas fa-chalkboard me-2"></i>Penugasan Mengajar</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">LAPORAN</div>
            <li><a href="/sistem-penilaian/modules/nilai/laporan_nilai.php" class="nav-link text-white <?= (strpos($current, 'laporan_nilai.php') !== false) ? 'active' : '' ?>"><i class="fas fa-file-alt me-2"></i>Laporan Nilai</a></li>
            <li><a href="/sistem-penilaian/modules/nilai/cetak_rapot.php" class="nav-link text-white <?= (strpos($current, 'cetak_rapot.php') !== false) ? 'active' : '' ?>"><i class="fas fa-print me-2"></i>Cetak Rapor</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">ADMINISTRASI</div>
            <li><a href="/sistem-penilaian/modules/user/users.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? 'active' : '' ?>"><i class="fas fa-users-cog me-2"></i>Manajemen User</a></li>
            <li><a href="/sistem-penilaian/modules/pengumuman/pengumuman.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/pengumuman/') !== false) ? 'active' : '' ?>"><i class="fas fa-bullhorn me-2"></i>Pengumuman</a></li>

        <!-- ================= GURU ================= -->
        <?php elseif ($role == 'guru') : ?>

            <hr>
            <div class="small text-muted px-3 mb-1">KEGIATAN HARIAN</div>
            <li><a href="/sistem-penilaian/modules/absensi/absensi.php" class="nav-link text-white <?= (strpos($current, 'absensi.php') !== false) ? 'active' : '' ?>"><i class="fas fa-user-check me-2"></i>Absensi Harian</a></li>
            <li><a href="/sistem-penilaian/modules/nilai/nilai.php" class="nav-link text-white <?= (strpos($current, 'nilai.php') !== false) ? 'active' : '' ?>"><i class="fas fa-book-open me-2"></i>Kelola Nilai</a></li>
            <li><a href="/sistem-penilaian/modules/ujian/ujian.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/ujian/') !== false && $current != 'ujian_saya.php') ? 'active' : '' ?>"><i class="fas fa-file-alt me-2"></i>Manajemen Ujian</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">JADWAL SAYA</div>
            <li><a href="/sistem-penilaian/modules/mengajar/mengajar.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/mengajar/') !== false) ? 'active' : '' ?>"><i class="fas fa-chalkboard me-2"></i>Kelas Saya</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">LAPORAN</div>
            <li><a href="/sistem-penilaian/modules/absensi/absensi_rekap.php" class="nav-link text-white <?= (strpos($current, 'absensi_rekap.php') !== false) ? 'active' : '' ?>"><i class="fas fa-calendar-alt me-2"></i>Rekap Absensi</a></li>
            <li><a href="/sistem-penilaian/modules/nilai/laporan_nilai.php" class="nav-link text-white <?= (strpos($current, 'laporan_nilai.php') !== false) ? 'active' : '' ?>"><i class="fas fa-file-alt me-2"></i>Laporan Nilai</a></li>

            <?php if ($is_wali) : ?>
            <li><a href="/sistem-penilaian/modules/nilai/cetak_rapot.php" class="nav-link text-white <?= (strpos($current, 'cetak_rapot.php') !== false) ? 'active' : '' ?>"><i class="fas fa-print me-2"></i>Cetak Rapor</a></li>
            <?php endif; ?>

        <!-- ================= SISWA ================= -->
        <?php elseif ($role == 'siswa') : ?>

            <hr>
            <div class="small text-muted px-3 mb-1">AKADEMIK SAYA</div>
            <li><a href="/sistem-penilaian/modules/absensi/absensi_saya.php" class="nav-link text-white <?= (strpos($current, 'absensi_saya.php') !== false) ? 'active' : '' ?>"><i class="fas fa-calendar-check me-2"></i>Rekap Absensi</a></li>
            <li><a href="/sistem-penilaian/modules/nilai/nilai_saya.php" class="nav-link text-white <?= (strpos($current, 'nilai_saya.php') !== false) ? 'active' : '' ?>"><i class="fas fa-book-open me-2"></i>Lihat Nilai</a></li>
            <li><a href="/sistem-penilaian/modules/ujian/ujian_saya.php" class="nav-link text-white <?= (strpos($_SERVER['PHP_SELF'], '/ujian/') !== false && $current != 'ujian.php') ? 'active' : '' ?>"><i class="fas fa-user-check me-2"></i>Ujian</a></li>

        <?php endif; ?>

    </ul>

    <hr>
    <div class="text-center text-muted small">
        &copy; <?= date('Y') ?> SMK Hebat.
    </div>
</div>
