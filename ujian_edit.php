<?php
// Panggil file-file yang dibutuhkan (TANPA header.php dulu)
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

// Judul halaman disiapkan dulu
$judul_halaman = "Edit Ujian";

// Pastikan yang login adalah guru
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    // Redirect SEBELUM header.php dipanggil
    header("Location: login.php?error=Akses ditolak"); // Arahkan ke login jika tidak dikenali sebagai guru
    exit();
}
$id_guru_login = (int)$_SESSION['id_guru'];

// 1. Ambil ID Ujian dari URL
$id_ujian = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_ujian <= 0) {
    header("Location: ujian.php?error=Ujian tidak valid");
    exit();
}

// 2. Query Detail Ujian yang Akan Diedit & Validasi Kepemilikan
$query_ujian = "SELECT u.*, m.id_guru
                FROM ujian u
                JOIN mengajar m ON u.id_mengajar = m.id_mengajar
                WHERE u.id_ujian = ? AND m.id_guru = ?";
$stmt_ujian = mysqli_prepare($koneksi, $query_ujian);
mysqli_stmt_bind_param($stmt_ujian, "ii", $id_ujian, $id_guru_login);
mysqli_stmt_execute($stmt_ujian);
$result_ujian = mysqli_stmt_get_result($stmt_ujian);

if (mysqli_num_rows($result_ujian) == 0) {
    header("Location: ujian.php?error=Akses ditolak atau ujian tidak ditemukan");
    exit();
}
$ujian_data = mysqli_fetch_assoc($result_ujian);

// ==========================================================
// PINDAHKAN PENGECEKAN STATUS KE SINI (SEBELUM header.php)
// ==========================================================
// Validasi status: Hanya bisa edit jika status 'Draft'
if ($ujian_data['status_ujian'] !== 'Draft') {
     // Redirect SEKARANG jika status bukan Draft
     header("Location: ujian_detail.php?id=" . $id_ujian . "&error=Tidak bisa edit, ujian sudah di-publish.");
     exit();
}
// ==========================================================

// Jika lolos semua cek di atas, BARU panggil header.php
require_once 'includes/header.php';

// Konversi waktu ke format datetime-local untuk input form
$waktu_mulai_form = date('Y-m-d\TH:i', strtotime($ujian_data['waktu_mulai']));
$waktu_selesai_form = date('Y-m-d\TH:i', strtotime($ujian_data['waktu_selesai']));
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Ujian</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="ujian.php">Manajemen Ujian</a></li>
        <li class="breadcrumb-item active">Edit Ujian</li>
    </ol>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Gagal!</strong> <?php echo isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'Terjadi kesalahan.'; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            Formulir Edit Ujian
        </div>
        <div class="card-body">
            <form action="proses_ujian_edit.php" method="POST">
                <input type="hidden" name="id_ujian" value="<?php echo $id_ujian; ?>">
                
                <div class="mb-3">
                    <label for="id_mengajar" class="form-label">Mapel / Kelas / Tahun Ajaran (Tidak bisa diubah)</label>
                    <select class="form-select" id="id_mengajar" name="id_mengajar_display" required disabled>
                        <option value="">-- Memuat... --</option>
                        <?php
                        $query_mengajar_detail = "SELECT mp.nama_mapel, k.nama_kelas, ta.tahun_ajaran, ta.semester
                                                  FROM mengajar m
                                                  JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                                  JOIN kelas k ON m.id_kelas = k.id_kelas
                                                  JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran
                                                  WHERE m.id_mengajar = ?";
                        $stmt_mengajar = mysqli_prepare($koneksi, $query_mengajar_detail);
                        mysqli_stmt_bind_param($stmt_mengajar, "i", $ujian_data['id_mengajar']);
                        mysqli_stmt_execute($stmt_mengajar);
                        $result_mengajar = mysqli_stmt_get_result($stmt_mengajar);
                        if ($row_mengajar = mysqli_fetch_assoc($result_mengajar)) {
                            echo "<option value='{$ujian_data['id_mengajar']}' selected>" . 
                                 htmlspecialchars($row_mengajar['nama_mapel'] . " - " . $row_mengajar['nama_kelas'] . " (" . $row_mengajar['tahun_ajaran'] . "/" . $row_mengajar['semester'] . ")") . 
                                 "</option>";
                        }
                        mysqli_stmt_close($stmt_mengajar);
                        ?>
                    </select>
                    <input type="hidden" name="id_mengajar" value="<?php echo $ujian_data['id_mengajar']; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="judul_ujian" class="form-label">Judul Ujian</label>
                    <input type="text" class="form-control" id="judul_ujian" name="judul_ujian" value="<?php echo htmlspecialchars($ujian_data['judul_ujian']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="durasi_menit" class="form-label">Durasi (dalam Menit)</label>
                    <input type="number" class="form-control" id="durasi_menit" name="durasi_menit" value="<?php echo $ujian_data['durasi_menit']; ?>" min="1" required>
                </div>

                <div class="mb-3">
                    <label for="waktu_mulai" class="form-label">Waktu Mulai Ujian</label>
                    <input type="datetime-local" class="form-control" id="waktu_mulai" name="waktu_mulai" value="<?php echo $waktu_mulai_form; ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="waktu_selesai" class="form-label">Waktu Selesai Ujian</label>
                    <input type="datetime-local" class="form-control" id="waktu_selesai" name="waktu_selesai" value="<?php echo $waktu_selesai_form; ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                <a href="ujian_detail.php?id=<?php echo $id_ujian; ?>" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<?php
mysqli_stmt_close($stmt_ujian);
// Panggil file footer.php
require_once 'includes/footer.php';
?>