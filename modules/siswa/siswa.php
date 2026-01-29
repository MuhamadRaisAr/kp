<?php
// 1. Panggil file auth_check.php untuk memastikan hanya user yang sudah login yang bisa mengakses halaman ini
require_once '../../includes/auth_check.php';

// 2. Atur judul halaman
$judul_halaman = "Data Siswa";

// 3. Panggil file header.php
require_once '../../includes/header.php';

// 4. Panggil file koneksi.php untuk menghubungkan ke database
require_once '../../includes/koneksi.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Data Siswa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Data Siswa</li>
    </ol>

    <?php
    // =================================================================
    // BLOK PHP UNTUK MENAMPILKAN NOTIFIKASI SETELAH AKSI
    // =================================================================
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $pesan = '';
        $tipe_alert = 'success';

        switch ($status) {
            case 'sukses_tambah':
                $pesan = '<strong>Berhasil!</strong> Data siswa baru telah ditambahkan.';
                break;
            case 'sukses_edit':
                $pesan = '<strong>Berhasil!</strong> Data siswa telah diperbarui.';
                break;
            case 'sukses_hapus':
                $pesan = '<strong>Berhasil!</strong> Data siswa telah dihapus.';
                break;
            default:
                $pesan = '<strong>Gagal!</strong> Terjadi kesalahan pada proses data.';
                $tipe_alert = 'danger';
        }

        echo '<div class="alert alert-' . $tipe_alert . ' alert-dismissible fade show" role="alert">'
            . $pesan .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
    ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Daftar Siswa
            </div>
            <a href="tambah_siswa.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah Siswa
            </a>
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered table-hover">
                <thead class="table-dark">
                    <tr class="text-center">
                        <th>No</th>
                        <th>NIS</th>
                        <th>NISN</th>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th>Jenis Kelamin</th>
                        <th width="170px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query untuk mengambil data siswa beserta nama kelasnya menggunakan JOIN
                    $query = "SELECT siswa.*, kelas.nama_kelas 
                              FROM siswa 
                              JOIN kelas ON siswa.id_kelas = kelas.id_kelas 
                              ORDER BY siswa.nama_lengkap ASC";
                    
                    $result = mysqli_query($koneksi, $query);
                    $nomor = 1;

                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                            <tr>
                                <td class="text-center"><?= $nomor++; ?></td>
                                <td><?= htmlspecialchars($row['nis']); ?></td>
                                <td><?= htmlspecialchars($row['nisn']); ?></td>
                                <td><?= htmlspecialchars($row['nama_lengkap']); ?></td>
                                <td><?= htmlspecialchars($row['nama_kelas']); ?></td>
                                <td><?= htmlspecialchars($row['jenis_kelamin']); ?></td>
                                <td class="text-center">
                                    <a href="edit_siswa.php?id=<?= $row['id_siswa']; ?>" 
                                       class="btn btn-warning btn-sm me-2" title="Edit">
                                       <i class="fas fa-pencil-alt"></i> Edit
                                    </a>
                                    <a href="hapus_siswa.php?id=<?= $row['id_siswa']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?');"
                                       title="Hapus">
                                       <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>Tidak ada data siswa.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once '../../includes/footer.php';
?>
