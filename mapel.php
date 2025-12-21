<?php
require_once 'includes/auth_check.php';
if ($_SESSION['role'] != 'admin') { die("Akses ditolak."); }
require_once 'includes/header.php';
require_once 'includes/koneksi.php';
$judul_halaman = "Data Mata Pelajaran";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Data Mata Pelajaran</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Data Mapel</li>
    </ol>

    <?php
    // Tampilkan notifikasi
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $pesan = '';
        $tipe_alert = 'success';
        if ($status == 'sukses_tambah') $pesan = 'Data mapel baru telah ditambahkan.';
        elseif ($status == 'sukses_edit') $pesan = 'Data mapel telah diperbarui.';
        elseif ($status == 'sukses_hapus') $pesan = 'Data mapel telah dihapus.';
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
            Daftar Mata Pelajaran
            <a href="tambah_mapel.php" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-plus"></i> Tambah Mapel
            </a>
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Mapel</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Jenis</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM mata_pelajaran ORDER BY nama_mapel ASC";
                    $result = mysqli_query($koneksi, $query);
                    $nomor = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $nomor++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['kode_mapel']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama_mapel']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['jenis']) . "</td>";
                        echo "<td>";
                        echo "<a href='edit_mapel.php?id=" . $row['id_mapel'] . "' class='btn btn-warning btn-sm me-2' title='Edit'><i class='fas fa-pencil-alt'></i></a>";
                        echo "<a href='hapus_mapel.php?id=" . $row['id_mapel'] . "' class='btn btn-danger btn-sm' title='Hapus' onclick=\"return confirm('Yakin ingin menghapus mata pelajaran ini?');\"><i class='fas fa-trash'></i></a>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>