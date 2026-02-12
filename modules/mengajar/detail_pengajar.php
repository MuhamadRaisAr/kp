<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Detail Pengajar";
$id_kelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;

// Ambil info nama kelas dan wali kelas
// Ambil info nama kelas dan wali kelas
$stmt = mysqli_prepare($koneksi, "SELECT k.nama_kelas, k.id_tahun_ajaran, g.nama_lengkap as nama_wali, k.id_guru_wali_kelas 
                                  FROM kelas k 
                                  LEFT JOIN guru g ON k.id_guru_wali_kelas = g.id_guru 
                                  WHERE k.id_kelas = ?");
mysqli_stmt_bind_param($stmt, "i", $id_kelas);
mysqli_stmt_execute($stmt);
$result_info = mysqli_stmt_get_result($stmt);
$info_kelas = mysqli_fetch_assoc($result_info);

if (!$info_kelas) {
    echo '<div class="alert alert-danger mx-4 mt-4">Data kelas tidak ditemukan.</div>';
    require_once '../../includes/footer.php';
    exit();
}

$wali_kelas = $info_kelas['nama_wali'] ? $info_kelas['nama_wali'] : "Belum ditentukan";
$id_tahun_breadcrumb = $info_kelas['id_tahun_ajaran'];
$role = $_SESSION['role'] ?? 'guest'; 

// Cek hak akses: Admin ATAU Wali Kelas dari kelas ini
$is_wali_kelas_this = false;
if ($role == 'guru' && isset($_SESSION['id_guru']) && $_SESSION['id_guru'] == $info_kelas['id_guru_wali_kelas']) {
    $is_wali_kelas_this = true;
}
$can_manage = ($role == 'admin' || $is_wali_kelas_this);


// Proses tambah guru pengajar
if (isset($_POST['tambah_guru'])) {
    if ($can_manage) {
        $id_guru_pengajar = (int)$_POST['id_guru'];
        $id_mapel = (int)$_POST['id_mapel'];
        $hari = $_POST['hari'];
        $jam_mulai = $_POST['jam_mulai'];
        $jam_selesai = $_POST['jam_selesai'];
        $tahun_ajaran_target = $info_kelas['id_tahun_ajaran']; 

        // Validasi sederhana
        if(empty($hari) || empty($jam_mulai) || empty($jam_selesai)) {
            $msg_error = "Hari dan Jam wajib diisi.";
        } else {
            // Insert ke mengajar table
            $stmt_ins = mysqli_prepare($koneksi, "INSERT INTO mengajar (id_guru, id_mapel, id_kelas, id_tahun_ajaran, hari, jam_mulai, jam_selesai) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_ins, "iiiisss", $id_guru_pengajar, $id_mapel, $id_kelas, $tahun_ajaran_target, $hari, $jam_mulai, $jam_selesai);
            
            if (mysqli_stmt_execute($stmt_ins)) {
                $msg_sukses = "Guru pengajar berhasil ditambahkan ke kelas ini.";
            } else {
                $msg_error = "Gagal menambahkan guru: " . mysqli_error($koneksi);
            }
        }
    } else {
        $msg_error = "Anda tidak memiliki akses untuk menambah guru.";
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Detail Pengajar Kelas <?php echo htmlspecialchars($info_kelas['nama_kelas']); ?></h1>
    <h5 class="text-muted mb-4">Wali Kelas: <?php echo htmlspecialchars($wali_kelas); ?></h5>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="mengajar.php">Daftar Wali Kelas</a></li>
        <li class="breadcrumb-item"><a href="penugasan_kelas.php?id_tahun=<?php echo $id_tahun_breadcrumb; ?>">Kelas</a></li>
        <li class="breadcrumb-item active">Detail Pengajar</li>
    </ol>

    <?php if (isset($msg_sukses)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $msg_sukses; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($msg_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $msg_error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Kolom Kiri: Daftar Guru Pengajar -->
        <div class="<?php echo ($can_manage) ? 'col-lg-8' : 'col-lg-12'; ?>">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chalkboard-teacher me-1"></i>
                    Daftar Guru Pengajar di Kelas Ini
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Guru</th>
                                <th>Mata Pelajaran</th>
                                <th>Jadwal</th>
                                <?php if ($can_manage): ?>
                                <th>Aksi</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_pengajar = "SELECT m.id_mengajar, g.nama_lengkap, mp.nama_mapel, m.hari, m.jam_mulai, m.jam_selesai
                                               FROM mengajar m
                                               JOIN guru g ON m.id_guru = g.id_guru
                                               JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                               WHERE m.id_kelas = $id_kelas
                                               ORDER BY m.hari DESC, m.jam_mulai ASC";
                            $res_pengajar = mysqli_query($koneksi, $query_pengajar);
                            if (mysqli_num_rows($res_pengajar) > 0) {
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($res_pengajar)) {
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_mapel']); ?></td>
                                        <td><?php echo htmlspecialchars($row['hari']) . ', ' . date('H:i', strtotime($row['jam_mulai'])) . ' - ' . date('H:i', strtotime($row['jam_selesai'])); ?></td>
                                        <?php if ($can_manage): ?>
                                        <td>
                                            <a href="edit_mengajar.php?id=<?php echo $row['id_mengajar']; ?>" class="btn btn-warning btn-sm me-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="hapus_mengajar.php?id=<?php echo $row['id_mengajar']; ?>&ref=kelas&id_kelas=<?php echo $id_kelas; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Hapus pengajar ini dari kelas?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="' . (($can_manage) ? 5 : 4) . '" class="text-center text-muted">Belum ada guru pengajar yang ditugaskan di kelas ini.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($can_manage): ?>
        <!-- Kolom Kanan: Form Tambah Guru -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-plus-circle me-1"></i>
                    Tambah Guru Pengajar
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="id_guru" class="form-label">Pilih Guru</label>
                            <select name="id_guru" id="id_guru" class="form-select" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php
                                $q_guru = mysqli_query($koneksi, "SELECT id_guru, nama_lengkap FROM guru ORDER BY nama_lengkap ASC");
                                while ($rg = mysqli_fetch_assoc($q_guru)) {
                                    echo "<option value='".$rg['id_guru']."'>".htmlspecialchars($rg['nama_lengkap'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_mapel" class="form-label">Pilih Mata Pelajaran</label>
                            <select name="id_mapel" id="id_mapel" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php
                                $q_mapel = mysqli_query($koneksi, "SELECT id_mapel, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel ASC");
                                while ($rm = mysqli_fetch_assoc($q_mapel)) {
                                    echo "<option value='".$rm['id_mapel']."'>".htmlspecialchars($rm['nama_mapel'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="hari" class="form-label">Hari</label>
                            <select name="hari" id="hari" class="form-select" required>
                                <option value="">-- Pilih Hari --</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="jam_mulai" class="form-label">Jam Mulai</label>
                                <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label for="jam_selesai" class="form-label">Jam Selesai</label>
                                <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" required>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="tambah_guru" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Tambahkan ke Kelas
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>
