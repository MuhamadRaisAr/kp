<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';
$judul_halaman = "Laporan Nilai";

// Fungsi bantu (tidak perlu diubah)
function hitungNilaiAkhir($tugas, $uts, $uas, $praktik) {
    $nilai_yang_ada = [];
    if ($tugas > 0) $nilai_yang_ada[] = $tugas;
    if ($uts > 0) $nilai_yang_ada[] = $uts;
    if ($uas > 0) $nilai_yang_ada[] = $uas;
    if ($praktik > 0) $nilai_yang_ada[] = $praktik;
    if (count($nilai_yang_ada) == 0) return 0;
    return array_sum($nilai_yang_ada) / count($nilai_yang_ada);
}
function tentukanPredikat($nilai_akhir) {
    if ($nilai_akhir >= 85) return 'A';
    if ($nilai_akhir >= 75) return 'B';
    if ($nilai_akhir >= 60) return 'C';
    if ($nilai_akhir >= 40) return 'D';
    return 'E';
}

// Ambil variabel dari session dan URL
$id_guru_login = isset($_SESSION['id_guru']) ? (int)$_SESSION['id_guru'] : null;
$role = $_SESSION['role'] ?? 'guest';
$selected_tahun = isset($_GET['tahun_ajaran']) ? (int)$_GET['tahun_ajaran'] : '';
$selected_kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : '';
$selected_mapel = isset($_GET['mapel']) ? (int)$_GET['mapel'] : '';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Laporan Nilai Siswa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Laporan Nilai</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i>Pilih Kriteria Laporan</div>
        <div class="card-body">
            <form method="GET" action="laporan_nilai.php" id="filterForm">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                        <select name="tahun_ajaran" id="tahun_ajaran" class="form-select" required>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <?php 
                            $result_tahun = mysqli_query($koneksi, "SELECT id_tahun_ajaran, tahun_ajaran, semester FROM tahun_ajaran ORDER BY tahun_ajaran DESC");
                            while($row = mysqli_fetch_assoc($result_tahun)) {
                                $selected = ($row['id_tahun_ajaran'] == $selected_tahun) ? 'selected' : '';
                                echo "<option value='{$row['id_tahun_ajaran']}' $selected>" . htmlspecialchars($row['tahun_ajaran'] . " - " . $row['semester']) . "</option>";
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="kelas" class="form-label">Kelas</label>
                        <select name="kelas" id="kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php 
                            if ($role == 'admin') {
                                $query_kelas_sql = "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC";
                            } else if ($id_guru_login) {
                                $query_kelas_sql = "SELECT DISTINCT k.id_kelas, k.nama_kelas FROM kelas k JOIN mengajar m ON k.id_kelas = m.id_kelas WHERE m.id_guru = $id_guru_login ORDER BY k.nama_kelas ASC";
                            }
                            
                            if (isset($query_kelas_sql)) {
                                $result_kelas = mysqli_query($koneksi, $query_kelas_sql);
                                while($row = mysqli_fetch_assoc($result_kelas)) {
                                    $selected = ($row['id_kelas'] == $selected_kelas) ? 'selected' : '';
                                    echo "<option value='{$row['id_kelas']}' $selected>" . htmlspecialchars($row['nama_kelas']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="mapel" class="form-label">Mata Pelajaran</label>
                        <select name="mapel" id="mapel" class="form-select" required>
                            <option value="">-- Pilih Kelas Terlebih Dahulu --</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Tampilkan Laporan</button>
            </form> 
        </div>
    </div>
    
    <?php
    // =================================================================
    // BAGIAN INI YANG SEBELUMNYA HILANG / TIDAK LENGKAP
    // =================================================================
    if (!empty($selected_tahun) && !empty($selected_kelas) && !empty($selected_mapel)) :
        // Dapatkan data NILAI dengan men-JOIN tabel mengajar
        // Ini lebih aman daripada mengambil satu id_mengajar saja, 
        // jaga-jaga jika ada duplikasi data di tabel mengajar.
        // Dapatkan data NILAI
        // PERBAIKAN: Jangan filter berdasarkan m.id_kelas secara kaku.
        // Cukup filter berdasarkan id_mapel, id_tahun_ajaran, dan PASTIKAN siswanya ada di kelas yang dipilih.
        $q_nilai = "SELECT n.id_siswa, n.jenis_nilai, n.nilai 
                    FROM nilai n
                    JOIN mengajar m ON n.id_mengajar = m.id_mengajar
                    WHERE m.id_mapel = {$selected_mapel} 
                      AND m.id_tahun_ajaran = {$selected_tahun}
                      AND n.id_siswa IN (SELECT id_siswa FROM siswa WHERE id_kelas = {$selected_kelas})";
        
        $res_nilai = mysqli_query($koneksi, $q_nilai);
        
        // Cek jika ada hasil
        // Kita tidak perlu mengecek id_mengajar secara eksplisit sekarang,
        // cukup cek apakah ada nilai atau siswanya ada.
        
        // Ambil Data Siswa
        $q_siswa = "SELECT id_siswa, nama_lengkap FROM siswa WHERE id_kelas = {$selected_kelas} ORDER BY nama_lengkap ASC";
        $res_siswa = mysqli_query($koneksi, $q_siswa);

        $nilai_siswa = [];
        if ($res_nilai) {
            while($row = mysqli_fetch_assoc($res_nilai)) {
                $nilai_siswa[$row['id_siswa']][$row['jenis_nilai']] = $row['nilai'];
            }
        }
        ?>
    <!-- TABEL LAPORAN NILAI -->
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-print me-1"></i>Hasil Laporan Nilai</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th class="text-start">Nama Siswa</th>
                            <th>Tugas</th>
                            <th>UTS</th>
                            <th>UAS</th>
                            <th>Praktik</th>
                            <th>Nilai Akhir</th>
                            <th>Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($res_siswa) > 0) {
                            $nomor = 1;
                            while($siswa = mysqli_fetch_assoc($res_siswa)): 
                                $id_siswa = $siswa['id_siswa'];
                                // Ambil nilai per jenis atau set 0 jika tidak ada
                                $tugas = $nilai_siswa[$id_siswa]['Tugas'] ?? 0;
                                $uts = $nilai_siswa[$id_siswa]['UTS'] ?? 0;
                                $uas = $nilai_siswa[$id_siswa]['UAS'] ?? 0;
                                $praktik = $nilai_siswa[$id_siswa]['Praktik'] ?? 0;

                                $nilai_akhir = hitungNilaiAkhir($tugas, $uts, $uas, $praktik);
                                $predikat = tentukanPredikat($nilai_akhir);
                        ?>
                        <tr>
                            <td><?php echo $nomor++; ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                            <td><?php echo $tugas; ?></td>
                            <td><?php echo $uts; ?></td>
                            <td><?php echo $uas; ?></td>
                            <td><?php echo $praktik; ?></td>
                            <td><?php echo number_format($nilai_akhir, 2); ?></td>
                            <td><strong><?php echo $predikat; ?></strong></td>
                        </tr>
                        <?php 
                            endwhile;
                        } else {
                            echo "<tr><td colspan='8' class='text-center'>Tidak ada siswa di kelas ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php 
    endif; 
    ?> 

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