<?php
require_once '../../includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';
$judul_halaman = "Data Tahun Ajaran";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Data Tahun Ajaran</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Tahun Ajaran</li>
    </ol>

    <?php
    // Tampilkan notifikasi
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $pesan = '';
        $tipe_alert = 'success';
        if ($status == 'sukses_tambah') $pesan = 'Tahun ajaran baru telah ditambahkan.';
        elseif ($status == 'sukses_edit') $pesan = 'Tahun ajaran telah diperbarui.';
        elseif ($status == 'sukses_hapus') $pesan = 'Tahun ajaran telah dihapus.';
        else { $pesan = 'Terjadi kesalahan.'; $tipe_alert = 'danger'; }

        echo '<div class="alert alert-' . $tipe_alert . ' alert-dismissible fade show" role="alert">'
             . $pesan .
             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
    ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Daftar Tahun Ajaran
            <a href="tambah_tahun_ajaran.php" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-plus"></i> Tambah Tahun Ajaran
            </a>
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tahun Ajaran</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC, semester DESC";
                    $result = mysqli_query($koneksi, $query);
                    $nomor = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $nomor++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['tahun_ajaran']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
                        echo "<td>" . ($row['status_aktif'] == 'Aktif' ? "<span class='badge bg-success'>Aktif</span>" : "<span class='badge bg-secondary'>Tidak Aktif</span>") . "</td>";
                        echo "<td>";
                        echo "<a href='edit_tahun_ajaran.php?id=" . $row['id_tahun_ajaran'] . "' class='btn btn-warning btn-sm me-2' title='Edit'><i class='fas fa-pencil-alt'></i></a>";
                        echo "<a href='hapus_tahun_ajaran.php?id=" . $row['id_tahun_ajaran'] . "' class='btn btn-danger btn-sm' title='Hapus' onclick=\"return confirm('Yakin ingin menghapus tahun ajaran ini?');\"><i class='fas fa-trash'></i></a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
