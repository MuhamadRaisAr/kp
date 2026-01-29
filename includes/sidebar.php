<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 280px; min-height: 100vh;">
    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="fas fa-school fa-2x me-2"></i>
        <span class="fs-4">SI Penilaian</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" aria-current="page">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
        </li>

        <?php
        // Cek role sekali saja
        $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

        // TAMPILKAN MENU BERDASARKAN ROLE PENGGUNA
        if ($user_role == 'admin') :
        ?>
            <li><a href="guru.php" class="nav-link text-white <?php echo (strtolower(basename($_SERVER['PHP_SELF'])) == 'guru.php') ? 'active' : ''; ?>"><i class="fas fa-user-graduate me-2"></i>Guru</a></li>
            <li><a href="siswa.php" class="nav-link text-white <?php echo (strtolower(basename($_SERVER['PHP_SELF'])) == 'siswa.php') ? 'active' : ''; ?>"><i class="fas fa-chalkboard-teacher me-2"></i>Siswa</a></li>
            <li><a href="kelas.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'kelas.php') ? 'active' : ''; ?>"><i class="fas fa-door-open me-2"></i>Kelas</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">DATA MASTER</div>
            <li><a href="jurusan.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'jurusan.php') ? 'active' : ''; ?>"><i class="fas fa-book-reader me-2"></i>Jurusan</a></li>
            <li><a href="mapel.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'mapel.php') ? 'active' : ''; ?>"><i class="fas fa-book me-2"></i>Mata Pelajaran</a></li>
            <li><a href="tahun_ajaran.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'tahun_ajaran.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt me-2"></i>Tahun Ajaran</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">RELASI</div>
            <li><a href="mengajar.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'mengajar.php') ? 'active' : ''; ?>"><i class="fas fa-chalkboard me-2"></i>Penugasan Mengajar</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">LAPORAN</div>
            <li><a href="laporan_nilai.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'laporan_nilai.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt me-2"></i>Laporan Nilai</a></li>
            <li><a href="cetak_rapot.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'cetak_rapot.php') ? 'active' : ''; ?>"><i class="fas fa-print me-2"></i>Cetak Rapor</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">ADMINISTRASI</div>
            <li><a href="users.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users-cog me-2"></i>Manajemen User</a></li>
            <li><a href="pengumuman.php" class="nav-link text-white <?php echo (strpos(basename($_SERVER['PHP_SELF']), 'pengumuman') !== false) ? 'active' : ''; ?>"><i class="fas fa-bullhorn me-2"></i>Pengumuman</a></li>
            <?php
        elseif ($user_role == 'guru') :
        ?>
            <hr>
            <div class="small text-muted px-3 mb-1">KEGIATAN HARIAN</div>
            <li><a href="absensi.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'absensi.php') ? 'active' : ''; ?>"><i class="fas fa-user-check me-2"></i>Absensi Harian</a></li>
            <li><a href="nilai.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'nilai.php') ? 'active' : ''; ?>"><i class="fas fa-book-open me-2"></i>Kelola Nilai</a></li>
            <li><a href="ujian.php" class="nav-link text-white <?php echo (strpos(basename($_SERVER['PHP_SELF']), 'ujian') !== false && basename($_SERVER['PHP_SELF']) != 'ujian_saya.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt me-2"></i>Manajemen Ujian</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">JADWAL SAYA</div>
             <li><a href="mengajar.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'mengajar.php') ? 'active' : ''; ?>"><i class="fas fa-chalkboard me-2"></i>Kelas Saya</a></li>

            <hr>
            <div class="small text-muted px-3 mb-1">LAPORAN</div>
            <li><a href="absensi_rekap.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'absensi_rekap.php') ? 'active' : ''; ?>"><i class="fas fa-calendar-alt me-2"></i>Rekap Absensi</a></li>
            <li><a href="laporan_nilai.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'laporan_nilai.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt me-2"></i>Laporan Nilai</a></li>


        <?php
        elseif ($user_role == 'siswa') :
        ?>
            <hr>
            <div class="small text-muted px-3 mb-1">AKADEMIK SAYA</div>
            <li>
                <a href="nilai_saya.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'nilai_saya.php') ? 'active' : ''; ?>">
                    <i class="fas fa-book-open me-2"></i> Lihat Nilai
                </a>
            </li>

            <li>
                <a href="absensi_saya.php" class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) == 'absensi_saya.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check me-2"></i> Rekap Absensi
                </a>
            </li>
            <li>
                <a href="ujian_saya.php" class="nav-link text-white <?php echo (strpos(basename($_SERVER['PHP_SELF']), 'ujian') !== false && basename($_SERVER['PHP_SELF']) != 'ujian.php') ? 'active' : ''; ?>">
                    <i class="fas fa-user-check me-2"></i> Ujian
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <hr>
    <div class="text-center text-muted small">
        &copy; <?php echo date('Y'); ?> SMK Hebat.
    </div>
</div>