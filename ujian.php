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

                                echo "<td>";

                                // detail
                                echo "<a href='ujian_detail.php?id=".$row['id_ujian']."' class='btn btn-info btn-sm me-1'>
                                        <i class='fas fa-tasks'></i>
                                      </a>";

                                // hasil
                                echo "<a href='ujian_hasil.php?id=".$row['id_ujian']."' class='btn btn-success btn-sm me-1'>
                                        <i class='fas fa-poll'></i>
                                      </a>";
                                // EDIT (Draft / sebelum mulai)
                                if (
                                    $row['status_ujian'] == 'Draft' ||
                                    ($row['status_ujian'] == 'Published' && $waktu_sekarang < $mulai_ts)
                                ) {
                                    echo "<a href='ujian_edit.php?id=".$row['id_ujian']."' 
                                            class='btn btn-warning btn-sm me-1'
                                            title='Edit Ujian'>
                                            <i class='fas fa-edit'></i>
                                        </a>";
                                }

                                // BUKA KEMBALI (Published & sudah lewat)
                                if (
                                    $row['status_ujian'] == 'Published' &&
                                    $waktu_sekarang > $selesai_ts
                                ) {
                                    echo "<a href='ujian_edit.php?id=".$row['id_ujian']."&mode=buka_ulang' 
                                            class='btn btn-primary btn-sm me-1'
                                            title='Buka Kembali Ujian'>
                                            <i class='fas fa-redo'></i>
                                        </a>";
                                }


                                // HAPUS (aman)
                                if (
                                    $row['status_ujian'] == 'Draft' ||
                                    ($row['status_ujian'] == 'Published' && $waktu_sekarang < $mulai_ts)
                                ) {
                                    echo "<a href='proses_ujian_hapus.php?id=".$row['id_ujian']."' 
                                              class='btn btn-danger btn-sm'
                                              onclick='return confirm(\"Yakin hapus ujian ini?\")'>
                                            <i class='fas fa-trash'></i>
                                          </a>";
                                }

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

<?php require_once 'includes/footer.php'; ?>
