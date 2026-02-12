<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Daftar Kelas";
$id_tahun = isset($_GET['id_tahun']) ? (int)$_GET['id_tahun'] : 0;
// Ambil info tahun ajaran
$stmt = mysqli_prepare($koneksi, "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = ?");
mysqli_stmt_bind_param($stmt, "i", $id_tahun);
mysqli_stmt_execute($stmt);
$result_tahun = mysqli_stmt_get_result($stmt);
$tahun_row = mysqli_fetch_assoc($result_tahun);
$nama_tahun = ($tahun_row) ? htmlspecialchars($tahun_row['tahun_ajaran']) . ' - ' . htmlspecialchars($tahun_row['semester']) : 'Tidak Diketahui';
?>
<div class="container-fluid px-4">
    <h1 class="mt-4">Daftar Kelas <?php echo $nama_tahun; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="siswa.php">Siswa</a></li>
        <li class="breadcrumb-item active">Daftar Kelas</li>
    </ol>
    <div class="row">
        <?php
        $query_kelas = "SELECT DISTINCT k.*, j.nama_jurusan 
                        FROM kelas k 
                        LEFT JOIN jurusan j ON k.id_jurusan = j.id_jurusan
                        WHERE k.id_tahun_ajaran = {$id_tahun}
                        ORDER BY k.tingkat ASC, k.nama_kelas ASC";
        $result_kelas = mysqli_query($koneksi, $query_kelas);

        if (mysqli_num_rows($result_kelas) > 0) {
            while ($row = mysqli_fetch_assoc($result_kelas)) {
                ?>
                <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                    <div class="card shadow-sm h-100 border-start-lg border-start-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="fas fa-chalkboard-teacher text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-0 text-primary"><?php echo htmlspecialchars($row['nama_kelas']); ?></h5>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['nama_jurusan']); ?></small>
                                </div>
                            </div>
                            <hr class="my-2">
                             <div class="d-grid mt-3">
                                <a href="siswa_list.php?kelas=<?php echo $row['id_kelas']; ?>&tahun=<?php echo $id_tahun; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                    <i class="fas fa-users me-2"></i> Lihat Daftar Siswa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-warning">Belum ada kelas untuk tahun ajaran ini.</div></div>';
        }
        ?>
    </div>
</div>
<?php
require_once '../../includes/footer.php';
?>
