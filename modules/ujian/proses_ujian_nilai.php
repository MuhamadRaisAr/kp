<?php
// Selalu mulai session di awal
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/koneksi.php';
require_once '../../includes/auth_check.php';

// Validasi Akses: Guru
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    header("Location: dashboard.php?error=Akses ditolak");
    exit();
}
$id_guru_login = (int)$_SESSION['id_guru'];

// Validasi Metode: POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ujian.php");
    exit();
}

// 1. Ambil Data dari Form
$id_hasil = isset($_POST['id_hasil']) ? (int)$_POST['id_hasil'] : 0;
// $id_ujian = isset($_POST['id_ujian']) ? (int)$_POST['id_ujian'] : 0; // Opsional, bisa dari check
$nilai_soal = isset($_POST['nilai']) ? $_POST['nilai'] : []; // Array [id_soal => nilai]

if ($id_hasil <= 0 || empty($nilai_soal)) {
    header("Location: ujian.php?error=Data penilaian tidak lengkap");
    exit();
}

// 2. Validasi Kepemilikan Ujian (Guru vs Hasil) melalui join
$query_cek = "SELECT uh.id_hasil, uh.status_pengerjaan, u.id_ujian 
              FROM ujian_hasil uh
              JOIN ujian u ON uh.id_ujian = u.id_ujian
              JOIN mengajar m ON u.id_mengajar = m.id_mengajar
              WHERE uh.id_hasil = ? AND m.id_guru = ?";
$stmt_cek = mysqli_prepare($koneksi, $query_cek);
mysqli_stmt_bind_param($stmt_cek, "ii", $id_hasil, $id_guru_login);
mysqli_stmt_execute($stmt_cek);
$res_cek = mysqli_stmt_get_result($stmt_cek);
$data_cek = mysqli_fetch_assoc($res_cek);
mysqli_stmt_close($stmt_cek);

if (!$data_cek) {
    header("Location: ujian.php?error=Data tidak valid atau akses ditolak");
    exit();
}
$id_ujian_db = $data_cek['id_ujian'];

// 3. Simpan Nilai per Soal & Hitung Total
mysqli_begin_transaction($koneksi);
$total_nilai = 0;
$jumlah_soal = count($nilai_soal);

try {
    // Siapkan update untuk detail jawaban
    // Karena tabel ujian_jawaban_siswa tidak punya kolom nilai, kita perlu menambahkannya.
    // Tapi tunggu, skema di awal tidak menyebutkan kolom nilai per soal di jawaban siswa.
    // Kita harus ALTER tabel dulu atau pakai asumsi 'is_benar' untuk menyimpan nilai (agak hacky).
    // Opsi terbaik: Tambah kolom 'nilai_esai' di tabel 'ujian_jawaban_siswa'.
    // CHECK: Migration script sebelumnya hanya mengubah tipe data.
    // Kita perlu tambah kolom 'nilai_esai' DECIMAL(5,2) DEFAULT 0.
    
    // Asumsi: Skrip migrasi di bawah akan dijalankan user (saya akan buatkan setelah ini).
    // Query update nilai per soal
    $query_update_nilai_soal = "UPDATE ujian_jawaban_siswa SET nilai_esai = ? WHERE id_hasil = ? AND id_soal = ?";
    $stmt_update_soal = mysqli_prepare($koneksi, $query_update_nilai_soal);

    foreach ($nilai_soal as $id_soal => $nilai) {
        $nilai_float = (float)$nilai;
        $total_nilai += $nilai_float;

        mysqli_stmt_bind_param($stmt_update_soal, "dii", $nilai_float, $id_hasil, $id_soal);
        if (!mysqli_stmt_execute($stmt_update_soal)) {
            throw new Exception("Gagal menyimpan nilai soal ID: " . $id_soal);
        }
    }
    mysqli_stmt_close($stmt_update_soal);

    // 4. Update Nilai Akhir di ujian_hasil
    // Nilai Akhir Ujian = Rata-rata nilai soal * 100? Atau total poin?
    // Biasanya kalau soal esai 5, masing-masing max 100 -> Total 500 -> Rata-rata 100.
    // Atau guru input poin mentah (misal soal 1 poin 20, soal 2 poin 80).
    // Di form saya set input max 100. Mari asumsikan nilai akhir adalah RATA-RATA dari nilai per soal.
    // JIKA jumlah soal > 0.
    
    $nilai_akhir_ujian = ($jumlah_soal > 0) ? ($total_nilai / $jumlah_soal) : 0;
    
    // Update status jadi 'Dinilai'
    $query_update_hasil = "UPDATE ujian_hasil SET nilai_akhir = ?, status_pengerjaan = 'Dinilai' WHERE id_hasil = ?";
    $stmt_update_hasil = mysqli_prepare($koneksi, $query_update_hasil);
    mysqli_stmt_bind_param($stmt_update_hasil, "di", $nilai_akhir_ujian, $id_hasil);
    if (!mysqli_stmt_execute($stmt_update_hasil)) {
        throw new Exception("Gagal menyimpan nilai akhir ujian.");
    }
    mysqli_stmt_close($stmt_update_hasil);

    mysqli_commit($koneksi);
    mysqli_close($koneksi);

    header("Location: ujian_hasil.php?id=" . $id_ujian_db . "&status=sukses_nilai");
    exit();

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    mysqli_close($koneksi);
    header("Location: ujian_periksa.php?id_hasil=" . $id_hasil . "&error=" . urlencode("Gagal menyimpan penilaian: " . $e->getMessage()));
    exit();
}
?>
