<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/koneksi.php';
require_once 'includes/auth_check.php'; // Memastikan login & set role

// 1. Validasi Akses: Siswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa' || !isset($_SESSION['id_siswa']) || empty($_SESSION['id_siswa'])) {
    header("Location: login.php?error=Akses ditolak");
    exit();
}
$id_siswa_login = (int)$_SESSION['id_siswa'];

// 2. Validasi Metode: POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ujian_saya.php");
    exit();
}

// 3. Ambil ID Ujian dari Form
$id_ujian = isset($_POST['id_ujian']) ? (int)$_POST['id_ujian'] : 0;
if ($id_ujian <= 0) {
    header("Location: ujian_saya.php?error=Ujian tidak valid");
    exit();
}

// Waktu server saat ini
$waktu_sekarang_dt = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$waktu_sekarang_sql = $waktu_sekarang_dt->format('Y-m-d H:i:s');

// 4. Validasi Ujian (Lagi, untuk keamanan)
// Cek apakah ujian ada, published, sesuai kelas, dan dalam rentang waktu
$query_ujian = "SELECT 
                    u.id_ujian, u.waktu_mulai, u.waktu_selesai
                FROM ujian u
                JOIN mengajar m ON u.id_mengajar = m.id_mengajar
                JOIN siswa s ON m.id_kelas = s.id_kelas AND s.id_siswa = ? 
                WHERE u.id_ujian = ? 
                  AND u.status_ujian = 'Published'";
                  
$stmt_val = mysqli_prepare($koneksi, $query_ujian);
mysqli_stmt_bind_param($stmt_val, "ii", $id_siswa_login, $id_ujian);
mysqli_stmt_execute($stmt_val);
$result_val = mysqli_stmt_get_result($stmt_val);

if (mysqli_num_rows($result_val) == 0) {
    header("Location: ujian_saya.php?error=Ujian tidak ditemukan atau tidak tersedia.");
    exit();
}
$ujian_data = mysqli_fetch_assoc($result_val);
mysqli_stmt_close($stmt_val);

// Validasi waktu lagi
$waktu_mulai_ujian_dt = new DateTime($ujian_data['waktu_mulai'], new DateTimeZone('Asia/Jakarta'));
$waktu_selesai_ujian_dt = new DateTime($ujian_data['waktu_selesai'], new DateTimeZone('Asia/Jakarta'));

if ($waktu_sekarang_dt < $waktu_mulai_ujian_dt) {
    header("Location: ujian_saya.php?error=Ujian belum dimulai.");
    exit();
}
// Tidak perlu cek waktu selesai di sini, karena siswa mungkin telat mulai

// 5. Cek/Buat Record Hasil Ujian Siswa
mysqli_begin_transaction($koneksi); // Mulai transaksi

try {
    // Cek apakah sudah ada record di ujian_hasil
    $query_cek_hasil = "SELECT id_hasil, status_pengerjaan FROM ujian_hasil WHERE id_ujian = ? AND id_siswa = ?";
    $stmt_cek_hasil = mysqli_prepare($koneksi, $query_cek_hasil);
    mysqli_stmt_bind_param($stmt_cek_hasil, "ii", $id_ujian, $id_siswa_login);
    mysqli_stmt_execute($stmt_cek_hasil);
    $result_cek_hasil = mysqli_stmt_get_result($stmt_cek_hasil);
    $hasil_data = mysqli_fetch_assoc($result_cek_hasil);
    mysqli_stmt_close($stmt_cek_hasil);

    $id_hasil = null;
    $status_sebelumnya = 'Belum';

    if ($hasil_data) {
        // Jika record sudah ada
        $id_hasil = $hasil_data['id_hasil'];
        $status_sebelumnya = $hasil_data['status_pengerjaan'];
        
        // Jika sudah selesai, jangan proses lagi
        if ($status_sebelumnya == 'Selesai' || $status_sebelumnya == 'Dinilai') {
            mysqli_rollback($koneksi); // Batalkan transaksi jika ada
            header("Location: ujian_saya.php?info=Ujian sudah Anda selesaikan.");
            exit();
        }
        
        // Jika status 'Mengerjakan', tidak perlu update waktu mulai
        if ($status_sebelumnya != 'Mengerjakan') {
             // Update status jadi 'Mengerjakan' dan catat waktu mulai HANYA jika statusnya 'Belum'
             $query_update_hasil = "UPDATE ujian_hasil SET status_pengerjaan = 'Mengerjakan', waktu_mulai_mengerjakan = ? WHERE id_hasil = ?";
             $stmt_update_hasil = mysqli_prepare($koneksi, $query_update_hasil);
             mysqli_stmt_bind_param($stmt_update_hasil, "si", $waktu_sekarang_sql, $id_hasil);
             if(!mysqli_stmt_execute($stmt_update_hasil)){
                 throw new Exception("Gagal update status hasil ujian.");
             }
             mysqli_stmt_close($stmt_update_hasil);
        }

    } else {
        // Jika record belum ada, buat baru
        $query_insert_hasil = "INSERT INTO ujian_hasil (id_ujian, id_siswa, waktu_mulai_mengerjakan, status_pengerjaan) 
                               VALUES (?, ?, ?, 'Mengerjakan')";
        $stmt_insert_hasil = mysqli_prepare($koneksi, $query_insert_hasil);
        mysqli_stmt_bind_param($stmt_insert_hasil, "iis", $id_ujian, $id_siswa_login, $waktu_sekarang_sql);
        if (!mysqli_stmt_execute($stmt_insert_hasil)) {
            throw new Exception("Gagal membuat record hasil ujian.");
        }
        $id_hasil = mysqli_insert_id($koneksi); // Dapatkan ID hasil yang baru dibuat
        mysqli_stmt_close($stmt_insert_hasil);
        
        // Jika ini pertama kali mulai, buat slot jawaban kosong di ujian_jawaban_siswa
        $query_soal = "SELECT id_soal FROM ujian_soal WHERE id_ujian = ?";
        $stmt_soal = mysqli_prepare($koneksi, $query_soal);
        mysqli_stmt_bind_param($stmt_soal, "i", $id_ujian);
        mysqli_stmt_execute($stmt_soal);
        $result_soal = mysqli_stmt_get_result($stmt_soal);
        
        $query_insert_jawaban = "INSERT INTO ujian_jawaban_siswa (id_hasil, id_soal) VALUES (?, ?)";
        $stmt_insert_jawaban = mysqli_prepare($koneksi, $query_insert_jawaban);
        
        while ($soal = mysqli_fetch_assoc($result_soal)) {
            mysqli_stmt_bind_param($stmt_insert_jawaban, "ii", $id_hasil, $soal['id_soal']);
            if (!mysqli_stmt_execute($stmt_insert_jawaban)) {
                throw new Exception("Gagal membuat slot jawaban untuk soal ID: " . $soal['id_soal']);
            }
        }
        mysqli_stmt_close($stmt_soal);
        mysqli_stmt_close($stmt_insert_jawaban);
    }

    // Jika semua query berhasil, commit transaksi
    mysqli_commit($koneksi);
    mysqli_close($koneksi);

    // Redirect ke halaman pengerjaan ujian
    header("Location: ujian_mengerjakan.php?id=" . $id_ujian);
    exit();

} catch (Exception $e) {
    // Jika terjadi error di salah satu query, rollback semua perubahan
    mysqli_rollback($koneksi);
    mysqli_close($koneksi);
    // Tampilkan pesan error (atau redirect dengan pesan error)
    // Untuk debugging: echo "Error: " . $e->getMessage(); exit();
    header("Location: ujian_konfirmasi.php?id=" . $id_ujian . "&error=" . urlencode("Gagal memulai ujian: " . $e->getMessage()));
    exit();
}

?>