<?php
// Panggil file-file yang dibutuhkan
require_once '../../includes/auth_check.php'; // Memastikan login & set role
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

// Judul halaman
$judul_halaman = "Ujian Selesai";

// Pastikan yang login adalah siswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak.</div></div>';
    require_once '../../includes/footer.php';
    exit();
}

// Ambil ID Ujian dari URL (opsional, untuk menampilkan judul)
$id_ujian = isset($_GET['id_ujian']) ? (int)$_GET['id_ujian'] : 0;
$judul_ujian_selesai = '';

if ($id_ujian > 0) {
    $stmt = mysqli_prepare($koneksi, "SELECT judul_ujian FROM ujian WHERE id_ujian = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_ujian);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $judul_ujian_selesai = $row['judul_ujian'];
    }
    mysqli_stmt_close($stmt);
}

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Ujian Telah Selesai</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="ujian_saya.php">Ujian</a></li>
        <li class="breadcrumb-item active">Selesai</li>
    </ol>

    <div class="card mb-4">
        <div class="card-body text-center">
            <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
            <h3 class="card-title">Terima Kasih!</h3>
            <?php if (!empty($judul_ujian_selesai)): ?>
                <p class="lead">Anda telah menyelesaikan ujian: <strong><?php echo htmlspecialchars($judul_ujian_selesai); ?></strong>.</p>
            <?php else: ?>
                <p class="lead">Anda telah menyelesaikan ujian.</p>
            <?php endif; ?>
            <p>Jawaban Anda telah berhasil disimpan.</p>
            <p>Nilai akan segera tersedia setelah diperiksa oleh guru atau sesuai jadwal yang ditentukan.</p>
            <a href="ujian_saya.php" class="btn btn-primary mt-3">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Ujian
            </a>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once '../../includes/footer.php';
?>
