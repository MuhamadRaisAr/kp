<?php
// 1. Panggil file auth_check.php
require_once '../../includes/auth_check.php';

// 2. Atur judul halaman
$judul_halaman = "Data Guru";

// 3. Panggil file header.php dan koneksi.php
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Data Guru</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Data Guru</li>
    </ol>

    <?php
    // Tampilkan pesan status jika ada
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $pesan = '';
        $tipe_alert = 'success';

        if ($status == 'sukses_tambah') {
            $pesan = 'Data guru baru telah ditambahkan.';
        } elseif ($status == 'sukses_edit') {
            $pesan = 'Data guru telah diperbarui.';
        } elseif ($status == 'sukses_hapus') {
            $pesan = 'Data guru telah dihapus.';
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
            Daftar Guru
            <a href="tambah_guru.php" class="btn btn-primary btn-sm float-end">
                <i class="fas fa-plus"></i> Tambah Guru
            </a>
        </div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIP</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>No. Telepon</th>
                        <th>Akun Login</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Query untuk mengambil data guru dengan LEFT JOIN ke tabel users
                    $query = "SELECT guru.*, users.username 
                              FROM guru 
                              LEFT JOIN users ON guru.id_user = users.id_user 
                              ORDER BY guru.nama_lengkap ASC";
                    
                    $result = mysqli_query($koneksi, $query);
                    $nomor = 1;

                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . $nomor++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['nip']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['no_telepon']) . "</td>";
                            echo "<td>";
                            // Cek apakah guru punya akun login
                            if ($row['username']) {
                                echo '<span class="badge bg-success">' . htmlspecialchars($row['username']) . '</span>';
                            } else {
                                echo '<span class="badge bg-secondary">Belum Ada</span>';
                            }
                            echo "</td>";
                            echo "<td>";
                            // ======================================================
                            // BAGIAN YANG DIPERBAIKI ADA DI DUA BARIS DI BAWAH INI
                            // ======================================================
                            echo "<a href='edit_guru.php?id=" . $row['id_guru'] . "' class='btn btn-warning btn-sm me-2' title='Edit'><i class='fas fa-pencil-alt'></i></a>";
                            echo "<a href='hapus_guru.php?id=" . $row['id_guru'] . "' class='btn btn-danger btn-sm' title='Hapus' onclick=\"return confirm('Apakah Anda yakin ingin menghapus data guru ini?');\"><i class='fas fa-trash'></i></a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>Tidak ada data guru.</td></tr>";
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
