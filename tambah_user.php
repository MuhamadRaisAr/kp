<?php
require_once 'includes/auth_check.php';
require_once 'includes/koneksi.php';

if ($_SESSION['role'] != 'admin') {
    die("Akses ditolak.");
}

$judul_halaman = "Tambah User Baru";
$error_msg = "";
$success_msg = "";

// --- LOGIKA PENYIMPANAN DATA (Ditaruh di atas sebelum output HTML) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? '';
    $username = '';
    $email = '';
    $password = $_POST['password'] ?? '';
    $id_guru = null;
    $id_siswa = null;
    $is_valid = true;

    if (empty($password)) {
        $error_msg = "Password wajib diisi.";
        $is_valid = false;
    }

    if ($is_valid) {
        if ($role === 'guru') {
            $id_guru = !empty($_POST['id_guru']) ? $_POST['id_guru'] : null;
            if (empty($id_guru)) {
                $error_msg = "Silakan pilih Guru.";
                $is_valid = false;
            } else {
                // Ambil NIP sebagai username
                $q = mysqli_query($koneksi, "SELECT nip, email FROM guru WHERE id_guru = '$id_guru'");
                $d = mysqli_fetch_assoc($q);
                $username = $d['nip'];
                $email = !empty($d['email']) ? $d['email'] : $username.'@sekolah.id';
            }
        } elseif ($role === 'siswa') {
            $id_siswa = !empty($_POST['id_siswa']) ? $_POST['id_siswa'] : null;
            if (empty($id_siswa)) {
                $error_msg = "Silakan pilih Siswa.";
                $is_valid = false;
            } else {
                // Ambil NIS sebagai username (Siswa TIDAK punya kolom email di DB)
                $q = mysqli_query($koneksi, "SELECT nis FROM siswa WHERE id_siswa = '$id_siswa'");
                $d = mysqli_fetch_assoc($q);
                $username = $d['nis'];
                // Generate dummy email jika tidak ada kolom email
                $email = $username . '@sekolah.id';
            }
        } elseif ($role === 'admin') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            if (empty($username) || empty($email)) {
                $error_msg = "Username dan Email wajib diisi.";
                $is_valid = false;
            }
        } else {
            $error_msg = "Role tidak valid.";
            $is_valid = false;
        }
    }

    // Eksekusi Insert
    if ($is_valid) {
        // Cek duplikat
        $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$username'");
        if (mysqli_num_rows($cek) > 0) {
            $error_msg = "Username '$username' sudah terdaftar. Silakan gunakan user lain atau hubungi teknisi.";
        } else {
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // FIX: Gunakan bind_param 's' untuk semua parameter agar NULL tetap NULL
            $stmt = mysqli_prepare($koneksi, "INSERT INTO users (username, password, email, role, status, id_guru, id_siswa) VALUES (?, ?, ?, ?, 'aktif', ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssssss", $username, $pass_hash, $email, $role, $id_guru, $id_siswa);
            
            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($koneksi);
                
                // Update FK di tabel Siswa/Guru (Relasi dua arah)
                if ($role === 'guru') {
                    mysqli_query($koneksi, "UPDATE guru SET id_user = $new_id WHERE id_guru = $id_guru");
                } elseif ($role === 'siswa') {
                    mysqli_query($koneksi, "UPDATE siswa SET id_user = $new_id WHERE id_siswa = $id_siswa");
                }
                
                // REDIRECT KE HALAMAN UTAMA SETELAH SUKSES
                header("Location: users.php?status=sukses_tambah");
                exit();
                
            } else {
                $error_msg = "Gagal simpan database: " . mysqli_error($koneksi);
            }
        }
    }
}

// Baru panggil header setelah logika selesai (supaya header() redirect bisa jalan)
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Tambah User</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="users.php">Manajemen User</a></li>
        <li class="breadcrumb-item active">Tambah Baru</li>
    </ol>

    <div class="card mb-4" style="max-width: 800px">
        <div class="card-header">
            <i class="fas fa-user-plus me-1"></i> Form User Baru
        </div>
        <div class="card-body">
            
            <!-- Tampilkan Error Jika Ada -->
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Gagal!</strong> <?= $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                
                <!-- 1. PILIH ROLE (TRIGGER JS DI FOOTER) -->
                <div class="mb-3">
                    <label for="role" class="form-label fw-bold">Pilih Tipe User / Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="siswa">Siswa</option>
                        <option value="guru">Guru</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <!-- 2. PILIHAN SISWA (Hidden by Default via JS) -->
                <div class="mb-3" id="pilihan_siswa" style="display:none;">
                    <label for="id_siswa" class="form-label">Pilih Data Siswa</label>
                    <select class="form-select" id="id_siswa" name="id_siswa">
                        <option value="">-- Cari Siswa --</option>
                        <?php
                        // Ambil siswa yang belum punya user ATAU user-nya sudah dihapus (orphaned)
                        $qs = mysqli_query($koneksi, "SELECT s.id_siswa, s.nis, s.nama_lengkap 
                                                      FROM siswa s 
                                                      LEFT JOIN users u ON s.id_user = u.id_user 
                                                      WHERE s.id_user IS NULL OR u.id_user IS NULL 
                                                      ORDER BY s.nama_lengkap ASC");
                        while($s = mysqli_fetch_assoc($qs)){
                            echo "<option value='{$s['id_siswa']}'>{$s['nama_lengkap']} (NIS: {$s['nis']})</option>";
                        }
                        ?>
                    </select>
                    <div class="form-text">Username akan otomatis menggunakan NIS.</div>
                </div>

                <!-- 3. PILIHAN GURU (Hidden by Default via JS) -->
                <div class="mb-3" id="pilihan_guru" style="display:none;">
                    <label for="id_guru" class="form-label">Pilih Data Guru</label>
                    <select class="form-select" id="id_guru" name="id_guru">
                        <option value="">-- Cari Guru --</option>
                        <?php
                        // Ambil guru yang belum punya user ATAU user-nya sudah dihapus (orphaned)
                        $qg = mysqli_query($koneksi, "SELECT g.id_guru, g.nip, g.nama_lengkap 
                                                      FROM guru g 
                                                      LEFT JOIN users u ON g.id_user = u.id_user 
                                                      WHERE g.id_user IS NULL OR u.id_user IS NULL 
                                                      ORDER BY g.nama_lengkap ASC");
                        while($g = mysqli_fetch_assoc($qg)){
                            echo "<option value='{$g['id_guru']}'>{$g['nama_lengkap']} (NIP: {$g['nip']})</option>";
                        }
                        ?>
                    </select>
                    <div class="form-text">Username akan otomatis menggunakan NIP.</div>
                </div>

                <!-- 4. INPUT MANUAL ADMIN (Hidden by Default via JS) -->
                <div id="input_manual" style="display:none;">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Buat username baru">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="contoh@domain.com">
                    </div>
                </div>

                <!-- 5. PASSWORD (Hidden by Default via JS) -->
                <div class="mb-3" id="input_password" style="display:none;">
                    <label for="password" class="form-label fw-bold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter">
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan User</button>
                    <a href="users.php" class="btn btn-secondary ms-2">Batal</a>
                </div>

            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
