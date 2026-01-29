<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - Sistem Penilaian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet" />

    <style>
        body { 
            background-image: url('assets/img/bg-login.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center; 
            justify-content: center;
        }

        .login-card {
            max-width: 420px;
            width: 100%;
            border: none;
            border-radius: 15px;
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .login-header {
            padding: 30px 20px 20px;
            text-align: center;
        }
        
        .login-header img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 15px;
        }

        .login-header h3 {
            font-weight: 700;
            color: #333;
        }
        
        .login-body {
            padding: 20px 30px 30px;
        }

        .btn-custom {
            background-color: #007bff;
            border: none;
            transition: background-color 0.3s;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="assets/img/logo smk alhawari.jpeg" alt="Logo SMK IT AL-HAWARI">
        <h3>SMK IT AL-HAWARI</h3>
    </div>

    <div class="login-body">
        <?php 
        // Notifikasi error
        if(isset($_GET['error'])) {
            echo '<div class="alert alert-danger text-center py-2" role="alert">
                    Username atau Password salah!
                  </div>';
        }
        // Notifikasi logout
        if(isset($_GET['logout'])) {
            echo '<div class="alert alert-success text-center py-2" role="alert">
                    Anda telah berhasil logout.
                  </div>';
        }
        // Notifikasi reset password sukses
        if(isset($_GET['status']) && $_GET['status'] == 'reset_sukses') {
            echo '<div class="alert alert-success text-center py-2" role="alert">Password berhasil direset! Silakan login.</div>';
        }
        ?>

        <form action="proses_login.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-custom btn-lg text-white">
                    <i class="fas fa-sign-in-alt me-2"></i> Login
                </button>
            </div>
            

        </form>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>