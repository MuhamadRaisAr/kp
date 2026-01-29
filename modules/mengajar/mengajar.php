<?php
require_once '../../includes/auth_check.php';
// Atur hak akses: Admin bisa akses, Guru juga bisa (untuk melihat)
if ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'guru') { 
    die("Akses ditolak."); 
}

require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Penugasan Mengajar";

// Ambil role dan id_guru dari session
$role = $_SESSION['role'];
$id_guru_login = isset($_SESSION['id_guru']) ? $_SESSION['id_guru'] : null;

// Jika yang login adalah guru tapi tidak punya id_guru, hentikan.
if ($role == 'guru' && !$id_guru_login) {
    die("Akses ditolak. Akun guru Anda tidak terhubung dengan profil guru.");
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $judul_halaman; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $judul_halaman; ?></li>
    </ol>

    <?php
    // Notifikasi yang lebih detail
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $pesan = '';
        $tipe_alert = 'success';

        switch ($status) {
            case 'sukses_tambah':
                $pesan = 'Penugasan baru berhasil ditambahkan.';
                break;
            case 'sukses_edit':
                $pesan = 'Penugasan berhasil diperbarui.';
                break;
            case 'sukses_hapus':
                $pesan = 'Penugasan berhasil dihapus.';
                break;
            case 'duplikat':
                $pesan = '<strong>Gagal!</strong> Jadwal bentrok atau duplikat untuk guru pada hari dan jam yang sama.';
                $tipe_alert = 'danger';
                break;
            case 'jam_invalid':
                $pesan = '<strong>Gagal!</strong> Jam mulai tidak boleh lebih besar atau sama dengan jam selesai.';
                $tipe_alert = 'danger';
                break;
            case 'field_kosong':
                $pesan = '<strong>Gagal!</strong> Semua field wajib diisi.';
                $tipe_alert = 'danger';
                break;
            case 'gagal_tambah':
                $pesan = '<strong>Gagal!</strong> Terjadi kesalahan saat menyimpan ke database.';
                // Menampilkan detail error dari MySQL jika ada
                if (isset($_GET['error'])) {
                    $pesan .= '<br><small>Pesan Database: ' . htmlspecialchars(urldecode($_GET['error'])) . '</small>';
                }
                $tipe_alert = 'danger';
                break;
            default:
                $pesan = '<strong>Gagal!</strong> Terjadi kesalahan tidak dikenal.';
                $tipe_alert = 'danger';
                break;
        }

        echo '<div class="alert alert-' . $tipe_alert . ' alert-dismissible fade show" role="alert">'
             . $pesan .
             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-table me-1"></i> Daftar Penugasan</span>
            <?php if ($role == 'admin') : // Tombol tambah hanya untuk admin ?>
            <a href="tambah_mengajar.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah Penugasan
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Guru</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas</th>
                        <th>Tahun Ajaran / Semester</th>
                        <th>Hari</th>
                        <th>Waktu</th>
                        <?php if ($role == 'admin') echo '<th>Aksi</th>'; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT m.id_mengajar, g.nama_lengkap, mp.nama_mapel, k.nama_kelas, ta.tahun_ajaran, ta.semester, m.hari, m.jam_mulai, m.jam_selesai
                              FROM mengajar m
                              JOIN guru g ON m.id_guru = g.id_guru
                              JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                              JOIN kelas k ON m.id_kelas = k.id_kelas
                              JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran";
                    
                    if ($role == 'guru') {
                        $query .= " WHERE m.id_guru = " . $id_guru_login;
                    }
                    $query .= " ORDER BY ta.tahun_ajaran DESC, m.hari, m.jam_mulai, g.nama_lengkap";
                    
                    $result = mysqli_query($koneksi, $query);
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $no++ . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama_lengkap']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama_mapel']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nama_kelas']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['tahun_ajaran']) . " (" . htmlspecialchars($row['semester']) . ")</td>";
                        echo "<td>" . htmlspecialchars($row['hari']) . "</td>";
                        echo "<td>" . htmlspecialchars(date('H:i', strtotime($row['jam_mulai']))) . " - " . htmlspecialchars(date('H:i', strtotime($row['jam_selesai']))) . "</td>";
                        
                        if ($role == 'admin') {
                            echo "<td class='text-center'>";
                            echo "<a href='edit_mengajar.php?id=" . $row['id_mengajar'] . "' class='btn btn-warning btn-sm me-2' title='Edit'><i class='fas fa-pencil-alt'></i></a>";
                            echo "<a href='hapus_mengajar.php?id=" . $row['id_mengajar'] . "' class='btn btn-danger btn-sm' title='Hapus' onclick=\"return confirm('Yakin ingin menghapus jadwal ini?');\"><i class='fas fa-trash'></i></a>";
                            echo "</td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
