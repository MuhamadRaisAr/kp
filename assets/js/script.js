$(document).ready(function() {
    if ($('#kelas').length) {
        const kelasDropdown = $('#kelas');
        const mapelDropdown = $('#mapel');

        // Fungsi utama untuk memuat mata pelajaran
        function loadMataPelajaran() {
            const idKelas = kelasDropdown.val();
            mapelDropdown.empty().append('<option value="">-- Memuat... --</option>').prop('disabled', true);

            if (idKelas) {
                $.ajax({
                    url: 'api_get_mapel.php',
                    type: 'GET',
                    data: { id_kelas: idKelas },
                    dataType: 'json',
                    success: function(response) {
                        mapelDropdown.empty();
                        if (response.length > 0) {
                            mapelDropdown.prop('disabled', false);
                            mapelDropdown.append('<option value="">-- Pilih Mata Pelajaran --</option>');
                            
                            $.each(response, function(index, mapel) {
                                const option = $('<option>', {
                                    value: mapel.id_mapel,
                                    text: mapel.nama_mapel
                                });

                                // Logika "mengingat": Jika mapel ini sama dengan yang dipilih sebelumnya,
                                // buat dia terpilih lagi.
                                if (typeof selectedMapelFromPHP !== 'undefined' && mapel.id_mapel == selectedMapelFromPHP) {
                                    option.prop('selected', true);
                                }
                                mapelDropdown.append(option);
                            });
                        } else {
                            mapelDropdown.append('<option value="">-- Tidak ada mapel --</option>').prop('disabled', true);
                        }
                    },
                    error: function() {
                        mapelDropdown.empty().append('<option value="">-- Gagal memuat --</option>').prop('disabled', true);
                        alert('Gagal menghubungi server.');
                    }
                });
            } else {
                mapelDropdown.empty().append('<option value="">-- Pilih Kelas Dulu --</option>').prop('disabled', true);
            }
        }

        // Pasang pemicu 'change'
        kelasDropdown.on('change', loadMataPelajaran);

        // Pemicu saat halaman pertama kali dimuat:
        // Jika dropdown kelas SUDAH punya nilai (setelah reload), jalankan fungsinya.
        if (kelasDropdown.val()) {
            loadMataPelajaran();
        } else {
            mapelDropdown.prop('disabled', true);
        }
    }
});