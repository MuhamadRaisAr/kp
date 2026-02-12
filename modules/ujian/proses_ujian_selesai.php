<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/koneksi.php';
require_once '../../includes/auth_check.php'; // Memastikan login & set role

// 1. Validasi Akses: Siswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa' || !isset($_SESSION['id_siswa']) || empty($_SESSION['id_siswa'])) {
    header("Location: login.php?error=Akses ditolak");
    exit();
}
$id_siswa_login = (int)$_SESSION['id_siswa'];

// 2. Validasi Metode: POST (dari form) atau GET (jika timeout)
$is_timeout = isset($_GET['timeout']) && $_GET['timeout'] == 1;
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$is_timeout) {
    header("Location: ujian_saya.php");
    exit();
}

// 3. Ambil ID Ujian dan Hasil
$id_ujian = $is_timeout ? (isset($_GET['id_ujian']) ? (int)$_GET['id_ujian'] : 0) : (isset($_POST['id_ujian']) ? (int)$_POST['id_ujian'] : 0);
$id_hasil = $is_timeout ? 0 : (isset($_POST['id_hasil']) ? (int)$_POST['id_hasil'] : 0); // Jika timeout, kita cari id_hasil dari DB

// Ambil jawaban dari POST jika bukan timeout
$jawaban_siswa_post = $is_timeout ? [] : (isset($_POST['jawaban']) ? $_POST['jawaban'] : []); // Format: [id_soal => jawaban_huruf]

if ($id_ujian <= 0) {
    header("Location: ujian_saya.php?error=Ujian tidak valid");
    exit();
}

// Waktu server saat ini
$waktu_selesai_mengerjakan = date('Y-m-d H:i:s');

// 4. Validasi Status Pengerjaan Siswa
// Kita perlu id_hasil, jika timeout cari dulu
if ($is_timeout && $id_hasil == 0) {
    $stmt_cari_hasil = mysqli_prepare($koneksi, "SELECT id_hasil FROM ujian_hasil WHERE id_ujian = ? AND id_siswa = ? AND status_pengerjaan = 'Mengerjakan'");
    mysqli_stmt_bind_param($stmt_cari_hasil, "ii", $id_ujian, $id_siswa_login);
    mysqli_stmt_execute($stmt_cari_hasil);
    $res_cari_hasil = mysqli_stmt_get_result($stmt_cari_hasil);
    if ($data_hasil = mysqli_fetch_assoc($res_cari_hasil)) {
        $id_hasil = $data_hasil['id_hasil'];
    }
    mysqli_stmt_close($stmt_cari_hasil);
}

if ($id_hasil <= 0) {
    // Jika id_hasil tetap tidak ketemu (mungkin belum mulai atau sudah selesai)
    header("Location: ujian_saya.php?error=Status ujian tidak valid atau belum dimulai.");
    exit();
}

// Cek status terakhir, harus 'Mengerjakan'
$stmt_cek_status = mysqli_prepare($koneksi, "SELECT status_pengerjaan FROM ujian_hasil WHERE id_hasil = ? AND id_siswa = ?");
mysqli_stmt_bind_param($stmt_cek_status, "ii", $id_hasil, $id_siswa_login);
mysqli_stmt_execute($stmt_cek_status);
$res_cek_status = mysqli_stmt_get_result($stmt_cek_status);
$data_status = mysqli_fetch_assoc($res_cek_status);
mysqli_stmt_close($stmt_cek_status);

if (!$data_status || $data_status['status_pengerjaan'] !== 'Mengerjakan') {
    header("Location: ujian_saya.php?info=Ujian sudah selesai atau status tidak valid.");
    exit();
}

// 5a. Ambil Jenis Ujian
$stmt_ju = mysqli_prepare($koneksi, "SELECT jenis_ujian FROM ujian WHERE id_ujian = ?");
mysqli_stmt_bind_param($stmt_ju, "i", $id_ujian);
mysqli_stmt_execute($stmt_ju);
$res_ju = mysqli_stmt_get_result($stmt_ju);
$data_ju = mysqli_fetch_assoc($res_ju);
$jenis_ujian = $data_ju['jenis_ujian'] ?? 'Pilihan Ganda';
mysqli_stmt_close($stmt_ju);

// 5b. Ambil Kunci Jawaban (hanya ID soal jika Esai)
$kunci_jawaban_db = []; // Format: [id_soal => kunci_huruf]
$query_kunci = "SELECT id_soal, kunci_jawaban FROM ujian_soal WHERE id_ujian = ?";
$stmt_kunci = mysqli_prepare($koneksi, $query_kunci);
mysqli_stmt_bind_param($stmt_kunci, "i", $id_ujian);
mysqli_stmt_execute($stmt_kunci);
$result_kunci = mysqli_stmt_get_result($stmt_kunci);
while ($row = mysqli_fetch_assoc($result_kunci)) {
    $kunci_jawaban_db[$row['id_soal']] = $row['kunci_jawaban'];
}
mysqli_stmt_close($stmt_kunci);

$total_soal = count($kunci_jawaban_db);
if ($total_soal == 0) {
    header("Location: ujian_saya.php?error=Ujian tidak memiliki soal.");
    exit();
}

// 6. Proses Penyimpanan Jawaban dan Hitung Nilai
mysqli_begin_transaction($koneksi); // Mulai transaksi
$jumlah_benar = 0;

try {
    // Siapkan statement di dalam loop atau gunakan satu statement INSERT ON DUPLICATE KEY UPDATE jika supported
    // Karena kita ingin aman dan kompatibel, kita cek dulu keberadaan data (Upsert manual)
    
    // Statement untuk cek
    $stmt_cek_jawab = mysqli_prepare($koneksi, "SELECT 1 FROM ujian_jawaban_siswa WHERE id_hasil = ? AND id_soal = ?");
    
    // Statement untuk insert
    $stmt_insert_jawab = mysqli_prepare($koneksi, "INSERT INTO ujian_jawaban_siswa (jawaban_siswa, is_benar, id_hasil, id_soal) VALUES (?, ?, ?, ?)");
    
    // Statement untuk update
    $stmt_update_existing = mysqli_prepare($koneksi, "UPDATE ujian_jawaban_siswa SET jawaban_siswa = ?, is_benar = ? WHERE id_hasil = ? AND id_soal = ?");

    // Iterasi melalui SEMUA soal
    foreach ($kunci_jawaban_db as $id_soal => $kunci) {
        $jawaban_pilihan_siswa = isset($jawaban_siswa_post[$id_soal]) ? $jawaban_siswa_post[$id_soal] : null; 
        
        $is_benar = 0; // Default salah (untuk PG) atau 0 (sementara)

        if ($jenis_ujian == 'Esai') {
            // Logika Esai:
            // 1. Simpan jawaban teks (bisa panjang)
            // 2. Set is_benar = NULL (karena belum dinilai)
            // 3. Nilai otomatis = 0 (menunggu guru)
            $is_benar = null; 
            // Jawaban siswa sudah diambil di atas
        } else {
            // Logika Pilihan Ganda:
            if ($jawaban_pilihan_siswa !== null) {
                // Bandingkan dengan kunci
                if ($jawaban_pilihan_siswa == $kunci) {
                    $is_benar = 1;
                    $jumlah_benar++;
                }
            } else {
                $jawaban_pilihan_siswa = null;
            }
        }

        // Cek apakah data sudah ada
        // Kita harus reset parameter statement cek
        mysqli_stmt_bind_param($stmt_cek_jawab, "ii", $id_hasil, $id_soal);
        if (!mysqli_stmt_execute($stmt_cek_jawab)) {
             throw new Exception("Gagal mengecek jawaban untuk soal ID: " . $id_soal);
        }
        mysqli_stmt_store_result($stmt_cek_jawab);
        $exists_rows = mysqli_stmt_num_rows($stmt_cek_jawab);
        mysqli_stmt_free_result($stmt_cek_jawab); // Penting untuk enable next execute

        if ($exists_rows > 0) {
            // Update
            mysqli_stmt_bind_param($stmt_update_existing, "ssii", $jawaban_pilihan_siswa, $is_benar, $id_hasil, $id_soal);
            if (!mysqli_stmt_execute($stmt_update_existing)) {
                 throw new Exception("Gagal mengupdate jawaban untuk soal ID: " . $id_soal);
            }
        } else {
            // Insert
            mysqli_stmt_bind_param($stmt_insert_jawab, "ssii", $jawaban_pilihan_siswa, $is_benar, $id_hasil, $id_soal);
            if (!mysqli_stmt_execute($stmt_insert_jawab)) {
                 throw new Exception("Gagal menyimpan (insert) jawaban untuk soal ID: " . $id_soal);
            }
        }
    }
    
    // Tutup statement
    mysqli_stmt_close($stmt_cek_jawab);
    mysqli_stmt_close($stmt_insert_jawab);
    mysqli_stmt_close($stmt_update_existing);

    // 7. Hitung Nilai Akhir
    if ($jenis_ujian == 'Esai') {
        $nilai_akhir = 0; // Default untuk esai sebelum dinilai
    } else {
        $nilai_akhir = ($total_soal > 0) ? ($jumlah_benar / $total_soal) * 100 : 0;
    }

    // 8. Update Hasil Ujian (Nilai dan Status)
    $query_update_hasil = "UPDATE ujian_hasil 
                           SET nilai_akhir = ?, waktu_selesai_mengerjakan = ?, status_pengerjaan = 'Selesai' 
                           WHERE id_hasil = ?";
    $stmt_update_hasil = mysqli_prepare($koneksi, $query_update_hasil);
    mysqli_stmt_bind_param($stmt_update_hasil, "dsi", $nilai_akhir, $waktu_selesai_mengerjakan, $id_hasil);
    if (!mysqli_stmt_execute($stmt_update_hasil)) {
        throw new Exception("Gagal menyimpan nilai akhir ujian.");
    }
    mysqli_stmt_close($stmt_update_hasil);

    // Jika semua berhasil, commit transaksi
    mysqli_commit($koneksi);
    mysqli_close($koneksi);

    // Redirect ke halaman daftar ujian dengan pesan sukses
    header("Location: ujian_saya.php?status=selesai&id_ujian=" . $id_ujian);
    exit();

} catch (Exception $e) {
    // Jika ada error, rollback transaksi
    mysqli_rollback($koneksi);
    mysqli_close($koneksi);
    // Redirect dengan pesan error
    // Untuk debugging: echo "Error: " . $e->getMessage(); exit();
    $redirect_url = $is_timeout ? "ujian_saya.php" : "ujian_mengerjakan.php?id=" . $id_ujian;
    header("Location: ujian_selesai.php?id_ujian=" . $id_ujian);
}
?>
