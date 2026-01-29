<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';
$judul_halaman = "Rekap Absensi Bulanan";

// Ambil variabel dari session dan URL
$id_guru_login = isset($_SESSION['id_guru']) ? (int)$_SESSION['id_guru'] : null;
$role = $_SESSION['role'] ?? 'guest';

// Hanya untuk guru
if ($role !== 'guru' || !$id_guru_login) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Halaman ini hanya untuk Guru.</div></div>';
    require_once 'includes/footer.php';
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
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Rekap Absensi</li>
    </ol>

    <!-- FORM FILTER -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="m-0 text-primary"><i class="fas fa-filter me-2"></i>Filter Data</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="absensi_rekap.php">
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
                            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php if($i == $selected_tahun) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="kelas" class="form-label text-muted small fw-bold">Kelas</label>
                        <select name="kelas" id="kelas" class="form-select border-primary" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php 
                            // Tampilkan kelas yang diajar oleh guru ini
                            $query_kelas = "SELECT DISTINCT k.id_kelas, k.nama_kelas 
                                            FROM kelas k 
                                            JOIN mengajar m ON k.id_kelas = m.id_kelas 
                                            WHERE m.id_guru = {$id_guru_login} 
                                            ORDER BY k.nama_kelas ASC";
                            $result_kelas = mysqli_query($koneksi, $query_kelas);
                            while($row = mysqli_fetch_assoc($result_kelas)) {
                                $selected = ($row['id_kelas'] == $selected_kelas) ? 'selected' : '';
                                echo "<option value='{$row['id_kelas']}' $selected>" . htmlspecialchars($row['nama_kelas']) . "</option>";
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                         <button type="submit" class="btn btn-primary w-100 shadow-sm"><i class="fas fa-search me-1"></i> Tampilkan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    if (!empty($selected_bulan) && !empty($selected_tahun) && !empty($selected_kelas)) :
        // Dapatkan ID tahun ajaran yang sedang aktif
        $active_ta_result = mysqli_query($koneksi, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status_aktif = 'Aktif' LIMIT 1");
        
        if ($active_ta_result && mysqli_num_rows($active_ta_result) > 0) {
            $active_id_tahun_ajaran = mysqli_fetch_assoc($active_ta_result)['id_tahun_ajaran'];

            // 1. Ambil semua id_mengajar untuk guru & kelas ini (di tahun ajaran aktif)
            $q_mengajar = "SELECT id_mengajar FROM mengajar 
                           WHERE id_guru = {$id_guru_login} 
                             AND id_kelas = {$selected_kelas} 
                             AND id_tahun_ajaran = {$active_id_tahun_ajaran}";
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
                                    $status = $absensi_data[$siswa['id_siswa']][$i] ?? '-';
                                    $badge_class = 'secondary'; 
                                    $badge_text = '';

                                    if($status == 'Hadir') { $badge_class = 'success'; $badge_text = 'H'; }
                                    elseif($status == 'Sakit') { $badge_class = 'warning'; $badge_text = 'S'; }
                                    elseif($status == 'Izin') { $badge_class = 'info'; $badge_text = 'I'; }
                                    elseif($status == 'Alfa') { $badge_class = 'danger'; $badge_text = 'A'; }
                                    else { $badge_text = '-'; }

                                    $tanggal_link = $tahun_numerik . '-' . str_pad($selected_bulan, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                                    // Link ke absensi.php
                                    $link_absensi = "absensi.php?tanggal={$tanggal_link}&kelas={$selected_kelas}";
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
            
            <!-- Legend / Keterangan -->
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
    <?php } else {
            echo '<div class="alert alert-warning">Tidak ada jadwal mengajar yang cocok dengan kriteria yang dipilih pada tahun ajaran yang sedang aktif.</div>';
        }
    } else {
        echo '<div class="alert alert-danger">Tidak ada Tahun Ajaran yang berstatus Aktif. Silakan hubungi Admin.</div>';
    }
    endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>