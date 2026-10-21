// Interaksi form yang memakai rincian kondisi (Baik / Rusak Ringan / Rusak Berat):
//  - total jumlah dihitung otomatis (komponen <x-input-kondisi>)
//  - form barang keluar: isian menyesuaikan jenis (Peminjaman / Pemindahan / Pemusnahan)
//    dan menampilkan stok tersedia di lokasi asal.
// Memakai event delegation agar tetap berfungsi di halaman yang dimuat Turbo dan di dalam popup.

const KONDISI = ['Baik', 'Rusak Ringan', 'Rusak Berat'];

function hitungTotal(wadah) {
    const total = [...wadah.querySelectorAll('[data-jumlah-kondisi]')]
        .reduce((jumlah, input) => jumlah + (parseInt(input.value, 10) || 0), 0);
    const tempat = wadah.querySelector('[data-total-kondisi]');
    if (tempat) {
        tempat.textContent = total;
    }
}

function aturFormKeluar(form) {
    const jenis = form.querySelector('[data-pilih-jenis]:checked')?.value;

    // Tampilkan isian khusus jenis tertentu (lokasi tujuan / rencana kembali)
    form.querySelectorAll('[data-hanya-jenis]').forEach((el) => {
        const tampil = el.dataset.hanyaJenis === jenis;
        el.classList.toggle('hidden', !tampil);
        el.querySelectorAll('input, select').forEach((input) => { input.required = tampil; });
    });

    // Pemusnahan hanya untuk barang Rusak Berat
    form.querySelectorAll('[data-jumlah-kondisi]').forEach((input) => {
        const kunci = jenis === 'Pemusnahan' && input.dataset.jumlahKondisi !== 'Rusak Berat';
        input.readOnly = kunci;
        input.classList.toggle('opacity-50', kunci);
        if (kunci) {
            input.value = 0;
        }
    });

    const label = form.querySelector('[data-label-pemohon]');
    if (label) {
        label.textContent = jenis === 'Peminjaman' ? 'Peminjam' : 'Penanggung Jawab';
    }

    tampilkanStok(form);
    const wadah = form.querySelector('[data-input-kondisi]');
    if (wadah) {
        hitungTotal(wadah);
    }
}

// Info stok per kondisi untuk barang + lokasi asal yang dipilih
function tampilkanStok(form) {
    const info = form.querySelector('[data-info-stok]');
    const pilihBarang = form.querySelector('[data-pilih-barang]');
    const idLokasi = form.querySelector('[data-pilih-lokasi-asal]')?.value;
    if (!info || !pilihBarang?.value || !idLokasi) {
        info?.classList.add('hidden');
        return;
    }

    const peta = JSON.parse(form.querySelector('[data-peta-stok]')?.textContent || '{}');
    const stok = peta[pilihBarang.value]?.[idLokasi] ?? {};
    const satuan = pilihBarang.selectedOptions[0]?.dataset.satuan ?? '';

    info.textContent = 'Tersedia di lokasi ini: ' + KONDISI.map((k) => `${stok[k] ?? 0} ${k}`).join(' · ') + ` (${satuan})`;
    info.classList.remove('hidden');

    // Batas input mengikuti stok yang tersedia
    form.querySelectorAll('[data-jumlah-kondisi]').forEach((input) => {
        input.max = stok[input.dataset.jumlahKondisi] ?? 0;
    });
}

function aturSemuaFormKeluar() {
    document.querySelectorAll('[data-form-keluar]').forEach(aturFormKeluar);
}

export function pasangFormKondisi() {
    document.addEventListener('input', (event) => {
        const wadah = event.target.closest('[data-input-kondisi]');
        if (wadah && event.target.matches('[data-jumlah-kondisi]')) {
            hitungTotal(wadah);
        }
    });

    document.addEventListener('change', (event) => {
        const form = event.target.closest('[data-form-keluar]');
        if (form) {
            aturFormKeluar(form);
        }
    });

    // Halaman baru (Turbo) atau isi popup baru dimuat
    document.addEventListener('turbo:load', aturSemuaFormKeluar);
    document.addEventListener('turbo:frame-load', aturSemuaFormKeluar);
}
