        </main>
        
        <footer class="bg-light border-top p-3 text-center mt-auto">
            <div class="container-fluid">
                <p class="mb-0 text-muted">Sistem Penilaian SMK &copy; <?php echo date('Y'); ?></p>
            </div>
        </footer>

    </div> <!-- Penutup .content-wrapper -->
</div> <!-- Penutup .main-wrapper -->


<!-- Bootstrap JS Bundle (wajib ada untuk fitur interaktif seperti dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Opsional: JS Kustom Anda (jika ada, pastikan isinya kosong jika tidak digunakan) -->
<script src="assets/js/script.js"></script>

<!-- ======================================================== -->
<!-- SCRIPT UNTUK FORM TAMBAH USER DINAMIS -->
<!-- ======================================================== -->
<script>
// Cek apakah elemen dengan id 'role' ada di halaman ini.
// Ini untuk memastikan skrip hanya berjalan di halaman tambah_user.php
if (document.getElementById('role')) {
    // Jalankan skrip setelah seluruh halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        const roleDropdown = document.getElementById('role');
        
        function toggleUserSelection() {
            const role = roleDropdown.value;
            const pilihanGuru = document.getElementById('pilihan_guru');
            const pilihanSiswa = document.getElementById('pilihan_siswa');
            const inputManual = document.getElementById('input_manual');
            const inputPassword = document.getElementById('input_password');

            // Sembunyikan semua field dinamis dan reset atribut 'required'
            pilihanGuru.style.display = 'none';
            document.getElementById('id_guru').required = false;

            pilihanSiswa.style.display = 'none';
            document.getElementById('id_siswa').required = false;

            inputManual.style.display = 'none';
            document.getElementById('username').required = false;

            inputPassword.style.display = 'none';
            document.getElementById('password').required = false;

            // Tampilkan field yang sesuai berdasarkan role yang dipilih
            if (role === 'guru') {
                pilihanGuru.style.display = 'block';
                inputPassword.style.display = 'block';
                document.getElementById('id_guru').required = true;
                document.getElementById('password').required = true;
            } else if (role === 'siswa') {
                pilihanSiswa.style.display = 'block';
                inputPassword.style.display = 'block';
                document.getElementById('id_siswa').required = true;
                document.getElementById('password').required = true;
            } else if (role === 'admin' || role === 'operator') {
                inputManual.style.display = 'block';
                inputPassword.style.display = 'block';
                document.getElementById('username').required = true;
                document.getElementById('password').required = true;
            }
        }

        // Tambahkan 'event listener' ke dropdown role
        roleDropdown.addEventListener('change', toggleUserSelection);
    });
}
</script>

</body>
</html>