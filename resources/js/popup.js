// Popup (tengah) dan panel kanan memakai elemen <dialog> berisi Turbo Frame.
// Tautan dengan data-turbo-frame="modal" atau "laci" memuat halaman ke dalam frame itu
// tanpa pindah halaman; layout hanya mengirim isi halaman (lihat layouts/app.blade.php).

const DIALOG = { modal: 'dialog-modal', laci: 'dialog-laci' };

// Bagian (#id) yang dituju tautan popup terakhir, contoh "#catat-pengembalian"
let bagianTujuan = null;

function dialogDari(frame) {
    return frame ? document.getElementById(DIALOG[frame.id]) : null;
}

function kosongkan(dialog) {
    const frame = dialog.querySelector('turbo-frame');
    frame?.removeAttribute('src');
    // replaceChildren(): mengosongkan isi tanpa menjalankan parser HTML
    frame?.replaceChildren();
}

export function tutupSemuaPopup() {
    Object.values(DIALOG).forEach((id) => {
        const dialog = document.getElementById(id);
        if (dialog?.open) {
            dialog.close();
        }
    });
}

// Membuat <turbo-frame> di dalam setiap dialog (dipanggil setiap halaman baru tampil)
function siapkanFrame() {
    Object.entries(DIALOG).forEach(([idFrame, idDialog]) => {
        const dialog = document.getElementById(idDialog);
        if (dialog && !dialog.querySelector('turbo-frame')) {
            const frame = document.createElement('turbo-frame');
            frame.id = idFrame;
            dialog.appendChild(frame);
        }
    });
}

export function pasangPopup() {
    document.addEventListener('turbo:load', siapkanFrame);

    // Permintaan dari dalam popup: Referer diisi alamat popup (bukan halaman di belakangnya),
    // sehingga bila validasi gagal Laravel kembali ke form di dalam popup.
    // Alamat halaman di belakang popup dikirim lewat header X-Halaman-Asal.
    document.addEventListener('turbo:before-fetch-request', (event) => {
        const frame = event.target.closest?.('turbo-frame');
        if (!frame || !DIALOG[frame.id]) {
            return;
        }
        const { fetchOptions } = event.detail;
        if (frame.src) {
            fetchOptions.referrer = frame.src;
        }
        fetchOptions.headers['X-Halaman-Asal'] = window.location.href;
    });

    // Isi frame selesai dimuat -> tampilkan dialognya
    document.addEventListener('turbo:frame-load', (event) => {
        const dialog = dialogDari(event.target);
        if (dialog && !dialog.open) {
            dialog.showModal();
        }
        // Tautan dengan #bagian (mis. tombol "Kembalikan" -> #catat-pengembalian): gulir ke bagian itu
        if (dialog && bagianTujuan) {
            event.target.querySelector(bagianTujuan)?.scrollIntoView({ block: 'start' });
        }
        bagianTujuan = null;
    });

    // Turbo membuang #bagian dari alamat frame, jadi diingat dari tautan yang diklik
    document.addEventListener('click', (event) => {
        const tautan = event.target.closest('a[data-turbo-frame]');
        if (tautan && DIALOG[tautan.dataset.turboFrame]) {
            bagianTujuan = new URL(tautan.href, window.location.href).hash || null;
        }
    }, true);

    // Setelah form berhasil disimpan, server mengarahkan ke halaman daftar yang tidak punya
    // isi popup. Tutup popup lalu tampilkan halaman itu (beserta pesan suksesnya).
    document.addEventListener('turbo:frame-missing', (event) => {
        if (!DIALOG[event.target.id]) {
            return;
        }
        event.preventDefault();
        tutupSemuaPopup();
        event.detail.visit(event.detail.response);
    });

    // Tombol tutup (X), "Kembali", dan "Batal": di dalam popup cukup menutup popup
    document.addEventListener('click', (event) => {
        const tombol = event.target.closest('[data-tutup-popup]');
        const dialog = tombol?.closest('dialog');
        if (dialog) {
            event.preventDefault();
            dialog.close();
            return;
        }
        // Klik di area gelap di luar kotak popup menutup popup. Browser yang mendukung
        // closedby="any" (lihat layout) melakukannya sendiri; ini cadangan untuk Safari.
        if (!('closedBy' in HTMLDialogElement.prototype) && event.target.matches('dialog.popup')) {
            const kotak = event.target.getBoundingClientRect();
            const diDalamKotak = kotak.top <= event.clientY && event.clientY <= kotak.bottom
                && kotak.left <= event.clientX && event.clientX <= kotak.right;
            if (!diDalamKotak) {
                event.target.close();
            }
        }
    });

    // Saat dialog ditutup (termasuk tombol Esc), kosongkan isinya
    document.addEventListener('close', (event) => {
        if (event.target.matches?.('dialog.popup')) {
            kosongkan(event.target);
        }
    }, true);

    // Pindah halaman -> pastikan popup tertutup
    document.addEventListener('turbo:before-visit', tutupSemuaPopup);
}
