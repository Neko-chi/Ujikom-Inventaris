import './bootstrap';

// Font dibundel bersama aplikasi agar tampilan tetap sama walau tanpa internet.
import '@fontsource-variable/plus-jakarta-sans';

// Turbo: pindah menu dan kirim form tanpa memuat ulang seluruh halaman.
// Isi <body> diganti, sedangkan <html> (tema), musik, dan script tetap berjalan.
import * as Turbo from '@hotwired/turbo';

import { pasangTombolTema } from './tema';
import { pasangSuasanaLogin } from './suasana';
import { pasangMusik } from './musik';
import { pasangPopup } from './popup';

window.Turbo = Turbo;

// ---- Dipasang sekali saja (memakai event delegation di document) ----
pasangTombolTema();
pasangMusik();
pasangPopup();

const html = document.documentElement;

function simpanSidebar(tutup) {
    try {
        localStorage.setItem('sidebar-tutup', tutup ? '1' : '0');
    } catch (e) {
        // localStorage bisa diblokir; posisi sidebar tetap berlaku sampai halaman dimuat ulang.
    }
}

function bukaSidebarHp(buka) {
    document.getElementById('sidebar')?.classList.toggle('-translate-x-full', !buka);
    document.getElementById('sidebar-overlay')?.classList.toggle('hidden', !buka);
}

document.addEventListener('click', (event) => {
    const t = event.target;

    // Tombol burger: di layar besar menutup/membuka sidebar, di HP membuka menu geser
    if (t.closest('[data-sidebar-toggle]')) {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            simpanSidebar(html.classList.toggle('sidebar-tutup'));
        } else {
            bukaSidebarHp(true);
        }
        return;
    }
    if (t.closest('[data-sidebar-tutup]')) {
        bukaSidebarHp(false);
        return;
    }

    // Tutup pesan notifikasi
    const tutupPesan = t.closest('[data-dismiss]');
    if (tutupPesan) {
        tutupPesan.closest('[data-alert]')?.remove();
        return;
    }

    // Tampilkan / sembunyikan password
    const tombolPassword = t.closest('[data-toggle-password]');
    if (tombolPassword) {
        const input = document.getElementById(tombolPassword.dataset.togglePassword);
        const tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        tombolPassword.querySelector('[data-icon-show]')?.classList.toggle('hidden', tampil);
        tombolPassword.querySelector('[data-icon-hide]')?.classList.toggle('hidden', !tampil);
    }
});

// Pratinjau gambar sebelum diunggah (komponen <x-unggah-gambar>)
document.addEventListener('change', (event) => {
    const input = event.target.closest('[data-pratinjau]');
    if (!input) {
        return;
    }
    const nama = input.dataset.pratinjau;
    const file = input.files[0];
    const wadah = input.closest('form') ?? document;
    const gambar = wadah.querySelector(`#pratinjau-${nama}`);
    const kosong = wadah.querySelector(`#kosong-${nama}`);
    const namaFile = wadah.querySelector(`#nama-file-${nama}`);

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

// ---- Dijalankan setiap kali halaman baru tampil (termasuk lewat Turbo) ----
document.addEventListener('turbo:load', () => {
    bukaSidebarHp(false);
    pasangSuasanaLogin();
});
