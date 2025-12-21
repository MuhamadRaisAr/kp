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
$selected_mapel = isset($_GET['mapel']) ? (int)$_GET['mapel'] : '';

$nama_bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Rekap Absensi Bulanan</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Rekap Absensi</li>
    </ol>

    <!-- FORM FILTER -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i>Pilih Periode dan Kelas</div>
        <div class="card-body">
            <form method="GET" action="absensi_rekap.php">
                <div class="row">
                    <div class="col-md-3">
                        <label for="bulan" class="form-label">Bulan</label>
                        <select name="bulan" id="bulan" class="form-select" required>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if($i == $selected_bulan) echo 'selected'; ?>><?php echo $nama_bulan[$i]; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="tahun" class="form-label">Tahun</label>
                        <select name="tahun" id="tahun" class="form-select" required>
                            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                                <option value="<?php echo $i; ?>" <?php if($i == $selected_tahun) echo 'selected'; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="kelas" class="form-label">Kelas</label>
                        <select name="kelas" id="kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php 
                            $query_kelas = "SELECT DISTINCT k.id_kelas, k.nama_kelas FROM kelas k JOIN mengajar m ON k.id_kelas = m.id_kelas WHERE m.id_guru = {$id_guru_login} ORDER BY k.nama_kelas ASC";
                            $result_kelas = mysqli_query($koneksi, $query_kelas);
                            while($row = mysqli_fetch_assoc($result_kelas)) {
                                $selected = ($row['id_kelas'] == $selected_kelas) ? 'selected' : '';
                                echo "<option value='{$row['id_kelas']}' $selected>" . htmlspecialchars($row['nama_kelas']) . "</option>";
                            } 
                            ?>
                        </select>
                    </div>
                     <div class="col-md-3">
                        <label for="mapel" class="form-label">Mata Pelajaran</label>
                        <select name="mapel" id="mapel" class="form-select" required>
                            <option value="">-- Pilih Kelas Dulu --</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Tampilkan Rekap</button>
            </form>
        </div>
    </div>

    <?php
    if (!empty($selected_bulan) && !empty($selected_tahun) && !empty($selected_kelas) && !empty($selected_mapel)) :
        // Dapatkan ID tahun ajaran yang sedang aktif
        $active_ta_result = mysqli_query($koneksi, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status_aktif = 'Aktif' LIMIT 1");
        
        if ($active_ta_result && mysqli_num_rows($active_ta_result) > 0) {
            $active_id_tahun_ajaran = mysqli_fetch_assoc($active_ta_result)['id_tahun_ajaran'];

            $q_mengajar = "SELECT id_mengajar FROM mengajar 
                           WHERE id_guru = {$id_guru_login} 
                             AND id_kelas = {$selected_kelas} 
                             AND id_mapel = {$selected_mapel} 
                             AND id_tahun_ajaran = {$active_id_tahun_ajaran}";
            $res_mengajar = mysqli_query($koneksi, $q_mengajar);
            
            if(mysqli_num_rows($res_mengajar) > 0) {
                $id_mengajar = mysqli_fetch_assoc($res_mengajar)['id_mengajar'];
                
                $q_siswa = "SELECT id_siswa, nama_lengkap FROM siswa WHERE id_kelas = {$selected_kelas} ORDER BY nama_lengkap ASC";
                $res_siswa = mysqli_query($koneksi, $q_siswa);

                // LOGIKA TAHUN DIPERBAIKI: Langsung gunakan $selected_tahun dari filter
                $tahun_numerik = $selected_tahun;

                $absensi_data = [];
                $q_absensi = "SELECT id_siswa, DAY(tanggal) as tgl, status FROM absensi WHERE id_mengajar = {$id_mengajar} AND MONTH(tanggal) = {$selected_bulan} AND YEAR(tanggal) = {$tahun_numerik}";
                $res_absensi = mysqli_query($koneksi, $q_absensi);
                while($row = mysqli_fetch_assoc($res_absensi)) {
                    $absensi_data[$row['id_siswa']][$row['tgl']] = $row['status'];
                }
                
                $jumlah_hari = cal_days_in_month(CAL_GREGORIAN, $selected_bulan, $tahun_numerik);
        ?>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-calendar-alt me-1"></i>Rekap Kehadiran: <?php echo $nama_bulan[$selected_bulan] . " " . $tahun_numerik; ?></div>
        <div class="card-body">
            <div class="alert alert-info py-2">
                <i class="fas fa-info-circle"></i> Klik pada status (H, S, I, A, -) untuk mengisi absensi harian.
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" style="font-size: 0.8rem;">
                    <thead class="text-center">
                        <tr>
                            <th class="align-middle" rowspan="2">Nama Siswa</th>
                            <th colspan="<?php echo $jumlah_hari; ?>">Tanggal</th>
                        </tr>
                        <tr>
                            <?php for ($i = 1; $i <= $jumlah_hari; $i++): ?>
                                <th><?php echo $i; ?></th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(mysqli_num_rows($res_siswa) > 0) {
                            while($siswa = mysqli_fetch_assoc($res_siswa)): ?>
                            <tr>
                                <td style="min-width: 150px;"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                                <?php for ($i = 1; $i <= $jumlah_hari; $i++): 
                                    $status = $absensi_data[$siswa['id_siswa']][$i] ?? '-';
                                    $badge_color = 'secondary';
                                    if($status == 'Hadir') $badge_color = 'success';
                                    if($status == 'Sakit') $badge_color = 'warning';
                                    if($status == 'Izin') $badge_color = 'info';
                                    if($status == 'Alfa') $badge_color = 'danger';

                                    $tanggal_link = $tahun_numerik . '-' . str_pad($selected_bulan, 2, '0', STR_PAD_LEFT) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                                    $link_absensi = "absensi.php?tanggal={$tanggal_link}&kelas={$selected_kelas}&mapel={$selected_mapel}";
                                ?>
                                    <td class="text-center">
                                        <a href="<?php echo $link_absensi; ?>" title="Input Absensi Tanggal <?php echo $i; ?>" class="text-decoration-none">
                                            <span class="badge bg-<?php echo $badge_color; ?>"><?php echo substr($status, 0, 1); ?></span>
                                        </a>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                            <?php endwhile; 
                        } else {
                            echo "<tr><td colspan='" . ($jumlah_hari + 1) . "' class='text-center'>Belum ada siswa di kelas ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
             <div class="mt-3">
                <strong>Keterangan:</strong>
                <span class="badge bg-success">H: Hadir</span>
                <span class="badge bg-warning text-dark">S: Sakit</span>
                <span class="badge bg-info text-dark">I: Izin</span>
                <span class="badge bg-danger">A: Alfa</span>
                <span class="badge bg-secondary">-: Belum Diabsen</span>
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

<!-- JavaScript untuk dropdown dinamis -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const kelasDropdown = document.getElementById('kelas');
    const mapelDropdown = document.getElementById('mapel');
    const selectedMapelFromPHP = "<?php echo $selected_mapel; ?>";

    function fetchMapel() {
        const idKelas = kelasDropdown.value;
        mapelDropdown.innerHTML = '<option value="">Memuat...</option>';

        if (!idKelas) {
            mapelDropdown.innerHTML = '<option value="">-- Pilih Kelas Dulu --</option>';
            return;
        }
        
        fetch('api_get_mapel.php?id_kelas=' + idKelas)
            .then(response => response.json())
            .then(data => {
                mapelDropdown.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';
                data.forEach(mapel => {
                    const option = document.createElement('option');
                    option.value = mapel.id_mapel;
                    option.textContent = mapel.nama_mapel;
                    if(mapel.id_mapel == selectedMapelFromPHP) {
                        option.selected = true;
                    }
                    mapelDropdown.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error fetching mapel:', error);
                mapelDropdown.innerHTML = '<option value="">Gagal memuat data</option>';
            });
    }

    kelasDropdown.addEventListener('change', fetchMapel);

    if (kelasDropdown.value) {
        fetchMapel();
    }
});
</script>