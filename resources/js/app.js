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
