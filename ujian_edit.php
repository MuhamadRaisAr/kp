<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

if (!isset($_SESSION['id_guru'])) {
    echo "<div class='alert alert-danger'>Akses ditolak</div>";
    require_once 'includes/footer.php';
    exit();
}

$id_guru = (int)$_SESSION['id_guru'];
$id_ujian = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode_buka_ulang = isset($_GET['mode']) && $_GET['mode'] === 'buka_ulang';

// ambil data ujian
$sql = "SELECT u.*, mp.nama_mapel, k.nama_kelas
        FROM ujian u
        JOIN mengajar m ON u.id_mengajar = m.id_mengajar
        JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
        JOIN kelas k ON m.id_kelas = k.id_kelas
        WHERE u.id_ujian = ? AND m.id_guru = ?
        LIMIT 1";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ii", $id_ujian, $id_guru);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) !== 1) {
    echo "<div class='alert alert-danger'>Ujian tidak ditemukan</div>";
    require_once 'includes/footer.php';
    exit();
}

$ujian = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <?= $mode_buka_ulang ? 'Buka Ulang Ujian' : 'Edit Ujian' ?>
    </h1>

    <div class="card mb-4">
        <div class="card-body">
            <form action="proses_ujian_edit.php" method="POST">
                <input type="hidden" name="id_ujian" value="<?= $ujian['id_ujian'] ?>">
                <input type="hidden" name="mode" value="<?= $mode_buka_ulang ? 'buka_ulang' : 'edit' ?>">

                <div class="mb-3">
                    <label class="form-label">Judul Ujian</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($ujian['judul_ujian']) ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mata Pelajaran</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($ujian['nama_mapel']) ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kelas</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($ujian['nama_kelas']) ?>" readonly>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Waktu Mulai</label>
                        <input type="datetime-local" name="waktu_mulai"
                               class="form-control"
                               value="<?= date('Y-m-d\TH:i', strtotime($ujian['waktu_mulai'])) ?>"
                               required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Waktu Selesai</label>
                        <input type="datetime-local" name="waktu_selesai"
                               class="form-control"
                               value="<?= date('Y-m-d\TH:i', strtotime($ujian['waktu_selesai'])) ?>"
                               required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan
                </button>

                <a href="ujian.php" class="btn btn-secondary">
                    Kembali
                </a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
