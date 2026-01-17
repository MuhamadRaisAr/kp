<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Absensi Siswa Harian";

// SESSION
$id_guru_login = $_SESSION['id_guru'] ?? null;
$role_login    = strtolower($_SESSION['role'] ?? 'guest');

if ($role_login !== 'guru' || !$id_guru_login) {
    echo '<div class="container-fluid px-4">
            <div class="alert alert-danger mt-4">Halaman ini hanya untuk Guru.</div>
          </div>';
    require_once 'includes/footer.php';
    exit();
}

// FILTER
$selected_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$selected_tahun   = $_GET['tahun_ajaran'] ?? '';
$selected_kelas   = $_GET['kelas'] ?? '';

// DEFAULT TAHUN AJARAN AKTIF
if (!$selected_tahun) {
    $q = mysqli_query($koneksi, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status_aktif='Aktif' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $selected_tahun = mysqli_fetch_assoc($q)['id_tahun_ajaran'];
    }
}
?>

<div class="container-fluid px-4">
<h1 class="mt-4">Absensi Siswa Harian</h1>

<div class="card mb-4">
<div class="card-header"><i class="fas fa-filter me-1"></i>Pilih Jadwal</div>
<div class="card-body">
<form method="GET">
<div class="row">

<div class="col-md-4">
<label class="form-label">Tanggal</label>
<input type="date" name="tanggal" class="form-control"
       value="<?= $selected_tanggal ?>" required>
</div>

<div class="col-md-4">
<label class="form-label">Tahun Ajaran</label>
<select name="tahun_ajaran" class="form-select" onchange="this.form.submit()" required>
<option value="">-- Pilih Tahun Ajaran --</option>
<?php
$q = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC");
while ($t = mysqli_fetch_assoc($q)) {
    $sel = ($t['id_tahun_ajaran'] == $selected_tahun) ? 'selected' : '';
    echo "<option value='{$t['id_tahun_ajaran']}' $sel>
            {$t['tahun_ajaran']} - {$t['semester']}
          </option>";
}
?>
</select>
</div>

<div class="col-md-4">
<label class="form-label">Kelas</label>
<select name="kelas" class="form-select" onchange="this.form.submit()" required>
<option value="">-- Pilih Kelas --</option>
<?php
if ($selected_tahun) {
    $q = mysqli_query($koneksi, "
        SELECT DISTINCT k.id_kelas, k.nama_kelas
        FROM kelas k
        JOIN mengajar m ON k.id_kelas = m.id_kelas
        WHERE m.id_guru = $id_guru_login
          AND m.id_tahun_ajaran = $selected_tahun
    ");
    while ($k = mysqli_fetch_assoc($q)) {
        $sel = ($k['id_kelas'] == $selected_kelas) ? 'selected' : '';
        echo "<option value='{$k['id_kelas']}' $sel>{$k['nama_kelas']}</option>";
    }
}
?>
</select>
</div>

</div>
<button class="btn btn-primary mt-3">Tampilkan Siswa</button>
</form>
</div>
</div>

<?php
// ================== TABEL ABSENSI ==================
if ($selected_tanggal && $selected_kelas && $selected_tahun):

$q_mengajar = mysqli_query($koneksi, "
    SELECT id_mengajar
    FROM mengajar
    WHERE id_guru = $id_guru_login
      AND id_kelas = $selected_kelas
      AND id_tahun_ajaran = $selected_tahun
    LIMIT 1
");

if (mysqli_num_rows($q_mengajar) == 0) {
    echo '<div class="alert alert-warning">Tidak ada jadwal mengajar.</div>';
    goto footer;
}

$id_mengajar = mysqli_fetch_assoc($q_mengajar)['id_mengajar'];

$q_siswa = mysqli_query($koneksi, "
    SELECT id_siswa, nama_lengkap
    FROM siswa
    WHERE id_kelas = $selected_kelas
    ORDER BY nama_lengkap
");

// ABSENSI EXISTING
$absensi = [];
$stmt = mysqli_prepare($koneksi,
    "SELECT id_siswa, status FROM absensi WHERE id_mengajar=? AND tanggal=?"
);
mysqli_stmt_bind_param($stmt, "is", $id_mengajar, $selected_tanggal);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($r = mysqli_fetch_assoc($res)) {
    $absensi[$r['id_siswa']] = $r['status'];
}
?>

<div class="card mb-4">
<div class="card-header">
<i class="fas fa-user-check me-1"></i>
Input Absensi (<?= date('d F Y', strtotime($selected_tanggal)) ?>)
</div>

<div class="card-body">
<form action="proses_absensi.php" method="POST">
<input type="hidden" name="id_mengajar" value="<?= $id_mengajar ?>">
<input type="hidden" name="tanggal" value="<?= $selected_tanggal ?>">

<table class="table table-hover">
<thead>
<tr>
<th>Nama Siswa</th>
<th class="text-center" width="40%">Status Kehadiran</th>
</tr>
</thead>
<tbody>

<?php while ($s = mysqli_fetch_assoc($q_siswa)):
$status = $absensi[$s['id_siswa']] ?? 'Hadir'; ?>

<tr>
<td class="align-middle"><?= htmlspecialchars($s['nama_lengkap']) ?></td>
<td>
<div class="btn-group w-100" role="group">

<?php
$opsi = [
 'Hadir' => 'success',
 'Sakit' => 'warning',
 'Izin'  => 'info',
 'Alfa'  => 'danger'
];

foreach ($opsi as $label => $color):
$id = strtolower($label).'_'.$s['id_siswa'];
?>

<input type="radio" class="btn-check"
       name="status[<?= $s['id_siswa'] ?>]"
       id="<?= $id ?>"
       value="<?= $label ?>"
       <?= $status == $label ? 'checked' : '' ?>>

<label class="btn btn-outline-<?= $color ?>" for="<?= $id ?>">
<?= $label ?>
</label>

<?php endforeach; ?>

</div>
</td>
</tr>

<?php endwhile; ?>

</tbody>
</table>

<button class="btn btn-primary float-end">Simpan Absensi</button>
</form>
</div>
</div>

<?php endif; ?>
</div>

<?php
footer:
require_once 'includes/footer.php';
?>
