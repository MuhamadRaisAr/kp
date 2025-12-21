<?php
// ==========================================================
// 1. LOGIKA UTAMA (Auth, Koneksi, dan Penentuan Judul)
//    BAGIAN INI HARUS BEBAS DARI OUTPUT APAPUN!
// ==========================================================
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Manajemen Pengumuman";

// ==========================================================
// 2. CEK AKSES ADMIN & JALUR KELUAR AWAL
// ==========================================================
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Jika akses ditolak, kita tampilkan halaman error, lalu keluar.
    require_once 'includes/header.php';
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Akses ditolak. Halaman ini hanya untuk Admin.</div></div>';
    require_once 'includes/footer.php';
    mysqli_close($koneksi);
    exit();
}

$id_user_login = (int)$_SESSION['id_user'];

// ==========================================================
// 3. PROSES LOGIKA HAPUS (Wajib di atas Panggilan Header)
//    Menggunakan header("Location: ...") harus di sini!
// ==========================================================
if (isset($_GET['aksi']) && $_GET['aksi'] == 'hapus' && isset($_GET['id'])) {
    $id_hapus = (int)$_GET['id'];
    
    if ($id_hapus > 0) {
        // Soft delete (ubah is_aktif jadi 0)
        $stmt_hapus = mysqli_prepare($koneksi, "UPDATE pengumuman SET is_aktif = 0 WHERE id_pengumuman = ?");
        mysqli_stmt_bind_param($stmt_hapus, "i", $id_hapus);
        
        if (mysqli_stmt_execute($stmt_hapus)) {
            // Redirect sukses
            header("Location: pengumuman.php?status=sukses_hapus");
            exit(); 
        } else {
            // Redirect gagal
            header("Location: pengumuman.php?status=gagal_hapus");
            exit(); 
        }
        mysqli_stmt_close($stmt_hapus);
    } else {
        // Redirect jika ID tidak valid
        header("Location: pengumuman.php?status=gagal_hapus_invalid");
        exit();
    }
}

// ==========================================================
// 4. PANGGIL HEADER (Output HTML Dimulai)
// ==========================================================
require_once 'includes/header.php'; 
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manajemen Pengumuman</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Pengumuman</li>
    </ol>

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'sukses_tambah' || $_GET['status'] == 'sukses_edit' || $_GET['status'] == 'sukses_hapus'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Berhasil!</strong> 
                <?php
                if ($_GET['status'] == 'sukses_tambah') echo 'Pengumuman baru telah ditambahkan.';
                elseif ($_GET['status'] == 'sukses_edit') echo 'Pengumuman telah diperbarui.';
                elseif ($_GET['status'] == 'sukses_hapus') echo 'Pengumuman telah disembunyikan (dinonaktifkan).';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif (str_starts_with($_GET['status'], 'gagal_')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Gagal!</strong> Terjadi kesalahan. Silakan coba lagi.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="mb-3">
        <a href="pengumuman_tambah.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Pengumuman Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-bullhorn me-1"></i>
            Daftar Pengumuman (Yang Aktif)
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
                        // Query untuk mengambil pengumuman yang aktif
                        $query_pengumuman = "SELECT p.id_pengumuman, p.judul, p.tanggal_posting, p.target_role, u.username AS nama_pembuat
                                             FROM pengumuman p
                                             LEFT JOIN users u ON p.id_user_pembuat = u.id_user
                                             WHERE p.is_aktif = 1
                                             ORDER BY p.tanggal_posting DESC";

                        $result = mysqli_query($koneksi, $query_pengumuman);

                        if (mysqli_num_rows($result) > 0): // Menggunakan sintaks titik dua
                            $nomor = 1;
                            while ($row = mysqli_fetch_assoc($result)): // Menggunakan sintaks titik dua
                        ?>
                        
                        <tr>
                            <td><?= $nomor++ ?></td>
                            <td><?= htmlspecialchars($row['judul']) ?></td>
                            <td><?= date('d M Y, H:i', strtotime($row['tanggal_posting'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_pembuat'] ?? 'N/A') ?></td>
                            <td><?= ucfirst($row['target_role']) ?></td>
                            <td>
                                <a href='pengumuman_edit.php?id=<?= $row['id_pengumuman'] ?>' class='btn btn-warning btn-sm me-1' title='Edit'><i class='fas fa-edit'></i></a>
                                <a href='pengumuman.php?aksi=hapus&id=<?= $row['id_pengumuman'] ?>' class='btn btn-danger btn-sm' title='Sembunyikan' onclick='return confirm("Yakin ingin menyembunyikan pengumuman ini?");'><i class='fas fa-eye-slash'></i></a>
                            </td>
                        </tr>
                        
                        <?php 
                            endwhile; // Penutup while
                        else: 
                        ?>
                        
                        <tr>
                            <td colspan='6' class='text-center'>Belum ada pengumuman aktif.</td>
                        </tr>
                        
                        <?php 
                        endif; // Penutup if
                        ?>
                    </tbody>
                </table>
            </div>
            <small class="text-muted">*Menyembunyikan pengumuman tidak menghapusnya secara permanen.</small>
        </div>
    </div>
</div>

<?php
mysqli_close($koneksi); // Tutup koneksi sebelum footer
// Panggil file footer.php
require_once 'includes/footer.php';
?>