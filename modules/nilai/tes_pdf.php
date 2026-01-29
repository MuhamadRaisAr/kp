<?php
// Panggil library FPDF
require 'lib/fpdf.php';

// Buat objek PDF baru
$pdf = new FPDF();

// Tambah halaman baru
$pdf->AddPage();

// Atur font
$pdf->SetFont('Arial', 'B', 16);

// Tulis teks di dalam sel
$pdf->Cell(40, 10, 'Halo Dunia! PDF berhasil dibuat.');

// Kirim output ke browser
$pdf->Output();
?>
