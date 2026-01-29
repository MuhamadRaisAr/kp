<?php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

$judul_halaman = "Manajemen Ujian";

// pastikan guru
if (!isset($_SESSION['id_guru']) || empty($_SESSION['id_guru'])) {
    echo '<div class="container-fluid px-4">
            <div class="alert alert-danger mt-4">
                Akses ditolak. Halaman ini hanya untuk Guru.
            </div>
          </div>';
    require_once 'includes/footer.php';
    exit();
}

$id_guru_login = (int)$_SESSION['id_guru'];
$waktu_sekarang = time();
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manajemen Ujian</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Manajemen Ujian</li>
    </ol>

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'sukses_hapus'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Ujian berhasil dihapus.
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($_GET['status'] == 'sukses_buka'): ?>
            <div class="alert alert-success alert-dismissible fade show">
                Ujian berhasil diperbarui / dibuka kembali.
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="mb-3">
        <a href="ujian_tambah.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Ujian Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-list-alt me-1"></i>
            Daftar Ujian yang Anda Buat
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Judul Ujian</th>
                            <th>Mapel</th>
                            <th>Kelas</th>
                            <th>Tahun Ajaran</th>
                            <th>Durasi</th>
                            <th>Jadwal</th>
                            <th>Status</th>
                            <th width="160">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT 
                                    u.id_ujian,
                                    u.judul_ujian,
                                    u.durasi_menit,
                                    u.waktu_mulai,
                                    u.waktu_selesai,
                                    u.status_ujian,
                                    mp.nama_mapel,
                                    k.nama_kelas,
                                    ta.tahun_ajaran,
                                    ta.semester
                                FROM ujian u
                                JOIN mengajar m ON u.id_mengajar = m.id_mengajar
                                JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                                JOIN kelas k ON m.id_kelas = k.id_kelas
                                JOIN tahun_ajaran ta ON m.id_tahun_ajaran = ta.id_tahun_ajaran
                                WHERE m.id_guru = ?
                                ORDER BY u.waktu_mulai DESC";

                        $stmt = mysqli_prepare($koneksi, $sql);
                        mysqli_stmt_bind_param($stmt, "i", $id_guru_login);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        if (mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) {

                                $mulai_ts = strtotime($row['waktu_mulai']);
                                $selesai_ts = strtotime($row['waktu_selesai']);

                                $jadwal = date('d M Y H:i', $mulai_ts) . " - " . date('H:i', $selesai_ts);

                                $badge = 'bg-secondary';
                                if ($row['status_ujian'] == 'Published') $badge = 'bg-success';
                                if ($row['status_ujian'] == 'Selesai') $badge = 'bg-dark';

                                echo "<tr>";
                                echo "<td>".$no++."</td>";
                                echo "<td>".htmlspecialchars($row['judul_ujian'])."</td>";
                                echo "<td>".htmlspecialchars($row['nama_mapel'])."</td>";
                                echo "<td>".htmlspecialchars($row['nama_kelas'])."</td>";
                                echo "<td>".htmlspecialchars($row['tahun_ajaran']." (".$row['semester'].")")."</td>";
                                echo "<td>".$row['durasi_menit']." Menit</td>";
                                echo "<td>".$jadwal."</td>";
                                echo "<td><span class='badge $badge'>".$row['status_ujian']."</span></td>";

                                // Buka kolom aksi
                                echo "<td>";

                                // Tombol Edit (Hanya jika Draft)
                                echo "<a href='ujian_edit.php?id=" . $row['id_ujian'] . "' class='btn btn-warning btn-sm me-1' title='Edit Ujian'><i class='fas fa-edit'></i></a>";

                                // Cek apakah ujian sudah lewat / expired
                                $waktu_selesai_ts = strtotime($row['waktu_selesai']);
                                if ($row['status_ujian'] != 'Draft' && $waktu_sekarang > $waktu_selesai_ts) {
                                    // Tombol Buka Kembali (Memicu Modal)
                                    echo "<button type='button' class='btn btn-dark btn-sm me-1' title='Buka Kembali Ujian' data-bs-toggle='modal' data-bs-target='#modalBukaKembali' data-id='" . $row['id_ujian'] . "' data-judul='" . htmlspecialchars($row['judul_ujian'], ENT_QUOTES) . "'>
                                            <i class='fas fa-lock-open'></i>
                                          </button>";
                                }

                                // ==========================================
                                // PERUBAHAN LOGIKA TOMBOL HAPUS - SELALU MUNCUL
                                // ==========================================
                                echo "<a href='proses_ujian_hapus.php?id=" . $row['id_ujian'] . "' class='btn btn-danger btn-sm' title='Hapus Ujian' onclick='return confirm(\"Yakin ingin menghapus ujian ini? SEMUA SOAL dan NILAI SISWA di dalamnya juga akan terhapus.\");'><i class='fas fa-trash'></i></a>";
                                // ==========================================
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr>
                                    <td colspan='9' class='text-center'>
                                        Belum ada ujian.
                                    </td>
                                  </tr>";
                        }

                        mysqli_stmt_close($stmt);
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buka Kembali -->
<div class="modal fade" id="modalBukaKembali" tabindex="-1" aria-labelledby="modalBukaKembaliLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title text-dark" id="modalBukaKembaliLabel"><i class="fas fa-history me-2"></i>Buka Kembali Ujian</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="proses_ujian_buka_kembali.php" method="POST">
          <div class="modal-body">
            <p>Anda akan membuka kembali ujian: <strong id="namaUjianModal"></strong></p>
            <input type="hidden" name="id_ujian" id="idUjianModal">
            
            <div class="mb-3">
                <label for="waktu_selesai_baru" class="form-label">Waktu Selesai Baru:</label>
                <input type="datetime-local" class="form-control" name="waktu_selesai_baru" required 
                       value="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>">
                <small class="text-muted">Tentukan sampai kapan ujian ini dibuka kembali.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-dark">Simpan & Buka</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script>
    var modalBukaKembali = document.getElementById('modalBukaKembali')
    modalBukaKembali.addEventListener('show.bs.modal', function (event) {
        // Tombol yang memicu modal
        var button = event.relatedTarget
        // Ambil data dari atribut data-*
        var idUjian = button.getAttribute('data-id')
        var judulUjian = button.getAttribute('data-judul')
        
        // Update isi modal
        var inputId = modalBukaKembali.querySelector('#idUjianModal')
        var labelNama = modalBukaKembali.querySelector('#namaUjianModal')
        
        inputId.value = idUjian
        labelNama.textContent = judulUjian
    })
</script>

<?php
// Panggil file footer.php
require_once 'includes/footer.php';
?>
