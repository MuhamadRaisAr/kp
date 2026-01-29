<?php
// Panggil file-file yang dibutuhkan
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Kelola Detail Ujian";
$id_guru_login = (int)$_SESSION['id_guru'];

// 1. Ambil ID Ujian dari URL
$id_ujian = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_ujian <= 0) {
    header("Location: ujian.php?error=Ujian tidak valid");
    exit();
}

// 2. Query Detail Ujian & Validasi Kepemilikan
$query_detail = "SELECT 
                    u.id_ujian, u.judul_ujian, u.durasi_menit, u.waktu_mulai, u.waktu_selesai, u.status_ujian,
                    mp.nama_mapel, 
                    k.nama_kelas, 
                    ta.tahun_ajaran, ta.semester
                FROM ujian u
                JOIN mengajar m ON u.id_mengajar = m.id_mengajar
                JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                JOIN kelas k ON m.id_kelas = k.id_kelas
                JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran
                WHERE u.id_ujian = ? AND m.id_guru = ?";
                
$stmt_detail = mysqli_prepare($koneksi, $query_detail);
mysqli_stmt_bind_param($stmt_detail, "ii", $id_ujian, $id_guru_login);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);

if (mysqli_num_rows($result_detail) == 0) {
    header("Location: ujian.php?error=Akses ditolak atau ujian tidak ditemukan");
    exit();
}
$ujian_data = mysqli_fetch_assoc($result_detail);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Kelola Ujian: <?php echo htmlspecialchars($ujian_data['judul_ujian']); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="ujian.php">Manajemen Ujian</a></li>
        <li class="breadcrumb-item active">Detail Ujian</li>
    </ol>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'sukses_buat'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Berhasil!</strong> Ujian baru telah dibuat. Silakan tambahkan soal.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'sukses_soal'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Berhasil!</strong> Soal baru telah ditambahkan.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] == 'sukses_publish'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Berhasil!</strong> Ujian telah di-publish dan bisa diakses oleh siswa.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>


    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-info-circle me-1"></i>Detail Ujian</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <p><strong>Mapel:</strong> <?php echo htmlspecialchars($ujian_data['nama_mapel']); ?></p>
                    <p><strong>Kelas:</strong> <?php echo htmlspecialchars($ujian_data['nama_kelas']); ?></p>
                    <p><strong>Tahun Ajaran:</strong> <?php echo htmlspecialchars($ujian_data['tahun_ajaran'] . " (" . $ujian_data['semester'] . ")"); ?></p>
                    <p><strong>Durasi:</strong> <?php echo $ujian_data['durasi_menit']; ?> Menit</p>
                    <p><strong>Jadwal:</strong> <?php echo date('d M Y, H:i', strtotime($ujian_data['waktu_mulai'])) . " s/d " . date('d M Y, H:i', strtotime($ujian_data['waktu_selesai'])); ?></p>
                </div>
                <div class="col-md-4 text-md-end">
                    <strong>Status:</strong> 
                    <?php
                    $status = $ujian_data['status_ujian'];
                    $waktu_sekarang = time();
                    $waktu_selesai_ts = strtotime($ujian_data['waktu_selesai']);
                    $is_expired = ($waktu_sekarang > $waktu_selesai_ts);

                    if ($status == 'Draft') {
                        echo '<span class="badge bg-secondary fs-6">Draft</span>';
                    } elseif ($status == 'Published') {
                        if ($is_expired) {
                             echo '<span class="badge bg-danger fs-6">Berakhir / Terlewat</span>';
                        } else {
                             echo '<span class="badge bg-success fs-6">Published (Aktif)</span>';
                        }
                    } elseif ($status == 'Selesai') {
                        echo '<span class="badge bg-dark fs-6">Selesai</span>';
                    }
                    ?>
                    
                    <div class="mt-3">
                        <a href="ujian_edit.php?id=<?php echo $id_ujian; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit me-1"></i> Edit Pengaturan</a>
                        
                        <?php if ($status == 'Draft'): ?>
                        <form action="proses_ujian_publish.php" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin mem-publish ujian ini? Setelah di-publish, soal tidak bisa diubah lagi.');">
                            <input type="hidden" name="id_ujian" value="<?php echo $id_ujian; ?>">
                            <button type="submit" class="btn btn-success btn-sm ms-1"><i class="fas fa-paper-plane me-2"></i>Publish</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FITUR BUKA KEMBALI UJIAN (Khusus Published/Selesai/Expired) -->
    <?php if ($status != 'Draft'): ?> 
    <div class="card mb-4 border-warning">
        <div class="card-header bg-warning text-dark"><i class="fas fa-history me-1"></i> Perpanjang Waktu / Buka Kembali Ujian</div>
        <div class="card-body">
            <p class="mb-2">Gunakan fitur ini jika batas waktu ujian sudah lewat dan Anda ingin membukanya kembali untuk siswa.</p>
            <form action="proses_ujian_buka_kembali.php" method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="id_ujian" value="<?php echo $id_ujian; ?>">
                
                <div class="col-md-5">
                    <label class="form-label">Set Waktu Selesai Baru:</label>
                    <input type="datetime-local" class="form-control" name="waktu_selesai_baru" required>
                </div>
                <!-- Atau bisa tambahkan opsi instan -->
                <!-- 
                <div class="col-md-3">
                    <label class="form-label">Atau Tambah Durasi:</label>
                    <select class="form-select" name="tambah_menit">
                        <option value="0">-- Pilih --</option>
                        <option value="60">+ 1 Jam dari Sekarang</option>
                        <option value="120">+ 2 Jam dari Sekarang</option>
                        <option value="1440">+ 24 Jam dari Sekarang</option>
                    </select>
                </div> 
                -->
                <div class="col-md-3">
                    <button type="submit" class="btn btn-dark" onclick="return confirm('Apakah Anda yakin ingin memperbarui waktu selesai ujian ini? Siswa akan dapat mengakses ujian kembali.');">
                        <i class="fas fa-unlock-alt me-1"></i> Simpan & Buka
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // ==========================================================
    // PERBAIKAN: Pindahkan hitungan soal ke sini (ke luar blok if)
    // ==========================================================
    // Hitung total soal yang sudah ada.
    $q_total_soal = mysqli_query($koneksi, "SELECT COUNT(id_soal) AS total FROM ujian_soal WHERE id_ujian = $id_ujian");
    $data_total_soal = mysqli_fetch_assoc($q_total_soal);
    $total_soal = $data_total_soal ? $data_total_soal['total'] : 0;
    $next_nomor = $total_soal + 1;
    // ==========================================================
    ?>


    <?php if ($ujian_data['status_ujian'] == 'Draft'): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-plus-circle me-1"></i>Tambah Soal Baru</div>
        <div class="card-body">
            <form action="proses_soal_tambah.php" method="POST">
                <input type="hidden" name="id_ujian" value="<?php echo $id_ujian; ?>">
                
                <div class="mb-3">
                    <label class="form-label"><strong>Soal Nomor <?php echo $next_nomor; ?></strong></label>
                    <textarea class="form-control" name="pertanyaan" rows="3" placeholder="Ketik pertanyaan di sini..." required></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi A</label>
                        <input type="text" class="form-control" name="opsi_a" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi B</label>
                        <input type="text" class="form-control" name="opsi_b" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi C</label>
                        <input type="text" class="form-control" name="opsi_c" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi D</label>
                        <input type="text" class="form-control" name="opsi_d" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi E (Opsional)</label>
                        <input type="text" class="form-control" name="opsi_e">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kunci Jawaban</label>
                        <select class="form-select" name="kunci_jawaban" required>
                            <option value="">-- Pilih Kunci --</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                            <option value="E">E</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Simpan Soal</button>
            </form>
        </div>
    </div>
    <?php endif; ?> <div class="card mb-4">
        <div class="card-header"><i class="fas fa-list-ol me-1"></i>Bank Soal (<?php echo $total_soal; ?> Soal)</div>
        <div class="card-body">
            <?php
            $query_soal = "SELECT * FROM ujian_soal WHERE id_ujian = ? ORDER BY nomor_soal ASC";
            $stmt_soal = mysqli_prepare($koneksi, $query_soal);
            mysqli_stmt_bind_param($stmt_soal, "i", $id_ujian);
            mysqli_stmt_execute($stmt_soal);
            $result_soal = mysqli_stmt_get_result($stmt_soal);
            
            if (mysqli_num_rows($result_soal) > 0) {
                while ($soal = mysqli_fetch_assoc($result_soal)) {
                    echo "<div class='mb-4 p-3 border rounded'>";
                    echo "<strong>" . $soal['nomor_soal'] . ". " . htmlspecialchars($soal['pertanyaan']) . "</strong>";
                    
                    // Tampilkan Opsi
                    echo "<ul class='list-unstyled ms-3 mt-2'>";
                    $opsi = ['A', 'B', 'C', 'D', 'E'];
                    foreach ($opsi as $o) {
                        $kolom_opsi = 'opsi_' . strtolower($o); // 'opsi_a', 'opsi_b', dst.
                        if (!empty($soal[$kolom_opsi])) {
                            $is_kunci = ($soal['kunci_jawaban'] == $o);
                            echo "<li class='" . ($is_kunci ? 'text-success fw-bold' : '') . "'>" . 
                                 $o . ". " . htmlspecialchars($soal[$kolom_opsi]) . 
                                 ($is_kunci ? " <i class='fas fa-check'></i> (Kunci)" : "") .
                                 "</li>";
                        }
                    }
                    echo "</ul>";
                    
                    // Tombol Aksi (Edit/Hapus) SELALU MUNCUL (Request User)
                    echo "<div class='mt-2'>
                            <a href='soal_edit.php?id_soal=" . $soal['id_soal'] . "' class='btn btn-warning btn-sm'><i class='fas fa-edit'></i> Edit</a>
                            <a href='soal_hapus.php?id_soal=" . $soal['id_soal'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin ingin menghapus soal ini?\");'><i class='fas fa-trash'></i> Hapus</a>
                          </div>";
                    
                    echo "</div>";
                }
            } else {
                echo "<p class='text-center text-muted'>Belum ada soal yang ditambahkan untuk ujian ini.</p>";
            }
            mysqli_stmt_close($stmt_soal);
            ?>
        </div>
    </div>

</div>

<?php
// Panggil file footer.php
require_once '../../includes/footer.php';
?>
