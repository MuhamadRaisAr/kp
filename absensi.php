<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';
$judul_halaman = "Absensi Siswa Harian";

// Ambil variabel dari session dan URL
$id_guru_login = isset($_SESSION['id_guru']) ? (int)$_SESSION['id_guru'] : null;
$role_login = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'guest'; // Ambil dan ubah ke huruf kecil

// Hanya untuk guru (Pengecekan role dibuat case-insensitive)
if ($role_login !== 'guru' || !$id_guru_login) {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Halaman ini hanya untuk Guru.</div></div>';
    require_once 'includes/footer.php';
    exit();
}

// Ambil filter dari URL, atau default ke hari ini
$selected_tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$selected_tahun = isset($_GET['tahun_ajaran']) ? (int)$_GET['tahun_ajaran'] : '';
$selected_kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : '';
$selected_mapel = isset($_GET['mapel']) ? (int)$_GET['mapel'] : '';

// Jika tahun ajaran belum dipilih, coba ambil yang aktif sebagai default
if (empty($selected_tahun)) {
    $q_tahun_aktif = mysqli_query($koneksi, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status_aktif = 'Aktif' LIMIT 1");
    if ($q_tahun_aktif && mysqli_num_rows($q_tahun_aktif) > 0) {
        $selected_tahun = mysqli_fetch_assoc($q_tahun_aktif)['id_tahun_ajaran'];
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Absensi Siswa Harian</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Absensi Harian</li>
    </ol>
    
    <?php
    if (isset($_GET['status']) && $_GET['status'] == 'sukses_simpan') {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                Absensi telah berhasil disimpan.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
    ?>
    
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i>Pilih Jadwal</div>
        <div class="card-body">
            <form method="GET" action="absensi.php">
                <div class="row">
                    <div class="col-md-3">
                        <label for="tanggal" class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control" value="<?php echo $selected_tanggal; ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                        <select name="tahun_ajaran" id="tahun_ajaran" class="form-select" required onchange="this.form.submit()">
                             <option value="">-- Pilih Tahun Ajaran --</option>
                            <?php 
                            // Ambil SEMUA tahun ajaran, agar guru bisa input absensi ke semester lama
                            $query_tahun = "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC, semester DESC";
                            $result_tahun = mysqli_query($koneksi, $query_tahun);
                            while($row = mysqli_fetch_assoc($result_tahun)) {
                                $selected = ($row['id_tahun_ajaran'] == $selected_tahun) ? 'selected' : '';
                                echo "<option value='{$row['id_tahun_ajaran']}' $selected>" . htmlspecialchars($row['tahun_ajaran']) . " - " . htmlspecialchars($row['semester']) . "</option>";
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="kelas" class="form-label">Kelas</label>
                        <select name="kelas" id="kelas" class="form-select" required onchange="this.form.submit()">
                             <option value="">-- Pilih Tahun Dulu --</option>
                            <?php 
                            // PERBAIKAN: Query kelas sekarang bergantung pada $selected_tahun
                            if ($selected_tahun) {
                                $query_kelas = "SELECT DISTINCT k.id_kelas, k.nama_kelas 
                                                FROM kelas k 
                                                JOIN mengajar m ON k.id_kelas = m.id_kelas 
                                                WHERE m.id_guru = {$id_guru_login} AND m.id_tahun_ajaran = {$selected_tahun}
                                                ORDER BY k.nama_kelas ASC";
                                $result_kelas = mysqli_query($koneksi, $query_kelas);
                                while($row = mysqli_fetch_assoc($result_kelas)) {
                                    $selected = ($row['id_kelas'] == $selected_kelas) ? 'selected' : '';
                                    echo "<option value='{$row['id_kelas']}' $selected>" . htmlspecialchars($row['nama_kelas']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="mapel" class="form-label">Mata Pelajaran</label>
                        <select name="mapel" id="mapel" class="form-select" required>
                           <option value="">-- Pilih Kelas Dulu --</option>
                           <?php 
                            // PERBAIKAN: Query mapel sekarang bergantung pada $selected_tahun DAN $selected_kelas
                            if($selected_kelas && $selected_tahun) {
                                $query_mapel = "SELECT DISTINCT mp.id_mapel, mp.nama_mapel 
                                                FROM mata_pelajaran mp 
                                                JOIN mengajar m ON mp.id_mapel = m.id_mapel 
                                                WHERE m.id_guru = {$id_guru_login} 
                                                  AND m.id_kelas = {$selected_kelas}
                                                  AND m.id_tahun_ajaran = {$selected_tahun}";
                                $result_mapel = mysqli_query($koneksi, $query_mapel);
                                while($row = mysqli_fetch_assoc($result_mapel)) {
                                    $selected = ($row['id_mapel'] == $selected_mapel) ? 'selected' : '';
                                    echo "<option value='{$row['id_mapel']}' $selected>" . htmlspecialchars($row['nama_mapel']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Tampilkan Siswa</button>
            </form>
        </div>
    </div>

    <?php
    // Tampilkan tabel absensi jika filter sudah lengkap
    if (!empty($selected_tanggal) && !empty($selected_kelas) && !empty($selected_mapel) && !empty($selected_tahun)) :
        
        $q_mengajar = "SELECT id_mengajar FROM mengajar 
                       WHERE id_guru = {$id_guru_login} 
                         AND id_kelas = {$selected_kelas} 
                         AND id_mapel = {$selected_mapel} 
                         AND id_tahun_ajaran = {$selected_tahun}";
        $res_mengajar = mysqli_query($koneksi, $q_mengajar);
        
        if(mysqli_num_rows($res_mengajar) > 0) {
            $id_mengajar = mysqli_fetch_assoc($res_mengajar)['id_mengajar'];
            
            // Hapus "AND status_siswa = 'Aktif'" jika kolomnya tidak ada
            $q_siswa = "SELECT id_siswa, nama_lengkap FROM siswa WHERE id_kelas = {$selected_kelas} ORDER BY nama_lengkap ASC";
            $res_siswa = mysqli_query($koneksi, $q_siswa);
            
            $absensi_existing = [];
            // Gunakan prepared statement untuk keamanan
            $q_absensi = "SELECT id_siswa, status FROM absensi WHERE id_mengajar = ? AND tanggal = ?";
            $stmt_absensi = mysqli_prepare($koneksi, $q_absensi);
            mysqli_stmt_bind_param($stmt_absensi, "is", $id_mengajar, $selected_tanggal);
            mysqli_stmt_execute($stmt_absensi);
            $result_absensi = mysqli_stmt_get_result($stmt_absensi);
            while($row = mysqli_fetch_assoc($result_absensi)) {
                $absensi_existing[$row['id_siswa']] = $row['status'];
            }
            mysqli_stmt_close($stmt_absensi);
        ?>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-user-check me-1"></i>Input Kehadiran Siswa - <?php echo date('d F Y', strtotime($selected_tanggal)); ?></div>
        <div class="card-body">
            <form action="proses_absensi.php" method="POST">
                <input type="hidden" name="id_mengajar" value="<?php echo $id_mengajar; ?>">
                <input type="hidden" name="tanggal" value="<?php echo $selected_tanggal; ?>">
                
                <input type="hidden" name="filter_kelas" value="<?php echo $selected_kelas; ?>">
                <input type="hidden" name="filter_mapel" value="<?php echo $selected_mapel; ?>">
                <input type="hidden" name="filter_tahun_ajaran" value="<?php echo $selected_tahun; ?>">

                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th class="text-center" width="40%">Status Kehadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(mysqli_num_rows($res_siswa) > 0) {
                            while($siswa = mysqli_fetch_assoc($res_siswa)): 
                                $status_sekarang = $absensi_existing[$siswa['id_siswa']] ?? 'Hadir'; // Default ke Hadir
                            ?>
                            <tr>
                                <td class="align-middle"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                                <td>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="status[<?php echo $siswa['id_siswa']; ?>]" id="hadir_<?php echo $siswa['id_siswa']; ?>" value="Hadir" <?php if($status_sekarang == 'Hadir') echo 'checked'; ?>>
                                        <label class="btn btn-outline-success" for="hadir_<?php echo $siswa['id_siswa']; ?>">Hadir</label>

                                        <input type="radio" class="btn-check" name="status[<?php echo $siswa['id_siswa']; ?>]" id="sakit_<?php echo $siswa['id_siswa']; ?>" value="Sakit" <?php if($status_sekarang == 'Sakit') echo 'checked'; ?>>
                                        <label class="btn btn-outline-warning" for="sakit_<?php echo $siswa['id_siswa']; ?>">Sakit</label>

                                        <input type="radio" class="btn-check" name="status[<?php echo $siswa['id_siswa']; ?>]" id="izin_<?php echo $siswa['id_siswa']; ?>" value="Izin" <?php if($status_sekarang == 'Izin') echo 'checked'; ?>>
                                        <label class="btn btn-outline-info" for="izin_<?php echo $siswa['id_siswa']; ?>">Izin</label>

                                        <input type="radio" class="btn-check" name="status[<?php echo $siswa['id_siswa']; ?>]" id="alfa_<?php echo $siswa['id_siswa']; ?>" value="Alfa" <?php if($status_sekarang == 'Alfa') echo 'checked'; ?>>
                                        <label class="btn btn-outline-danger" for="alfa_<?php echo $siswa['id_siswa']; ?>">Alfa</label>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                            endwhile;
                        } else {
                            echo "<tr><td colspan='2' class='text-center'>Belum ada siswa di kelas ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <?php if(mysqli_num_rows($res_siswa) > 0): ?>
                    <button type="submit" class="btn btn-primary float-end">Simpan Absensi</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <?php } else {
            echo '<div class="alert alert-warning">Tidak ada jadwal mengajar yang cocok dengan kriteria yang dipilih. Pastikan Anda sudah ditugaskan mengajar mapel ini di kelas dan tahun ajaran tersebut.</div>';
        }
    endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>