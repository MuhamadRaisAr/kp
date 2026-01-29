<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';
$judul_halaman = "Cetak Rapor Siswa";

// Cek apakah user adalah admin
if ($_SESSION['role'] !== 'admin') {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini!'); window.location='dashboard.php';</script>";
    exit;
}

// Ambil data untuk dropdown
$query_tahun = "SELECT * FROM tahun_ajaran WHERE status_aktif = 'Aktif' ORDER BY tahun_ajaran DESC";
$query_kelas = "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC";
$result_tahun = mysqli_query($koneksi, $query_tahun);
$result_kelas = mysqli_query($koneksi, $query_kelas);

$selected_tahun = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';
$selected_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Cetak Rapor Siswa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Cetak Rapor</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-filter me-1"></i>Pilih Kelas & Tahun Ajaran</div>
        <div class="card-body">
            <form method="GET" action="cetak_rapot.php">
                <div class="row">
                    <div class="col-md-6">
                        <label for="tahun_ajaran" class="form-label">Tahun Ajaran Aktif</label>
                        <select name="tahun_ajaran" id="tahun_ajaran" class="form-select" required>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <?php while($row = mysqli_fetch_assoc($result_tahun)) {
                                $selected = ($row['id_tahun_ajaran'] == $selected_tahun) ? 'selected' : '';
                                echo "<option value='{$row['id_tahun_ajaran']}' $selected>{$row['tahun_ajaran']} - {$row['semester']}</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="kelas" class="form-label">Kelas</label>
                        <select name="kelas" id="kelas" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php while($row = mysqli_fetch_assoc($result_kelas)) {
                                $selected = ($row['id_kelas'] == $selected_kelas) ? 'selected' : '';
                                echo "<option value='{$row['id_kelas']}' $selected>{$row['nama_kelas']}</option>";
                            } ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Tampilkan Daftar Siswa</button>
            </form>
        </div>
    </div>

    <?php
    // Tampilkan daftar siswa HANYA JIKA filter sudah dipilih
    if (!empty($selected_tahun) && !empty($selected_kelas)) :
        $query_siswa = "SELECT id_siswa, nis, nisn, nama_lengkap FROM siswa WHERE id_kelas = {$selected_kelas} ORDER BY nama_lengkap ASC";
        $result_siswa = mysqli_query($koneksi, $query_siswa);
    ?>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-users me-1"></i>Daftar Siswa</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NIS</th>
                        <th>Nama Lengkap</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (mysqli_num_rows($result_siswa) > 0) {
                        $nomor = 1;
                        while($siswa = mysqli_fetch_assoc($result_siswa)) {
                            echo "<tr>";
                            echo "<td>" . $nomor++ . "</td>";
                            echo "<td>" . htmlspecialchars($siswa['nis']) . "</td>";
                            echo "<td>" . htmlspecialchars($siswa['nama_lengkap']) . "</td>";
                            echo "<td>";
                            // Tombol ini akan mengarah ke skrip pembuat PDF
                            echo "<a href='generate_pdf.php?id_siswa={$siswa['id_siswa']}&id_tahun_ajaran={$selected_tahun}' class='btn btn-success btn-sm' target='_blank'><i class='fas fa-print me-2'></i>Cetak Rapor</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>Tidak ada siswa di kelas ini.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>