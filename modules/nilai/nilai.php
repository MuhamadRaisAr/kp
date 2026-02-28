<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/header.php';
require_once '../../includes/koneksi.php';

$judul_halaman = "Kelola Nilai";
$id_guru_login = isset($_SESSION['id_guru']) ? (int)$_SESSION['id_guru'] : null;

// Jika bukan guru, tolak
if ($_SESSION['role'] !== 'guru') {
    echo '<div class="container-fluid px-4"><div class="alert alert-danger mt-4">Halaman ini khusus untuk guru.</div></div>';
    require_once '../../includes/footer.php';
    exit();
}

// --- TENTUKAN STEP BERDASARKAN PARAMETER URL ---
$step = 1;
$selected_tahun = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';
$selected_kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';
$selected_mapel = isset($_GET['mapel']) ? $_GET['mapel'] : '';

// LOGIKA SKIP STEP 3 (Pilih Mapel) JIKA CUMA 1 MAPEL
if (!empty($selected_tahun) && !empty($selected_kelas) && empty($selected_mapel)) {
    // Cek jumlah mapel yang diajar guru ini di kelas & tahun tsb
    $q_cek_mapel = "SELECT id_mapel FROM mengajar 
                    WHERE id_guru = $id_guru_login 
                    AND id_kelas = '$selected_kelas' 
                    AND id_tahun_ajaran = '$selected_tahun'";
    $res_cek_mapel = mysqli_query($koneksi, $q_cek_mapel);
    
    if (mysqli_num_rows($res_cek_mapel) == 1) {
        // Jika cuma 1, langsung ambil id_mapelnya dan redirect ke step 4
        $row_mapel = mysqli_fetch_assoc($res_cek_mapel);
        $auto_mapel = $row_mapel['id_mapel'];
        // Redirect
        echo "<script>window.location.replace('nilai.php?tahun_ajaran=$selected_tahun&kelas=$selected_kelas&mapel=$auto_mapel');</script>";
        exit();
    }
}

if (!empty($selected_tahun) && !empty($selected_kelas) && !empty($selected_mapel)) {
    $step = 4;
} elseif (!empty($selected_tahun) && !empty($selected_kelas)) {
    $step = 3;
} elseif (!empty($selected_tahun)) {
    $step = 2;
}

// Helper untuk nama tahun global (breadcrumb)
$nama_tahun_global = "";
if($selected_tahun) {
    $q_tag = mysqli_query($koneksi, "SELECT * FROM tahun_ajaran WHERE id_tahun_ajaran = '$selected_tahun'");
    if($r_tag = mysqli_fetch_assoc($q_tag)) {
        $nama_tahun_global = $r_tag['tahun_ajaran'] . " (" . $r_tag['semester'] . ")";
    }
}
?>

<div class="container-fluid px-4">
    <?php if ($step == 1): ?>
        <!-- STEP 1: PILIH TAHUN AJARAN -->
        <h1 class="mt-4">Kelola Nilai - Pilih Tahun Ajaran</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Kelola Nilai</li>
        </ol>

        <div class="row">
            <?php
            // Tampilkan semua tahun ajaran seperti pada Laporan Nilai
            $query_tahun = "SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC, semester DESC";
                            
            $result_tahun = mysqli_query($koneksi, $query_tahun);
            if (mysqli_num_rows($result_tahun) > 0) {
                while ($row = mysqli_fetch_assoc($result_tahun)) {
                    $status_class = ($row['status_aktif'] == 'Aktif') ? 'border-primary' : '';
                    ?>
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                        <div class="card shadow-sm h-100 <?php echo $status_class; ?>">
                            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center py-4">
                                <div class="mb-3"><i class="fas fa-calendar-check fa-3x text-secondary"></i></div>
                                <h4 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($row['tahun_ajaran']); ?></h4>
                                <small class="text-muted"><?php echo htmlspecialchars($row['semester']); ?></small>
                                <?php if ($row['status_aktif'] == 'Aktif'): ?>
                                    <span class="badge bg-success mt-2">Aktif</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-white border-top-0 pb-3 text-center">
                                <a href="nilai.php?tahun_ajaran=<?php echo $row['id_tahun_ajaran']; ?>" class="btn btn-outline-dark w-100 rounded-pill">
                                    <i class="fas fa-arrow-right me-2"></i> Kelola Nilai
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-info">Anda belum memiliki jadwal mengajar di tahun ajaran manapun.</div></div>';
            }
            ?>
        </div>

    <?php elseif ($step == 2): 
        // STEP 2: PILIH KELAS
    ?>
        <h1 class="mt-4">Pilih Kelas</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="nilai.php">Kelola Nilai</a></li>
            <li class="breadcrumb-item active">Pilih Kelas (<?php echo htmlspecialchars($nama_tahun_global); ?>)</li>
        </ol>

        <div class="row">
            <?php
            // Filter Kelas: Hanya kelas yang DIAJAR oleh guru ini di tahun ini
            $query_kelas = "SELECT DISTINCT k.id_kelas, k.nama_kelas, k.tingkat
                            FROM kelas k
                            JOIN mengajar m ON k.id_kelas = m.id_kelas
                            WHERE m.id_guru = $id_guru_login AND m.id_tahun_ajaran = '$selected_tahun'
                            ORDER BY k.tingkat ASC, k.nama_kelas ASC";
            
            $result_kelas = mysqli_query($koneksi, $query_kelas);

            if (mysqli_num_rows($result_kelas) > 0) {
                while ($row = mysqli_fetch_assoc($result_kelas)) {
                    ?>
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                        <div class="card shadow-sm h-100 border-start-lg border-start-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                        <i class="fas fa-chalkboard-teacher text-primary fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted small mb-1">Kelas</h6>
                                        <h5 class="card-title fw-bold mb-0 text-dark"><?php echo htmlspecialchars($row['nama_kelas']); ?></h5>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="d-grid mt-3">
                                    <a href="nilai.php?tahun_ajaran=<?php echo $selected_tahun; ?>&kelas=<?php echo $row['id_kelas']; ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                                        <i class="fas fa-pen me-2"></i> Input Nilai
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-warning">Tidak ada kelas yang Anda ajar di tahun ajaran ini.</div></div>';
            }
            ?>
        </div>

    <?php elseif ($step == 3): 
        // STEP 3: PILIH MATA PELAJARAN (Hanya muncul jika mapel > 1)
        $q_kls = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas = '$selected_kelas'");
        $r_kls = mysqli_fetch_assoc($q_kls);
        $nama_kelas = $r_kls['nama_kelas'];
    ?>
        <h1 class="mt-4">Pilih Mata Pelajaran</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="nilai.php">Kelola Nilai</a></li>
            <li class="breadcrumb-item"><a href="nilai.php?tahun_ajaran=<?php echo $selected_tahun; ?>">Kelas <?php echo htmlspecialchars($nama_kelas); ?></a></li>
            <li class="breadcrumb-item active">Pilih Mapel</li>
        </ol>

        <div class="row">
            <?php
            // Filter Mapel: Hanya mapel yang diajar guru ini di kelas & tahun ini
            $query_mapel = "SELECT m.id_mapel, mp.nama_mapel
                            FROM mengajar m
                            JOIN mata_pelajaran mp ON m.id_mapel = mp.id_mapel
                            WHERE m.id_kelas = '$selected_kelas' 
                            AND m.id_tahun_ajaran = '$selected_tahun' 
                            AND m.id_guru = $id_guru_login
                            ORDER BY mp.nama_mapel ASC";
            
            $result_mapel = mysqli_query($koneksi, $query_mapel);

            if (mysqli_num_rows($result_mapel) > 0) {
                while ($row = mysqli_fetch_assoc($result_mapel)) {
                    ?>
                    <div class="col-xl-3 col-md-4 col-sm-6 mb-4">
                        <div class="card shadow-sm h-100 border-start-lg border-start-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                                        <i class="fas fa-book text-warning fa-lg"></i>
                                    </div>
                                    <div>
                                        <h6 class="card-subtitle text-muted small mb-1">Mata Pelajaran</h6>
                                        <h5 class="card-title fw-bold mb-0 text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['nama_mapel']); ?></h5>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="d-grid mt-3">
                                    <a href="nilai.php?tahun_ajaran=<?php echo $selected_tahun; ?>&kelas=<?php echo $selected_kelas; ?>&mapel=<?php echo $row['id_mapel']; ?>" class="btn btn-outline-warning btn-sm rounded-pill">
                                        <i class="fas fa-edit me-2"></i> Input Nilai
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12"><div class="alert alert-warning">Tidak ada mata pelajaran yang ditemukan.</div></div>';
            }
            ?>
        </div>

    <?php elseif ($step == 4): 
        // STEP 4: FORM INPUT NILAI
        $q_kls = mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id_kelas = '$selected_kelas'");
        $r_kls = mysqli_fetch_assoc($q_kls);
        $nama_kelas = $r_kls['nama_kelas'];
        
        $q_mpl = mysqli_query($koneksi, "SELECT nama_mapel FROM mata_pelajaran WHERE id_mapel = '$selected_mapel'");
        $r_mpl = mysqli_fetch_assoc($q_mpl);
        $nama_mapel = $r_mpl['nama_mapel'];
        
        // Ambil ID Mengajar
        $q_mengajar = "SELECT id_mengajar FROM mengajar WHERE id_guru = $id_guru_login AND id_kelas = '$selected_kelas' AND id_mapel = '$selected_mapel' AND id_tahun_ajaran = '$selected_tahun'";
        $res_mengajar = mysqli_query($koneksi, $q_mengajar);
        
        if(mysqli_num_rows($res_mengajar) > 0) {
            $d_mengajar = mysqli_fetch_assoc($res_mengajar);
            $id_mengajar = $d_mengajar['id_mengajar'];

            // Ambil Siswa
            $q_siswa = "SELECT id_siswa, nis, nama_lengkap FROM siswa WHERE id_kelas = '$selected_kelas' ORDER BY nama_lengkap ASC";
            $res_siswa = mysqli_query($koneksi, $q_siswa);

            // Ambil Nilai Existing
            $nilai_existing = [];
            $q_nilai_ex = "SELECT id_siswa, jenis_nilai, nilai FROM nilai WHERE id_mengajar = '$id_mengajar'";
            $res_nilai_ex = mysqli_query($koneksi, $q_nilai_ex);
            while($rn = mysqli_fetch_assoc($res_nilai_ex)) {
                $nilai_existing[$rn['id_siswa']][$rn['jenis_nilai']] = $rn['nilai'];
            }
    ?>
        <h1 class="mt-4">Input Nilai Siswa</h1>
        <ol class="breadcrumb mb-4">
            <li class="breadcrumb-item"><a href="/sistem-penilaian/dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="nilai.php">Kelola Nilai</a></li>
            <!-- Back Logic: Jika mapel > 1 kembalinya ke step 3, jika mapel = 1 kembalinya ke step 2 (Pilih Kelas) -->
            <?php
            $cnt_mapel = 0;
            $q_cnt = mysqli_query($koneksi, "SELECT COUNT(*) as jml FROM mengajar WHERE id_guru = $id_guru_login AND id_kelas = '$selected_kelas' AND id_tahun_ajaran = '$selected_tahun'");
            if($r_cnt = mysqli_fetch_assoc($q_cnt)) {
                $cnt_mapel = $r_cnt['jml'];
            }

             if($cnt_mapel > 1) {
                 echo '<li class="breadcrumb-item"><a href="nilai.php?tahun_ajaran='.$selected_tahun.'&kelas='.$selected_kelas.'">Pilih Mapel</a></li>';
                 $link_kembali = "nilai.php?tahun_ajaran=$selected_tahun&kelas=$selected_kelas";
             } else {
                 echo '<li class="breadcrumb-item"><a href="nilai.php?tahun_ajaran='.$selected_tahun.'">Pilih Kelas</a></li>';
                 $link_kembali = "nilai.php?tahun_ajaran=$selected_tahun";
             }
            ?>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($nama_mapel); ?></li>
        </ol>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-edit me-1"></i>
                Form Input Nilai: <strong><?php echo htmlspecialchars($nama_mapel); ?></strong> - Kelas <strong><?php echo htmlspecialchars($nama_kelas); ?></strong>
                <a href="<?php echo $link_kembali; ?>" class="btn btn-light btn-sm float-end text-primary fw-bold">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <form action="proses_simpan_nilai.php" method="POST">
                    <input type="hidden" name="id_mengajar" value="<?php echo htmlspecialchars($id_mengajar); ?>">
                    <!-- Kirim balik parameter ini agar setelah simpan bisa redirect kembali ke sini -->
                    <input type="hidden" name="redirect_tahun" value="<?php echo htmlspecialchars($selected_tahun); ?>">
                    <input type="hidden" name="redirect_kelas" value="<?php echo htmlspecialchars($selected_kelas); ?>">
                    <input type="hidden" name="redirect_mapel" value="<?php echo htmlspecialchars($selected_mapel); ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="30%">Nama Siswa</th>
                                    <th width="15%">Tugas (0-100)</th>
                                    <th width="15%">UTS (0-100)</th>
                                    <th width="15%">UAS (0-100)</th>
                                    <th width="15%">Praktik (0-100)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                if (mysqli_num_rows($res_siswa) > 0) {
                                    $nomor = 1;
                                    while($siswa = mysqli_fetch_assoc($res_siswa)): 
                                        $id_siswa = $siswa['id_siswa'];
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $nomor++; ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?> <br><small class="text-muted"><?php echo $siswa['nis']; ?></small></td>
                                    <?php 
                                    $jenis_penilaian = ['Tugas', 'UTS', 'UAS', 'Praktik'];
                                    foreach ($jenis_penilaian as $jenis) :
                                        $nilai = "";
                                        if(isset($nilai_existing[$id_siswa][$jenis])) {
                                            $nilai = $nilai_existing[$id_siswa][$jenis];
                                        }
                                    ?>
                                    <td>
                                        <input type="number" name="nilai[<?php echo $id_siswa; ?>][<?php echo $jenis; ?>]" value="<?php echo $nilai; ?>" class="form-control text-center" min="0" max="100" step="0.01" placeholder="-">
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php 
                                    endwhile;
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-4'>Tidak ada siswa di kelas ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                         <button type="submit" class="btn btn-success btn-lg px-5 shadow"><i class="fas fa-save me-2"></i> Simpan Nilai</button>
                    </div>
                </form>
            </div>
        </div>
    <?php 
        } else {
            echo '<div class="alert alert-danger">ERROR: Data Mengajar tidak valid (ID Mengajar tidak ditemukan). Silakan hubungi administrator.</div>';
        }
    endif; 
    ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
