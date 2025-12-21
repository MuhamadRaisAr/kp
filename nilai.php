<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Kelola Nilai";

// Ambil ID guru yang sedang login dari session
$id_guru_login = isset($_SESSION['id_guru']) ? (int)$_SESSION['id_guru'] : null;

// Query untuk dropdown: Ambil Tahun Ajaran (Tidak perlu prepared statement karena tidak ada input user)
$query_tahun = "SELECT id_tahun_ajaran, tahun_ajaran, semester FROM tahun_ajaran ORDER BY tahun_ajaran DESC";
$result_tahun = mysqli_query($koneksi, $query_tahun);

// Variabel untuk menampung pilihan filter dari URL
$selected_tahun = isset($_GET['tahun_ajaran']) ? (int)$_GET['tahun_ajaran'] : '';
$selected_kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : '';
$selected_mapel = isset($_GET['mapel']) ? (int)$_GET['mapel'] : '';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Kelola Nilai Siswa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Kelola Nilai</li>
    </ol>

    <?php if ($id_guru_login) : ?>
    
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i>Pilih Kriteria</div>
        <div class="card-body">
            <form method="GET" action="nilai.php">
                <div class="row">
                    <div class="col-md-4">
                        <label for="tahun_ajaran" class="form-label">Tahun Ajaran</label>
                        <select name="tahun_ajaran" id="tahun_ajaran" class="form-select" required>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <?php 
                            mysqli_data_seek($result_tahun, 0);
                            while($row = mysqli_fetch_assoc($result_tahun)) {
                                $selected = ($row['id_tahun_ajaran'] == $selected_tahun) ? 'selected' : '';
                                echo "<option value='{$row['id_tahun_ajaran']}' $selected>{$row['tahun_ajaran']} - {$row['semester']}</option>";
                            } 
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="kelas" class="form-label">Kelas</label>
                        <select name="kelas" id="kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php 
                            // -- Menggunakan Prepared Statement untuk keamanan --
                            $query_kelas_sql = "SELECT DISTINCT k.id_kelas, k.nama_kelas 
                                                FROM kelas k
                                                JOIN mengajar m ON k.id_kelas = m.id_kelas
                                                WHERE m.id_guru = ?
                                                ORDER BY k.nama_kelas ASC";
                            $stmt_kelas = mysqli_prepare($koneksi, $query_kelas_sql);
                            mysqli_stmt_bind_param($stmt_kelas, "i", $id_guru_login);
                            mysqli_stmt_execute($stmt_kelas);
                            $result_kelas = mysqli_stmt_get_result($stmt_kelas);
                            while($row = mysqli_fetch_assoc($result_kelas)) {
                                $selected = ($row['id_kelas'] == $selected_kelas) ? 'selected' : '';
                                echo "<option value='{$row['id_kelas']}' $selected>{$row['nama_kelas']}</option>";
                            }
                            mysqli_stmt_close($stmt_kelas);
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="mapel" class="form-label">Mata Pelajaran</label>
                        <select name="mapel" id="mapel" class="form-select" required>
                            <option value="">-- Pilih Kelas Dulu --</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Tampilkan Siswa</button>
            </form>
        </div>
    </div>

    <?php
    if (!empty($selected_tahun) && !empty($selected_kelas) && !empty($selected_mapel)) :
        // -- (Sisa kode PHP di sini TIDAK BERUBAH, biarkan saja) --
        $query_mengajar_sql = "SELECT id_mengajar FROM mengajar WHERE id_guru = ? AND id_kelas = ? AND id_mapel = ? AND id_tahun_ajaran = ?";
        $stmt_mengajar = mysqli_prepare($koneksi, $query_mengajar_sql);
        mysqli_stmt_bind_param($stmt_mengajar, "iiii", $id_guru_login, $selected_kelas, $selected_mapel, $selected_tahun);
        mysqli_stmt_execute($stmt_mengajar);
        $result_mengajar = mysqli_stmt_get_result($stmt_mengajar);
        
        if(mysqli_num_rows($result_mengajar) > 0) {
            $data_mengajar = mysqli_fetch_assoc($result_mengajar);
            $id_mengajar = $data_mengajar['id_mengajar'];

            $query_siswa_sql = "SELECT id_siswa, nama_lengkap FROM siswa WHERE id_kelas = ? ORDER BY nama_lengkap ASC";
            $stmt_siswa = mysqli_prepare($koneksi, $query_siswa_sql);
            mysqli_stmt_bind_param($stmt_siswa, "i", $selected_kelas);
            mysqli_stmt_execute($stmt_siswa);
            $result_siswa = mysqli_stmt_get_result($stmt_siswa);

            $nilai_existing = [];
            $query_nilai_sql = "SELECT id_siswa, jenis_nilai, nilai FROM nilai WHERE id_mengajar = ?";
            $stmt_nilai = mysqli_prepare($koneksi, $query_nilai_sql);
            mysqli_stmt_bind_param($stmt_nilai, "i", $id_mengajar);
            mysqli_stmt_execute($stmt_nilai);
            $result_nilai_existing = mysqli_stmt_get_result($stmt_nilai);
            while($row = mysqli_fetch_assoc($result_nilai_existing)) {
                $nilai_existing[$row['id_siswa']][$row['jenis_nilai']] = $row['nilai'];
            }
            mysqli_stmt_close($stmt_nilai);
        ?>
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-edit me-1"></i>Input Nilai Siswa</div>
                <div class="card-body">
                    <form action="proses_simpan_nilai.php" method="POST">
                        <input type="hidden" name="id_mengajar" value="<?php echo htmlspecialchars($id_mengajar); ?>">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Siswa</th>
                                        <th>Tugas</th>
                                        <th>UTS</th>
                                        <th>UAS</th>
                                        <th>Praktik</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $nomor = 1;
                                    while($siswa = mysqli_fetch_assoc($result_siswa)): 
                                        $id_siswa = $siswa['id_siswa'];
                                    ?>
                                    <tr>
                                        <td><?php echo $nomor++; ?></td>
                                        <td><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                                        <?php 
                                        $jenis_penilaian = ['Tugas', 'UTS', 'UAS', 'Praktik'];
                                        foreach ($jenis_penilaian as $jenis) :
                                            $nilai = isset($nilai_existing[$id_siswa][$jenis]) ? htmlspecialchars($nilai_existing[$id_siswa][$jenis]) : '';
                                        ?>
                                        <td>
                                            <input type="number" name="nilai[<?php echo $id_siswa; ?>][<?php echo $jenis; ?>]" value="<?php echo $nilai; ?>" class="form-control" min="0" max="100" step="0.01">
                                        </td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endwhile; mysqli_stmt_close($stmt_siswa); ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-success float-end">Simpan Semua Perubahan</button>
                    </form>
                </div>
            </div>
    <?php 
        mysqli_stmt_close($stmt_mengajar);
        } else {
            echo '<div class="alert alert-info">Data mengajar tidak ditemukan untuk kriteria yang dipilih. Silakan hubungi admin.</div>';
        }
    endif; 
    ?>

    <?php else: ?>
    <div class="alert alert-warning">
        Halaman ini hanya dapat diakses oleh pengguna dengan peran sebagai **Guru**.
    </div>
    <?php endif; ?>

</div>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Ambil elemen dropdown
    const tahunDropdown = document.getElementById('tahun_ajaran');
    const kelasDropdown = document.getElementById('kelas');
    const mapelDropdown = document.getElementById('mapel');
    
    // Simpan nilai mapel yang dipilih dari PHP (jika ada)
    const selectedMapelId = '<?php echo $selected_mapel; ?>';

    // Fungsi untuk mengambil data mapel
    function fetchMapel() {
        const tahunId = tahunDropdown.value;
        const kelasId = kelasDropdown.value;
        
        // Reset dropdown mapel
        mapelDropdown.innerHTML = '<option value="">-- Loading... --</option>';

        // Hanya panggil API jika KELAS sudah dipilih (karena API-mu cuma butuh id_kelas)
        if (kelasId) {
            
            // ===============================================
            // PERBAIKAN: Ubah 'kelas_id' -> 'id_kelas' dan Hapus 'tahun_id'
            // Sesuai dengan apa yang DITERIMA oleh file api_get_mapel.php
            // ===============================================
            fetch(`api_get_mapel.php?id_kelas=${kelasId}`)
                .then(response => response.json())
                .then(data => {
                    // Kosongkan lagi
                    mapelDropdown.innerHTML = '';
                    
                    if (data.length > 0) {
                        mapelDropdown.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>';
                        data.forEach(mapel => {
                            const option = document.createElement('option');
                            option.value = mapel.id_mapel;
                            option.textContent = mapel.nama_mapel;
                            
                            // Cek apakah ini mapel yang seharusnya terpilih (saat load halaman)
                            if (mapel.id_mapel == selectedMapelId) {
                                option.selected = true;
                            }
                            
                            mapelDropdown.appendChild(option);
                        });
                    } else {
                        mapelDropdown.innerHTML = '<option value="">-- Tidak ada mapel --</option>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching mapel:', error);
                    mapelDropdown.innerHTML = '<option value="">-- Gagal memuat --</option>';
                });
        } else {
            // Jika kelas belum dipilih
            mapelDropdown.innerHTML = '<option value="">-- Pilih Kelas Dulu --</option>';
        }
    }

    // Tambahkan event listener ke dropdown Tahun Ajaran dan Kelas
    // Kita cuma butuh listener di 'kelas' karena API-nya cuma pakai 'id_kelas'
    // tapi kita biarkan 'tahun_ajaran' juga me-reset, untuk jaga-jaga
    tahunDropdown.addEventListener('change', fetchMapel);
    kelasDropdown.addEventListener('change', fetchMapel);

    // Panggil fungsi sekali saat halaman dimuat
    fetchMapel();
});
</script>