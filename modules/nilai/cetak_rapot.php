<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Cetak Rapor Siswa";

// Ambil data session
$id_guru_login = isset($_SESSION['id_guru']) ? $_SESSION['id_guru'] : null;
$role = strtolower($_SESSION['role']);

// --- PROTEKSI AKSES DASAR ---
// Hanya Admin dan Guru yang boleh masuk ke halaman ini
if ($role !== 'admin' && $role !== 'guru') {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses Ditolak! Halaman ini hanya untuk Admin atau Wali Kelas.</div></div>';
    require_once '../../includes/footer.php';
    exit();
}

// Ambil data Tahun Ajaran Aktif
$query_tahun = "SELECT * FROM tahun_ajaran WHERE status_aktif = 'Aktif' ORDER BY tahun_ajaran DESC";
$result_tahun = mysqli_query($koneksi, $query_tahun);

// --- LOGIKA FILTER KELAS ---
if ($role == 'admin') {
    // Admin bisa melihat semua kelas
    $query_kelas = "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC";
} else {
    // Guru HANYA bisa melihat kelas di mana dia menjadi WALI KELAS
    $query_kelas = "SELECT id_kelas, nama_kelas FROM kelas WHERE id_guru_wali_kelas = '$id_guru_login' ORDER BY nama_kelas ASC";
}
$result_kelas = mysqli_query($koneksi, $query_kelas);

$selected_tahun = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';
$selected_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Cetak Rapor Siswa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Cetak Rapor</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            <?php echo ($role == 'admin') ? 'Pilih Kelas & Tahun Ajaran' : 'Data Kelas Perwalian Anda'; ?>
        </div>
        <div class="card-body">
            <form method="GET" action="cetak_rapot.php">
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label">Tahun Ajaran</label>
                        <select name="tahun_ajaran" class="form-select" required>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <?php while($row = mysqli_fetch_assoc($result_tahun)) {
                                $selected = ($row['id_tahun_ajaran'] == $selected_tahun) ? 'selected' : '';
                                echo "<option value='{$row['id_tahun_ajaran']}' $selected>{$row['tahun_ajaran']} - {$row['semester']}</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Kelas</label>
                        <select name="kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php 
                            if (mysqli_num_rows($result_kelas) > 0) {
                                while($row = mysqli_fetch_assoc($result_kelas)) {
                                    $selected = ($row['id_kelas'] == $selected_kelas) ? 'selected' : '';
                                    echo "<option value='{$row['id_kelas']}' $selected>{$row['nama_kelas']}</option>";
                                }
                            } else {
                                echo "<option value='' disabled>Anda bukan Wali Kelas di kelas manapun.</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php
    // Tampilkan daftar siswa HANYA JIKA filter sudah dipilih
    if (!empty($selected_tahun) && !empty($selected_kelas)) :
        
        // --- KEAMANAN TAMBAHAN (URL BYPASS PROTECTION) ---
        // Jika Guru mencoba mengganti ID Kelas lewat URL ke kelas yang bukan miliknya
        if ($role == 'guru') {
            $check_wali = mysqli_query($koneksi, "SELECT id_kelas FROM kelas WHERE id_kelas = '$selected_kelas' AND id_guru_wali_kelas = '$id_guru_login'");
            if (mysqli_num_rows($check_wali) == 0) {
                echo '<div class="alert alert-danger">Anda tidak memiliki otoritas sebagai Wali Kelas untuk melihat data ini.</div>';
                exit();
            }
        }

        $query_siswa = "SELECT id_siswa, nis, nama_lengkap FROM siswa WHERE id_kelas = '$selected_kelas' ORDER BY nama_lengkap ASC";
        $result_siswa = mysqli_query($koneksi, $query_siswa);
    ?>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-users me-1"></i>Daftar Siswa</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">No</th>
                            <th>NIS</th>
                            <th>Nama Lengkap</th>
                            <th width="200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($result_siswa) > 0) {
                            $nomor = 1;
                            while($siswa = mysqli_fetch_assoc($result_siswa)) {
                                echo "<tr>";
                                echo "<td class='text-center'>" . $nomor++ . "</td>";
                                echo "<td>" . htmlspecialchars($siswa['nis']) . "</td>";
                                echo "<td>" . htmlspecialchars($siswa['nama_lengkap']) . "</td>";
                                echo "<td>";
                                echo "<a href='generate_pdf.php?id_siswa={$siswa['id_siswa']}&id_tahun_ajaran={$selected_tahun}' class='btn btn-success btn-sm w-100' target='_blank'><i class='fas fa-print me-2'></i>Cetak Rapor</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center'>Tidak ada siswa di kelas ini.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
