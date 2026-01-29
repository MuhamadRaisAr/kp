<?php
// ==========================================================
// 1. LOGIKA UTAMA (Auth, Koneksi, dan Penentuan Judul)
//    BAGIAN INI HARUS BEBAS DARI OUTPUT APAPUN!
// ==========================================================
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Manajemen Pengumuman";

// ==========================================================
// 2. CEK AKSES ADMIN & JALUR KELUAR AWAL
// ==========================================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Jika akses ditolak, kita tampilkan halaman error, lalu keluar.
    require_once '../../includes/header.php';
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk Admin.</div></div>';
    require_once '../../includes/footer.php';
    mysqli_close($koneksi);
    exit();
}

$id_user_login = (int)$_SESSION['id_user'];

// ==========================================================
// 3. PROSES LOGIKA HAPUS (Wajib di atas Panggilan Header)
//    Menggunakan header("Location: ...") harus di sini!
// ==========================================================
// ==========================================================
// 3. PROSES LOGIKA AKSI (Hapus Soft, Restore, Hapus Permanen)
// ==========================================================
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_target = (int)$_GET['id'];
    $aksi = $_GET['aksi'];
    
    if ($id_target > 0) {
        if ($aksi == 'hapus') {
            // Soft delete (Sembunyikan)
            $stmt = mysqli_prepare($koneksi, "UPDATE pengumuman SET is_aktif = 0 WHERE id_pengumuman = ?");
            mysqli_stmt_bind_param($stmt, "i", $id_target);
            $msg = mysqli_stmt_execute($stmt) ? "sukses_hapus" : "gagal_hapus";
            mysqli_stmt_close($stmt);
            header("Location: pengumuman.php?status=$msg");
            exit();

        } elseif ($aksi == 'restore') {
            // Restore (Tampilkan Kembali)
            $stmt = mysqli_prepare($koneksi, "UPDATE pengumuman SET is_aktif = 1 WHERE id_pengumuman = ?");
            mysqli_stmt_bind_param($stmt, "i", $id_target);
            $msg = mysqli_stmt_execute($stmt) ? "sukses_restore" : "gagal_restore";
            mysqli_stmt_close($stmt);
            header("Location: pengumuman.php?view=arsip&status=$msg");
            exit();

        } elseif ($aksi == 'hapus_permanen') {
            // Hard delete
            $stmt = mysqli_prepare($koneksi, "DELETE FROM pengumuman WHERE id_pengumuman = ?");
            mysqli_stmt_bind_param($stmt, "i", $id_target);
            $msg = mysqli_stmt_execute($stmt) ? "sukses_hapus_permanen" : "gagal_hapus_permanen";
            mysqli_stmt_close($stmt);
            header("Location: pengumuman.php?view=arsip&status=$msg");
            exit();
        }
    }
}

// ==========================================================
// 4. PANGGIL HEADER (Output HTML Dimulai)
// ==========================================================
require_once '../../includes/header.php'; 

// Tentukan View (Aktif atau Arsip)
$view_file = isset($_GET['view']) && $_GET['view'] == 'arsip' ? 'arsip' : 'aktif';
$is_aktif_val = ($view_file == 'aktif') ? 1 : 0;
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manajemen Pengumuman</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Pengumuman</li>
    </ol>

    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-<?php echo str_starts_with($_GET['status'], 'gagal') ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
            <strong>Info:</strong> 
            <?php
            switch($_GET['status']) {
                case 'sukses_tambah': echo 'Pengumuman baru telah ditambahkan.'; break;
                case 'sukses_edit': echo 'Pengumuman telah diperbarui.'; break;
                case 'sukses_hapus': echo 'Pengumuman telah disembunyikan ke Arsip.'; break;
                case 'sukses_restore': echo 'Pengumuman telah dikembalikan ke daftar Aktif.'; break;
                case 'sukses_hapus_permanen': echo 'Pengumuman telah dihapus secara permanen.'; break;
                default: echo 'Terjadi kesalahan atau aksi gagal.';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row align-items-center mb-3">
        <div class="col-md-6">
            <a href="pengumuman_tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Buat Pengumuman Baru
            </a>
        </div>
        <div class="col-md-6 text-end">
             <div class="btn-group" role="group">
                <a href="pengumuman.php" class="btn btn-<?php echo $view_file == 'aktif' ? 'secondary' : 'outline-secondary'; ?>">
                    <i class="fas fa-list me-1"></i> Aktif
                </a>
                <a href="pengumuman.php?view=arsip" class="btn btn-<?php echo $view_file == 'arsip' ? 'secondary' : 'outline-secondary'; ?>">
                    <i class="fas fa-archive me-1"></i> Arsip / Tersembunyi
                </a>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-<?php echo $view_file == 'aktif' ? 'bullhorn' : 'box-archive'; ?> me-1"></i>
            <?php echo $view_file == 'aktif' ? 'Daftar Pengumuman Aktif' : 'Daftar Pengumuman Tersembunyi (Arsip)'; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Judul</th>
                            <th>Tanggal Posting</th>
                            <th>Pembuat</th>
                            <th>Target</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query sesuai view
                        $query_pengumuman = "SELECT p.id_pengumuman, p.judul, p.tanggal_posting, p.target_role, u.username AS nama_pembuat
                                             FROM pengumuman p
                                             LEFT JOIN users u ON p.id_user_pembuat = u.id_user
                                             WHERE p.is_aktif = ?
                                             ORDER BY p.tanggal_posting DESC";

                        $stmt = mysqli_prepare($koneksi, $query_pengumuman);
                        mysqli_stmt_bind_param($stmt, "i", $is_aktif_val);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        if (mysqli_num_rows($result) > 0): 
                            $nomor = 1;
                            while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                        
                        <tr>
                            <td><?= $nomor++ ?></td>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td><?= date('d M Y, H:i', strtotime($row['tanggal_posting'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_pembuat'] ?? 'N/A') ?></td>
                            <td><?= ucfirst($row['target_role']) ?></td>
                            <td>
                                <?php if ($view_file == 'aktif'): ?>
                                    <a href='pengumuman_edit.php?id=<?= $row['id_pengumuman'] ?>' class='btn btn-warning btn-sm me-1' title='Edit'><i class='fas fa-edit'></i></a>
                                    <a href='pengumuman.php?aksi=hapus&id=<?= $row['id_pengumuman'] ?>' class='btn btn-danger btn-sm' title='Sembunyikan / Arsipkan' onclick='return confirm("Yakin ingin menyembunyikan pengumuman ini ke arsip?");'><i class='fas fa-archive'></i></a>
                                <?php else: ?>
                                    <a href='pengumuman.php?aksi=restore&id=<?= $row['id_pengumuman'] ?>' class='btn btn-success btn-sm me-1' title='Kembalikan ke Aktif' onclick='return confirm("Tampilkan kembali pengumuman ini?");'><i class='fas fa-trash-restore'></i></a>
                                    <a href='pengumuman.php?aksi=hapus_permanen&id=<?= $row['id_pengumuman'] ?>' class='btn btn-dark btn-sm' title='Hapus Permanen' onclick='return confirm("YAKIN HAPUS SELAMANYA?\nData tidak bisa dikembalikan!");'><i class='fas fa-trash'></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                        
                        <tr>
                            <td colspan='6' class='text-center'>Data tidak ditemukan di folder <?php echo ucfirst($view_file); ?>.</td>
                        </tr>
                        
                        <?php 
                        endif; 
                        mysqli_stmt_close($stmt);
                        ?>
                    </tbody>
                </table>
            </div>
            <?php if ($view_file == 'aktif'): ?>
                <small class="text-muted">*Gunakan tombol "Arsip" (merah) untuk menyembunyikan pengumuman sementara.</small>
            <?php else: ?>
                <small class="text-danger">*Hapus Permanen akan menghilangkan data selamanya.</small>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
mysqli_close($koneksi); // Tutup koneksi sebelum footer
// Panggil file footer.php
require_once '../../includes/footer.php';
?>
