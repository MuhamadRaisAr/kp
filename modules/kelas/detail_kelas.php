<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Detail Pengajar";
$id_kelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : 0;

// Ambil info nama kelas dan wali kelas
$stmt = mysqli_prepare($koneksi, "SELECT k.nama_kelas, g.nama_lengkap as nama_wali 
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

// Proses tambah guru pengajar
if (isset($_POST['tambah_guru'])) {
    $id_guru_pengajar = (int)$_POST['id_guru'];
    $id_mapel = (int)$_POST['id_mapel'];
    $tahun_aktif = 11; // Default hardcoded, sebaiknya ambil dari tahun aktif dinamis
    
    // Ambil tahun aktif
    $q_ta = mysqli_query($koneksi, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status_aktif='Aktif' LIMIT 1");
    if($r_ta = mysqli_fetch_assoc($q_ta)) {
        $tahun_aktif = $r_ta['id_tahun_ajaran'];
    }

    // Insert ke mengajar table
    // Perhatikan: user bilang "bisa ditambahkan guru". Ini berarti assign guru mapel ke kelas ini.
    // Query INSERT ...
    $stmt_ins = mysqli_prepare($koneksi, "INSERT INTO mengajar (id_guru, id_mapel, id_kelas, id_tahun_ajaran, hari, jam_mulai, jam_selesai) VALUES (?, ?, ?, ?, 'Senin', '07:00:00', '08:00:00')");
    // Default hari senin jam 7-8 karena form simple, detail jadwal bisa diedit nanti di menu Penugasan.
    mysqli_stmt_bind_param($stmt_ins, "iiii", $id_guru_pengajar, $id_mapel, $id_kelas, $tahun_aktif);
    
    if (mysqli_stmt_execute($stmt_ins)) {
        $msg_sukses = "Guru pengajar berhasil ditambahkan ke kelas ini.";
    } else {
        $msg_error = "Gagal menambahkan guru: " . mysqli_error($koneksi);
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Detail Pengajar Kelas <?php echo htmlspecialchars($info_kelas['nama_kelas']); ?></h1>
    <h5 class="text-muted mb-4">Wali Kelas: <?php echo htmlspecialchars($wali_kelas); ?></h5>
    
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="kelas.php">Daftar Wali Kelas</a></li>
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
        <div class="col-lg-8">
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
                                <th>Jadwal Default</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query_pengajar = "SELECT m.id_mengajar, g.nama_lengkap, mp.nama_mapel, m.hari, m.jam_mulai, m.jam_selesai
                                               FROM mengajar m
                                               JOIN guru g ON m.id_guru = g.id_guru
                                               JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                               WHERE m.id_kelas = $id_kelas
                                               ORDER BY mp.nama_mapel ASC";
                            $res_pengajar = mysqli_query($koneksi, $query_pengajar);
                            if (mysqli_num_rows($res_pengajar) > 0) {
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($res_pengajar)) {
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nama_mapel']); ?></td>
                                        <td><?php echo htmlspecialchars($row['hari']) . ', ' . date('H:i', strtotime($row['jam_mulai'])); ?></td>
                                        <td>
                                            <a href="../mengajar/hapus_mengajar.php?id=<?php echo $row['id_mengajar']; ?>&ref=kelas&id_kelas=<?php echo $id_kelas; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Hapus pengajar ini dari kelas?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted">Belum ada guru pengajar yang ditugaskan di kelas ini.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
                        <div class="d-grid">
                            <button type="submit" name="tambah_guru" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Tambahkan ke Kelas
                            </button>
                        </div>
                        <small class="text-muted d-block mt-2 text-center">
                            *Jadwal default senin 07:00. Ubah detail di menu Penugasan.
                        </small>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>
