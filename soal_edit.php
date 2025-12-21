<?php
// Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Edit Soal Ujian";
$id_guru_login = (int)$_SESSION['id_guru'];

// 1. Ambil ID Soal dari URL
$id_soal = isset($_GET['id_soal']) ? (int)$_GET['id_soal'] : 0;
if ($id_soal <= 0) {
    header("Location: ujian.php?error=Soal tidak valid");
    exit();
}

// 2. Query Detail Soal & Validasi Kepemilikan (via Ujian & Guru)
// Ambil data soal + id_ujian + pastikan ujiannya milik guru ini dan masih draft
$query_soal = "SELECT us.*, u.status_ujian
               FROM ujian_soal us
               JOIN ujian u ON us.id_ujian = u.id_ujian
               JOIN mengajar m ON u.id_mengajar = m.id_mengajar
               WHERE us.id_soal = ? AND m.id_guru = ?";
               
$stmt_soal = mysqli_prepare($koneksi, $query_soal);
mysqli_stmt_bind_param($stmt_soal, "ii", $id_soal, $id_guru_login);
mysqli_stmt_execute($stmt_soal);
$result_soal = mysqli_stmt_get_result($stmt_soal);

if (mysqli_num_rows($result_soal) == 0) {
    header("Location: ujian.php?error=Akses ditolak atau soal tidak ditemukan");
    exit();
}
$soal_data = mysqli_fetch_assoc($result_soal);

// 3. Validasi Status Ujian: Hanya bisa edit jika masih 'Draft'
if ($soal_data['status_ujian'] !== 'Draft') {
    header("Location: ujian_detail.php?id=" . $soal_data['id_ujian'] . "&error=Tidak bisa edit soal, ujian sudah di-publish.");
    exit();
}
$id_ujian = $soal_data['id_ujian']; // Simpan id_ujian untuk redirect
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Soal Nomor <?php echo $soal_data['nomor_soal']; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="ujian.php">Manajemen Ujian</a></li>
        <li class="breadcrumb-item"><a href="ujian_detail.php?id=<?php echo $id_ujian; ?>">Detail Ujian</a></li>
        <li class="breadcrumb-item active">Edit Soal</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-edit me-1"></i>Form Edit Soal</div>
        <div class="card-body">
            <form action="proses_soal_edit.php" method="POST">
                <input type="hidden" name="id_soal" value="<?php echo $id_soal; ?>">
                <input type="hidden" name="id_ujian" value="<?php echo $id_ujian; ?>">
                
                <div class="mb-3">
                    <label class="form-label"><strong>Pertanyaan</strong></label>
                    <textarea class="form-control" name="pertanyaan" rows="3" required><?php echo htmlspecialchars($soal_data['pertanyaan']); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi A</label>
                        <input type="text" class="form-control" name="opsi_a" value="<?php echo htmlspecialchars($soal_data['opsi_a']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi B</label>
                        <input type="text" class="form-control" name="opsi_b" value="<?php echo htmlspecialchars($soal_data['opsi_b']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi C</label>
                        <input type="text" class="form-control" name="opsi_c" value="<?php echo htmlspecialchars($soal_data['opsi_c']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi D</label>
                        <input type="text" class="form-control" name="opsi_d" value="<?php echo htmlspecialchars($soal_data['opsi_d']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Opsi E (Opsional)</label>
                        <input type="text" class="form-control" name="opsi_e" value="<?php echo htmlspecialchars($soal_data['opsi_e'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kunci Jawaban</label>
                        <select class="form-select" name="kunci_jawaban" required>
                            <option value="">-- Pilih Kunci --</option>
                            <?php 
                            $opsi = ['A', 'B', 'C', 'D', 'E'];
                            foreach ($opsi as $o) {
                                $selected = ($soal_data['kunci_jawaban'] == $o) ? 'selected' : '';
                                echo "<option value='$o' $selected>$o</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Simpan Perubahan Soal</button>
                <a href="ujian_detail.php?id=<?php echo $id_ujian; ?>" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>