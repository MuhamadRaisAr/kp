<?php
// Mulai output buffering
ob_start();

// Panggil file-file yang dibutuhkan
require_once '../../includes/auth_check.php';
require_once '../../includes/koneksi.php';
require_once '../../lib/fpdf.php';

// Ambil ID dari URL dan pastikan valid
if (!isset($_GET['id_siswa']) || !isset($_GET['id_tahun_ajaran'])) {
    die("Parameter tidak lengkap.");
}
$id_siswa = (int)$_GET['id_siswa'];
$id_tahun_ajaran = (int)$_GET['id_tahun_ajaran'];

// Cek Keamanan: Jika user adalah siswa, pastikan dia hanya melihat datanya sendiri
if (isset($_SESSION['role']) && strtolower($_SESSION['role']) == 'siswa') {
    if ($id_siswa !== (int)$_SESSION['id_siswa']) {
        die("Akses ditolak. Anda hanya diperbolehkan mendownload nilai anda sendiri.");
    }
}

// --- Fungsi bantu untuk kalkulasi nilai ---
function hitungNilaiAkhir($tugas, $uts, $uas, $praktik) {
    $nilai_yang_ada = [];
    if ($tugas > 0) $nilai_yang_ada[] = $tugas;
    if ($uts > 0) $nilai_yang_ada[] = $uts;
    if ($uas > 0) $nilai_yang_ada[] = $uas;
    if ($praktik > 0) $nilai_yang_ada[] = $praktik;
    if (count($nilai_yang_ada) == 0) return 0;
    return array_sum($nilai_yang_ada) / count($nilai_yang_ada);
}
function tentukanPredikat($nilai_akhir) {
    if ($nilai_akhir >= 85) return 'A';
    if ($nilai_akhir >= 75) return 'B';
    if ($nilai_akhir >= 60) return 'C';
    if ($nilai_akhir >= 40) return 'D';
    return 'E';
}

// =================================================================
// MENGAMBIL SEMUA DATA DARI DATABASE
// =================================================================

// 1. Ambil Data Siswa & Kelas
$query_siswa = "SELECT s.nama_lengkap, s.nis, s.nisn, k.nama_kelas
                FROM siswa s 
                JOIN kelas k ON s.id_kelas = k.id_kelas 
                WHERE s.id_siswa = ?";
$stmt_siswa = mysqli_prepare($koneksi, $query_siswa);
mysqli_stmt_bind_param($stmt_siswa, "i", $id_siswa);
mysqli_stmt_execute($stmt_siswa);
$data_siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_siswa));

// 2. Ambil Data Tahun Ajaran
$query_tahun = "SELECT tahun_ajaran, semester FROM tahun_ajaran WHERE id_tahun_ajaran = ?";
$stmt_tahun = mysqli_prepare($koneksi, $query_tahun);
mysqli_stmt_bind_param($stmt_tahun, "i", $id_tahun_ajaran);
mysqli_stmt_execute($stmt_tahun);
$data_tahun = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_tahun));

// Hentikan jika data tidak ditemukan
if (!$data_siswa || !$data_tahun) {
    ob_end_clean();
    die("Data tidak ditemukan.");
}

// 3. Ambil Nilai
// PERBAIKAN: Hanya ambil nilai dari Mapel yang diajarkan DI KELAS SISWA TERSEBUT
$query_nilai = "SELECT mp.nama_mapel, n.jenis_nilai, n.nilai 
                FROM nilai n
                JOIN mengajar m ON n.id_mengajar = m.id_mengajar
                JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                WHERE n.id_siswa = ? 
                  AND m.id_tahun_ajaran = ?
                  AND m.id_kelas = (SELECT id_kelas FROM siswa WHERE id_siswa = ?)"; // Tambahan Filter Kelas

$stmt_nilai = mysqli_prepare($koneksi, $query_nilai);
// Binding parameter: id_siswa (i), id_tahun (i), id_siswa (subquery) (i) -> Total "iii"
mysqli_stmt_bind_param($stmt_nilai, "iii", $id_siswa, $id_tahun_ajaran, $id_siswa);
mysqli_stmt_execute($stmt_nilai);
$result_nilai = mysqli_stmt_get_result($stmt_nilai);

$nilai_per_mapel = [];
while($row = mysqli_fetch_assoc($result_nilai)) {
    $nilai_per_mapel[$row['nama_mapel']][$row['jenis_nilai']] = $row['nilai'];
}

// =================================================================
// MEMBUAT PDF
// =================================================================
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// --- KOP SURAT LEGAL ---
$logo_path = '../../assets/img/logo_sekolah.png';
if (file_exists($logo_path)) {
    // Image(file, x, y, w, h)
    $pdf->Image($logo_path, 15, 10, 22); 
}

// Atur Font Resmi (Times New Roman)
$pdf->SetFont('Times', 'B', 12);
$pdf->Cell(0, 5, 'YAYASAN PENDIDIKAN AL-HAWARI', 0, 1, 'C'); // Nama Yayasan

$pdf->SetFont('Times', 'B', 18);
$pdf->Cell(0, 8, 'SMK IT AL-HAWARI', 0, 1, 'C'); // Nama Sekolah Besar

$pdf->SetFont('Times', '', 10);
$pdf->Cell(0, 5, 'Jalan Raya Garut - Tasikmalaya KM. 10, Kec. Cilawu, Kab. Garut - Jawa Barat', 0, 1, 'C');
$pdf->Cell(0, 5, 'Email: smkitalhawari@example.com | Telp: (0262) 1234567', 0, 1, 'C');

// Garis Pemisah (Kop Surat)
$pdf->SetLineWidth(0.8);
$pdf->Line(10, 36, 200, 36); // Garis tebal
$pdf->SetLineWidth(0.2);
$pdf->Line(10, 37, 200, 37); // Garis tipis tambahan (Double Line Styles)

$pdf->Ln(8);

// --- JUDUL DOKUMEN ---
$pdf->SetFont('Times', 'B', 14);
$pdf->Cell(0, 7, 'DAFTAR NILAI HASIL BELAJAR', 0, 1, 'C');

$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 6, 'Tahun Ajaran ' . $data_tahun['tahun_ajaran'] . ' - Semester ' . $data_tahun['semester'], 0, 1, 'C');
$pdf->Ln(5);

// Info Siswa
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(35, 7, 'Nama Siswa', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(100, 7, $data_siswa['nama_lengkap'], 0, 1);

$pdf->Cell(35, 7, 'NIS / NISN', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(100, 7, $data_siswa['nis'] . ' / ' . $data_siswa['nisn'], 0, 1);

$pdf->Cell(35, 7, 'Kelas', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(100, 7, $data_siswa['nama_kelas'], 0, 1);
$pdf->Ln(5);

// Tabel Nilai
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);

// Header Tabel
$pdf->Cell(10, 10, 'No', 1, 0, 'C', true);
$pdf->Cell(80, 10, 'Mata Pelajaran', 1, 0, 'C', true);
$pdf->Cell(15, 10, 'Tugas', 1, 0, 'C', true);
$pdf->Cell(15, 10, 'UTS', 1, 0, 'C', true);
$pdf->Cell(15, 10, 'UAS', 1, 0, 'C', true);
$pdf->Cell(15, 10, 'Praktik', 1, 0, 'C', true);
$pdf->Cell(20, 10, 'Akhir', 1, 0, 'C', true);
$pdf->Cell(20, 10, 'Predikat', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 10);
$nomor = 1;

if (count($nilai_per_mapel) > 0) {
    foreach ($nilai_per_mapel as $mapel => $nilai) {
        $tugas = $nilai['Tugas'] ?? 0;
        $uts = $nilai['UTS'] ?? 0;
        $uas = $nilai['UAS'] ?? 0;
        $praktik = $nilai['Praktik'] ?? 0;
        $nilai_akhir = hitungNilaiAkhir($tugas, $uts, $uas, $praktik);
        $predikat = tentukanPredikat($nilai_akhir);
        
        $pdf->Cell(10, 8, $nomor++, 1, 0, 'C');
        $pdf->Cell(80, 8, $mapel, 1, 0, 'L');
        $pdf->Cell(15, 8, ($tugas > 0 ? $tugas : '-'), 1, 0, 'C');
        $pdf->Cell(15, 8, ($uts > 0 ? $uts : '-'), 1, 0, 'C');
        $pdf->Cell(15, 8, ($uas > 0 ? $uas : '-'), 1, 0, 'C');
        $pdf->Cell(15, 8, ($praktik > 0 ? $praktik : '-'), 1, 0, 'C');
        $pdf->Cell(20, 8, number_format($nilai_akhir, 2), 1, 0, 'C');
        $pdf->Cell(20, 8, $predikat, 1, 1, 'C');
    }
} else {
    $pdf->Cell(190, 10, 'Belum ada data nilai.', 1, 1, 'C');
}

$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 8);
$pdf->Cell(0, 5, 'Dicetak pada: ' . date('d-m-Y H:i:s'), 0, 1, 'R');

// Output PDF (D = Download)
ob_end_clean();
$filename = 'Nilai_' . str_replace(' ', '_', $data_siswa['nama_lengkap']) . '_' . $data_tahun['semester'] . '.pdf';
$pdf->Output('D', $filename);
?>
