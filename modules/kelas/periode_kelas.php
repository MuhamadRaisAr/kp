<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Periode Kelas";
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Periode Kelas</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Periode Kelas</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar-alt me-1"></i>
            Daftar Periode Kelas (Tahun Ajaran)
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>No</th>
                        <th>Tahun Ajaran</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Jumlah Kelas</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT t.id_tahun_ajaran, t.tahun_ajaran, t.semester, t.status_aktif, 
                              COUNT(k.id_kelas) as jumlah_kelas 
                              FROM tahun_ajaran t 
                              LEFT JOIN kelas k ON t.id_tahun_ajaran = k.id_tahun_ajaran 
                              GROUP BY t.id_tahun_ajaran 
                              ORDER BY t.tahun_ajaran DESC, t.semester DESC";
                    
                    $result = mysqli_query($koneksi, $query);
                    $no = 1;
                    
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $status_badge = ($row['status_aktif'] == 'Aktif') ? 'bg-success' : 'bg-secondary';
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['tahun_ajaran']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['semester']) . "</td>";
                            echo "<td><span class='badge $status_badge'>" . htmlspecialchars($row['status_aktif']) . "</span></td>";
                            echo "<td>" . $row['jumlah_kelas'] . " Kelas</td>";
                            echo "<td>
                                    <a href='kelas.php?tahun_ajaran=" . $row['id_tahun_ajaran'] . "' class='btn btn-primary btn-sm'>
                                        <i class='fas fa-eye me-1'></i> Lihat Kelas
                                    </a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>Belum ada data tahun ajaran.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once '../../includes/footer.php';
?>
