<?php
// Memastikan session selalu dimulai di header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// DEFINISI BASE URL (PENTING AGAR TIDAK ERROR DI SUB-FOLDER)
if (!isset($base_url)) {
    // Sesuaikan ini dengan folder project Anda di Laragon/XAMPP
    $base_url = '/sistem-penilaian/'; 
}

// Mengatur judul halaman default jika tidak ditentukan di halaman lain
if (!isset($judul_halaman)) {
    $judul_halaman = "Sistem Penilaian SMK";
}

// Cek status login
$is_logged_in = isset($_SESSION['username']);

// ==========================================================
// AMBIL FOTO PROFIL DARI SESSION UNTUK NAVBAR
// ==========================================================
$foto_path_navbar = $base_url . 'assets/img/default-avatar.png'; // Path default

// Kita percaya session-nya dan HAPUS file_exists() yang error
if ($is_logged_in && !empty($_SESSION['foto_profil'])) {
    
    $foto_path_navbar = $base_url . 'assets/uploads/profiles/' . $_SESSION['foto_profil'];
}
// ==========================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($judul_halaman); ?> - SI Penilaian</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link href="<?php echo $base_url; ?>assets/css/style.css" rel="stylesheet">

    <style>
        /* CSS untuk foto profil di navbar */
        .navbar-avatar {
            width: 30px;
            height: 30px;
            object-fit: cover; /* Ini penting agar foto tidak gepeng */
            border-radius: 50%;
            margin-right: 8px; /* Jarak antara foto dan nama */
        }
    
        /* Layout full-height */
        body { display: flex; min-height: 100vh; flex-direction: column; }
        .main-wrapper { display: flex; flex: 1; }
        .content-wrapper { flex: 1; display: flex; flex-direction: column; overflow-x: hidden; }
        
        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            min-height: 100vh;
        }

        @media (max-width: 992px) {
            .main-wrapper { flex-direction: column; }
            .sidebar {
                width: 100%;
                min-height: auto;
                height: auto;
            }
        }
    </style>
</head>
<body>

<div class="main-wrapper"> 
    
    <?php 
    // Hanya tampilkan sidebar jika sudah login
    if ($is_logged_in) {
        // Gunakan __DIR__ agar bisa dipanggil dari folder manapun tanpa error path
        require_once __DIR__ . '/sidebar.php'; 
    }
    ?>
    
    <div class="content-wrapper">
        
        <?php 
        // Hanya tampilkan navbar jika sudah login
        if ($is_logged_in): 
        ?>
        <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom shadow-sm">
            <div class="container-fluid">
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <a class="navbar-brand ms-2 ms-lg-0" href="<?php echo $base_url; ?>dashboard.php"><?php echo htmlspecialchars($judul_halaman); ?></a>
                
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo $foto_path_navbar; ?>" alt="Foto" class="navbar-avatar">
                                <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Pengguna'; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo $base_url; ?>modules/user/profil.php">Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo $base_url; ?>logout.php">Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <?php endif; // Akhir dari 'if ($is_logged_in)' ?>

        <main class="p-4 bg-light flex-grow-1">