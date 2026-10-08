import './bootstrap';

// Font dibundel bersama aplikasi agar tampilan tetap sama walau tanpa internet.
import '@fontsource-variable/plus-jakarta-sans';

// Interaksi kecil di sisi browser (tanpa library tambahan).
document.addEventListener('DOMContentLoaded', () => {
    // Buka / tutup sidebar pada layar kecil
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggleSidebar = (buka) => {
        sidebar?.classList.toggle('-translate-x-full', !buka);
        overlay?.classList.toggle('hidden', !buka);
    };
    document.querySelectorAll('[data-sidebar-open]').forEach((el) => el.addEventListener('click', () => toggleSidebar(true)));
    document.querySelectorAll('[data-sidebar-close]').forEach((el) => el.addEventListener('click', () => toggleSidebar(false)));

    // Tutup pesan notifikasi
    document.querySelectorAll('[data-dismiss]').forEach((tombol) => {
        tombol.addEventListener('click', () => tombol.closest('[data-alert]')?.remove());
    });

    // Pratinjau gambar sebelum diunggah (komponen <x-unggah-gambar>)
    document.querySelectorAll('[data-pratinjau]').forEach((input) => {
        input.addEventListener('change', () => {
            const nama = input.dataset.pratinjau;
            const file = input.files[0];
            const gambar = document.getElementById(`pratinjau-${nama}`);
            const kosong = document.getElementById(`kosong-${nama}`);
            const namaFile = document.getElementById(`nama-file-${nama}`);

            if (!file) {
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran gambar melebihi 2 MB. Silakan pilih gambar yang lebih kecil.');
                input.value = '';
                return;
            }

            gambar.src = URL.createObjectURL(file);
            gambar.classList.remove('hidden');
            kosong?.classList.add('hidden');
            if (namaFile) {
                namaFile.textContent = file.name;
            }
        });
    });

    // Tampilkan / sembunyikan password
    document.querySelectorAll('[data-toggle-password]').forEach((tombol) => {
        tombol.addEventListener('click', () => {
            const input = document.getElementById(tombol.dataset.togglePassword);
            const tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            tombol.querySelector('[data-icon-show]')?.classList.toggle('hidden', tampil);
            tombol.querySelector('[data-icon-hide]')?.classList.toggle('hidden', !tampil);
        });
    });
});
