<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';
$judul_halaman = "Rekap Absensi Bulanan";

// Ambil variabel dari session dan URL
$id_guru_login = isset($_SESSION['id_guru']) ? (int)$_SESSION['id_guru'] : null;
$role = $_SESSION['role'] ?? 'guest';

// Hanya untuk guru
if ($role !== 'guru' || !$id_guru_login) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Halaman ini hanya untuk Guru.</div></div>';
    require_once '../../includes/footer.php';
    exit();
}

// Filter, default ke bulan dan tahun sekarang
$selected_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');
$selected_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$selected_kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : '';

$nama_bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $judul_halaman; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <?php if (isset($_GET['id_tahun_ajaran'])): ?>
            <li class="breadcrumb-item"><a href="absensi_rekap.php">Rekap Absensi</a></li>
            <li class="breadcrumb-item active">Detail</li>
        <?php else: ?>
            <li class="breadcrumb-item active">Rekap Absensi</li>
        <?php endif; ?>
    </ol>

    <?php
    // LOGIKA FILTER TAHUN AJARAN (Card View vs Report View)
    $selected_id_tahun_ajaran = isset($_GET['id_tahun_ajaran']) ? (int)$_GET['id_tahun_ajaran'] : 0;

    if ($selected_id_tahun_ajaran == 0) {
        // --- TAMPILAN PILIH TAHUN AJARAN (CARD VIEW) ---
    ?>
        <div class="row">
        <?php
        $query_ta = "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC, semester DESC";
        $result_ta = mysqli_query($koneksi, $query_ta);

        if (mysqli_num_rows($result_ta) > 0) {
            while ($ta = mysqli_fetch_assoc($result_ta)) {
                $status_badge = ($ta['status_aktif'] == 'Aktif') ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Tidak Aktif</span>';
                $card_border = ($ta['status_aktif'] == 'Aktif') ? 'border-primary' : '';
        ?>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card <?php echo $card_border; ?> shadow-sm h-100 text-center py-4">
                    <div class="card-body">
                        <div class="fs-1 text-muted mb-3"><i class="fas fa-calendar-alt"></i></div>
                        <h4 class="fw-bold"><?php echo htmlspecialchars($ta['tahun_ajaran']); ?></h4>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($ta['semester']); ?></p>
                        <div class="mb-4"><?php echo $status_badge; ?></div>
                        
                        <a href="absensi_rekap.php?id_tahun_ajaran=<?php echo $ta['id_tahun_ajaran']; ?>" class="btn btn-outline-primary rounded-pill px-4 stretched-link">
                            <i class="fas fa-eye me-2"></i> Lihat Rekap Absensi
                        </a>
                    </div>
                </div>
            </div>
        <?php
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-info">Belum ada data Tahun Ajaran.</div></div>';
        }
        ?>
        </div>

    <?php
    } else {
        // --- TAMPILAN LAPORAN (EXISTING LOGIC WITH MODIFICATIONS) ---
        
        // Ambil info tahun ajaran terpilih
        $q_info_ta = "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = $selected_id_tahun_ajaran";
        $res_info_ta = mysqli_query($koneksi, $q_info_ta);
        $info_ta = mysqli_fetch_assoc($res_info_ta);
        
        if (!$info_ta) {
            echo '<div class="alert alert-danger">Tahun Ajaran tidak ditemukan. <a href="absensi_rekap.php">Kembali</a></div>';
        } else {
            // Tentukan default tahun kalender berdasarkan tahun ajaran terpilih
            $tahun_pecah = explode('/', $info_ta['tahun_ajaran']); // [2025, 2026]
            $start_year = isset($tahun_pecah[0]) ? (int)$tahun_pecah[0] : date('Y');
            $end_year = isset($tahun_pecah[1]) ? (int)$tahun_pecah[1] : date('Y');
            
            $current_y = date('Y');
            if ($current_y >= $start_year && $current_y <= $end_year) {
                $default_tahun = $current_y;
            } else {
                $default_tahun = ($info_ta['semester'] == 'Ganjil') ? $start_year : $end_year;
            }

            // Override filter default jika tidak ada di GET
            if (!isset($_GET['tahun'])) $selected_tahun = $default_tahun;
    ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-info-circle me-2"></i>
                Menampilkan data untuk Tahun Ajaran: <strong><?php echo $info_ta['tahun_ajaran'] . ' (' . $info_ta['semester'] . ')'; ?></strong>
            </div>
            <a href="absensi_rekap.php" class="btn btn-sm btn-outline-dark">Ganti Tahun Ajaran</a>
        </div>

        <?php
        // LOGIKA BARU: Ambil daftar kelas dulu untuk cek jumlahnya
        $query_kelas_guru = "SELECT DISTINCT k.id_kelas, k.nama_kelas 
                            FROM kelas k 
                            JOIN mengajar m ON k.id_kelas = m.id_kelas 
                            WHERE m.id_guru = {$id_guru_login} 
                              AND m.id_tahun_ajaran = {$selected_id_tahun_ajaran}
                            ORDER BY k.nama_kelas ASC";
        $result_kelas_guru = mysqli_query($koneksi, $query_kelas_guru);
        $daftar_kelas = [];
        while($row_k = mysqli_fetch_assoc($result_kelas_guru)) {
            $daftar_kelas[] = $row_k;
        }

        // Auto-select jika cuma 1 kelas (dan belum dipilih user)
        $is_single_class = (count($daftar_kelas) == 1);
        if ($is_single_class && empty($selected_kelas)) {
             $selected_kelas = $daftar_kelas[0]['id_kelas'];
        }
        ?>

        <!-- FORM FILTER -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white border-bottom-0 py-3">
                <h5 class="m-0 text-primary"><i class="fas fa-filter me-2"></i>Filter Data</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="absensi_rekap.php">
                    <!-- Penting: Kirim kembali id_tahun_ajaran -->
                    <input type="hidden" name="id_tahun_ajaran" value="<?php echo $selected_id_tahun_ajaran; ?>">
                    <input type="hidden" name="is_filtered" value="yes">
                    
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="bulan" class="form-label text-muted small fw-bold">Bulan</label>
                            <select name="bulan" id="bulan" class="form-select border-primary" required>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php if($i == $selected_bulan) echo 'selected'; ?>><?php echo $nama_bulan[$i]; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="tahun" class="form-label text-muted small fw-bold">Tahun</label>
                            <select name="tahun" id="tahun" class="form-select border-primary" required>
                                <?php 
                                // Tampilkan tahun sesuai range tahun ajaran saja agar tidak bingung
                                for ($i = $end_year; $i >= $start_year; $i--): 
                                ?>
                                    <option value="<?php echo $i; ?>" <?php if($i == $selected_tahun) echo 'selected'; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="kelas" class="form-label text-muted small fw-bold">Kelas</label>
                            <?php if ($is_single_class): ?>
                                <!-- JIKA CUMA 1 KELAS: Tampilkan Readonly -->
                                <input type="hidden" name="kelas" value="<?php echo $daftar_kelas[0]['id_kelas']; ?>">
                                <input type="text" class="form-control border-primary bg-light" value="<?php echo htmlspecialchars($daftar_kelas[0]['nama_kelas']); ?>" readonly>
                            <?php else: ?>
                                <!-- JIKA BANYAK KELAS: Tampilkan Dropdown -->
                                <select name="kelas" id="kelas" class="form-select border-primary" required>
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php foreach($daftar_kelas as $kls): 
                                        $selected = ($kls['id_kelas'] == $selected_kelas) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $kls['id_kelas']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($kls['nama_kelas']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2">
                             <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="fas fa-search me-1"></i> Tampilkan</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        if (isset($_GET['is_filtered']) && !empty($selected_bulan) && !empty($selected_tahun) && !empty($selected_kelas)) {
            
            // 1. Ambil semua id_mengajar untuk guru & kelas ini (di id_tahun_ajaran terpilih)
            $q_mengajar = "SELECT id_mengajar FROM mengajar 
                           WHERE id_guru = {$id_guru_login} 
                             AND id_kelas = {$selected_kelas} 
                             AND id_tahun_ajaran = {$selected_id_tahun_ajaran}";
            $res_mengajar = mysqli_query($koneksi, $q_mengajar);
            
            $ids_mengajar = [];
            while($rm = mysqli_fetch_assoc($res_mengajar)) {
                $ids_mengajar[] = $rm['id_mengajar'];
            }

            if(count($ids_mengajar) > 0) {
                // Konversi array ke string untuk query IN (...)
                $ids_mengajar_str = implode(',', $ids_mengajar);
                
                $q_siswa = "SELECT id_siswa, nama_lengkap FROM siswa WHERE id_kelas = {$selected_kelas} ORDER BY nama_lengkap ASC";
                $res_siswa = mysqli_query($koneksi, $q_siswa);

                // LOGIKA TAHUN: Gunakan $selected_tahun dari filter
                $tahun_numerik = $selected_tahun;

                $absensi_data = [];
                // Ambil data absensi berdasarkan list id_mengajar
                $q_absensi = "SELECT id_siswa, DAY(tanggal) as tgl, status 
                              FROM absensi 
                              WHERE id_mengajar IN ({$ids_mengajar_str}) 
                                AND MONTH(tanggal) = {$selected_bulan} 
                                AND YEAR(tanggal) = {$tahun_numerik}";
                $res_absensi = mysqli_query($koneksi, $q_absensi);
                
                while($row = mysqli_fetch_assoc($res_absensi)) {
                    // Jika ada duplikasi data, data terakhir akan menimpa
                    $absensi_data[$row['id_siswa']][$row['tgl']] = $row['status'];
                }
                
                $jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $selected_bulan, $tahun_numerik);
        ?>
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-calendar-alt me-2"></i>
                        Rekap Periode: <strong><?php echo $nama_bulan[$selected_bulan] . " " . $tahun_numerik; ?></strong>
                    </div>
                    <div>
                        <small class="text-white-50"><i class="fas fa-info-circle me-1"></i> Klik status untuk edit</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0" style="font-size: 0.9rem;">
                            <thead class="bg-light text-center align-middle sticky-top" style="z-index: 10;">
                                <tr>
                                    <th class="py-3 bg-light" scope="col" style="position: sticky; left: 0; z-index: 20; min-width: 250px;">Nama Siswa</th>
                                    <?php for ($i = 1; $i <= $jumlah_hari; $i++): ?>
                                        <th scope="col" style="min-width: 35px;"><?php echo $i; ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if(mysqli_num_rows($res_siswa) > 0) {
                                    while($siswa = mysqli_fetch_assoc($res_siswa)): ?>
                                    <tr>
                                        <td class="fw-bold text-dark px-3 bg-white" style="position: sticky; left: 0; z-index: 10; border-right: 2px solid #dee2e6;">
                                            <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>
                                        </td>
                                        <?php for ($i = 1; $i <= $jumlah_hari; $i++): 
                                            // ... Logic status code ...
                                            // FIX: Gunakan null coalescing operator bertingkat untuk menghindari warning "Undefined array key"
                                            $status = ($absensi_data[$siswa['id_siswa']] ?? [])[$i] ?? '-';
                                            $badge_class = 'secondary'; 
                                            $badge_text = '';
        
                                            if($status == 'Hadir') { $badge_class = 'success'; $badge_text = 'H'; }
                                            elseif($status == 'Sakit') { $badge_class = 'warning'; $badge_text = 'S'; }
                                            elseif($status == 'Izin') { $badge_class = 'info'; $badge_text = 'I'; }
                                            elseif($status == 'Alfa') { $badge_class = 'danger'; $badge_text = 'A'; }
                                            else { $badge_text = '-'; }
        
                                            $tanggal_link = $tahun_numerik . '-' . str_pad($selected_bulan, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                                            // Link ke absensi.php
                                            $link_absensi = "absensi.php?tanggal={$tanggal_link}&kelas={$selected_kelas}&tahun_ajaran={$selected_id_tahun_ajaran}";
                                        ?>
                                            <td class="text-center p-0 align-middle">
                                                <a href="<?php echo $link_absensi; ?>" class="d-block w-100 h-100 py-2 text-decoration-none" title="Edit Tgl <?php echo $i; ?>">
                                                    <?php if($status != '-'): ?>
                                                        <span class="badge rounded-circle bg-<?php echo $badge_class; ?>" style="width: 25px; height: 25px; line-height: 20px; padding: 0;">
                                                            <?php echo $badge_text; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </a>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php endwhile; 
                                } else {
                                    echo "<tr><td colspan='" . ($jumlah_hari + 1) . "' class='text-center py-4'>Belum ada data siswa di kelas ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card-footer bg-white border-top">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-center">
                            <small class="fw-bold text-muted me-2">KETERANGAN:</small>
                            <span class="badge rounded-pill bg-success"><i class="fas fa-check me-1"></i>Hadir (H)</span>
                            <span class="badge rounded-pill bg-warning text-dark"><i class="fas fa-notes-medical me-1"></i>Sakit (S)</span>
                            <span class="badge rounded-pill bg-info text-dark"><i class="fas fa-envelope me-1"></i>Izin (I)</span>
                            <span class="badge rounded-pill bg-danger"><i class="fas fa-times me-1"></i>Alfa (A)</span>
                            <span class="badge rounded-pill bg-secondary"><i class="fas fa-minus me-1"></i>Belum Absen</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            } else {
                echo '<div class="alert alert-warning">Tidak ada jadwal mengajar yang cocok dengan kriteria yang dipilih pada tahun ajaran ini.</div>';
            }
        }
    } 
} // End else ($selected_id_tahun_ajaran != 0)
?>
</div>

<?php require_once '../../includes/footer.php'; ?>
