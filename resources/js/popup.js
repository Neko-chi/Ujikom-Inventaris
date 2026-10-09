// Popup (tengah) dan panel kanan memakai elemen <dialog> berisi Turbo Frame.
// Tautan dengan data-turbo-frame="modal" atau "laci" memuat halaman ke dalam frame itu
// tanpa pindah halaman; layout hanya mengirim isi halaman (lihat layouts/app.blade.php).

const DIALOG = { modal: 'dialog-modal', laci: 'dialog-laci' };

function dialogDari(frame) {
    return frame ? document.getElementById(DIALOG[frame.id]) : null;
}

function kosongkan(dialog) {
    const frame = dialog.querySelector('turbo-frame');
    frame?.removeAttribute('src');
    if (frame) {
        frame.innerHTML = '';
    }
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
    });

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
        // Klik di area gelap di luar kotak popup juga menutup popup
        if (event.target.matches('dialog.popup')) {
            event.target.close();
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
