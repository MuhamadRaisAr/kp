<?php
// 1. Panggil file-file dasar
require_once '../../includes/auth_check.php'; // Cek login
require_once '../../includes/header.php';    // PENTING: Muat layout (header, sidebar, navbar) DULU
require_once '../../includes/koneksi.php';

// 2. Atur judul halaman
$judul_halaman = "Data Jurusan";

// =================================================================
// BLOK KEAMANAN (SETELAH HEADER DIMUAT)
// =================================================================
$role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : ''; 

if ($role != 'admin') {
    // Jika bukan admin, tampilkan pesan error DI DALAM layout
    echo '<div class="container-fluid px-4">';
    echo '  <h1 class="mt-4">Akses Ditolak</h1>';
    echo '  <ol class="breadcrumb mb-4">';
    echo '      <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>';
    echo '      <li class="breadcrumb-item active">Akses Ditolak</li>';
    echo '  </ol>';
    echo '  <div class="alert alert-danger">Maaf, halaman ini hanya bisa diakses oleh Admin.</div>';
    echo '</div>';
    
} else {
    // =================================================================
    // JIKA LOLOS (adalah admin), TAMPILKAN KONTEN HALAMAN
    // =================================================================
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Data Jurusan</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Data Jurusan</li>
    </ol>

    <?php
    // Tampilkan pesan status jika ada dari proses CRUD
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $pesan = '';
        $tipe_alert = 'success';

        if ($status == 'sukses_tambah') {
            $pesan = 'Data jurusan baru telah ditambahkan.';
        } elseif ($status == 'sukses_edit') {
            $pesan = 'Data jurusan telah diperbarui.';
        } elseif ($status == 'sukses_hapus') {
            $pesan = 'Data jurusan telah dihapus.';
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
            <i class="fas fa-table me-1"></i>
            Daftar Jurusan
            <a href="tambah_jurusan.php" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-plus"></i> Tambah Jurusan
            </a>
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode Jurusan</th>
                        <th>Nama Jurusan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query untuk mengambil semua data jurusan
                    $query = "SELECT * FROM jurusan ORDER BY nama_jurusan ASC";
                    $result = mysqli_query($koneksi, $query);
                    $nomor = 1;

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . $nomor++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['kode_jurusan']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_jurusan']) . "</td>";
                            echo "<td>";
                            echo "<a href='edit_jurusan.php?id=" . $row['id_jurusan'] . "' class='btn btn-warning btn-sm me-2' title='Edit'><i class='fas fa-pencil-alt'></i></a>";
                            echo "<a href='hapus_jurusan.php?id=" . $row['id_jurusan'] . "' class='btn btn-danger btn-sm' title='Hapus' onclick=\"return confirm('Yakin ingin menghapus jurusan ini?');\"><i class='fas fa-trash'></i></a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>Tidak ada data jurusan.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
} // Penutup kurung "else" dari blok keamanan

// Panggil file footer.php
require_once '../../includes/footer.php';
?>
