<?php
// 1. Panggil file-file yang dibutuhkan
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

// 2. Atur judul halaman
$judul_halaman = "Data Kelas";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Data Kelas</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Data Kelas</li>
    </ol>

    <?php
    // Tampilkan pesan status jika ada dari proses CRUD
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $pesan = '';
        $tipe_alert = 'success';

        if ($status == 'sukses_tambah') {
            $pesan = 'Data kelas baru telah ditambahkan.';
        } elseif ($status == 'sukses_edit') {
            $pesan = 'Data kelas telah diperbarui.';
        } elseif ($status == 'sukses_hapus') {
            $pesan = 'Data kelas telah dihapus.';
        } else {
            $pesan = 'Terjadi kesalahan pada proses data.';
            $tipe_alert = 'danger';
        }

        echo '<div class="alert alert-' . $tipe_alert . ' alert-dismissible fade show" role="alert">'
             . $pesan .
             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
    ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-door-open me-1"></i>
            Daftar Kelas
            <a href="tambah_kelas.php" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-plus"></i> Tambah Kelas
            </a>
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Jurusan</th>
                        <th>Wali Kelas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query untuk mengambil data kelas dengan JOIN ganda ke tabel jurusan dan guru
                    $query = "SELECT 
                                kelas.*, 
                                jurusan.nama_jurusan, 
                                guru.nama_lengkap AS nama_wali_kelas 
                              FROM kelas 
                              JOIN jurusan ON kelas.id_jurusan = jurusan.id_jurusan 
                              JOIN guru ON kelas.id_guru_wali_kelas = guru.id_guru 
                              ORDER BY kelas.tingkat, kelas.nama_kelas ASC";
                    
                    $result = mysqli_query($koneksi, $query);
                    $nomor = 1;

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . $nomor++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_kelas']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['tingkat']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_jurusan']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_wali_kelas']) . "</td>";
                            echo "<td>";
                            // =============================================
                            // BAGIAN YANG DIPERBARUI ADA DI DUA BARIS INI
                            // =============================================
                            echo "<a href='edit_kelas.php?id=" . $row['id_kelas'] . "' class='btn btn-warning btn-sm me-2' title='Edit'><i class='fas fa-pencil-alt'></i></a>";
                            echo "<a href='hapus_kelas.php?id=" . $row['id_kelas'] . "' class='btn btn-danger btn-sm' title='Hapus' onclick=\"return confirm('Yakin ingin menghapus data kelas ini? Menghapus kelas akan berpengaruh pada data siswa.');\"><i class='fas fa-trash'></i></a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>Tidak ada data kelas.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>