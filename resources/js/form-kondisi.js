// Interaksi form yang memakai rincian kondisi (Baik / Rusak Ringan / Rusak Berat):
//  - total jumlah dihitung otomatis (komponen <x-input-kondisi>)
//  - form barang keluar: isian menyesuaikan jenis (Peminjaman / Pemindahan / Pemusnahan)
//    dan menampilkan stok tersedia di lokasi asal.
//  - form pemeriksaan kondisi: menampilkan stok tercatat dan membatasi jumlah sesuai kondisi di data.
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

// Pilihan lokasi menampilkan stok barang yang dipilih di tiap lokasi, sehingga Operator
// tidak perlu menebak letak barangnya. Lokasi tanpa stok barang itu tidak dapat dipilih.
function perbaruiPilihanLokasi(form, peta) {
    const pilihBarang = form.querySelector('[data-pilih-barang]');
    const pilihLokasi = form.querySelector('[data-pilih-lokasi-asal]');
    if (!pilihBarang || !pilihLokasi) {
        return;
    }

    const satuan = pilihBarang.selectedOptions[0]?.dataset.satuan ?? '';
    const stokBarang = peta[pilihBarang.value] ?? {};
    const adaStok = [];

    [...pilihLokasi.options].forEach((opsi) => {
        if (!opsi.value) {
            opsi.textContent = pilihBarang.value ? 'Pilih lokasi' : 'Pilih barang terlebih dahulu';
            return;
        }
        opsi.dataset.label ??= opsi.textContent.trim();
        if (!pilihBarang.value) {
            opsi.textContent = opsi.dataset.label;
            opsi.disabled = false;
            return;
        }

        const stok = stokBarang[opsi.value] ?? {};
        const total = KONDISI.reduce((jumlah, k) => jumlah + (stok[k] ?? 0), 0);
        const rincian = KONDISI.filter((k) => stok[k]).map((k) => `${stok[k]} ${k}`).join(', ');
        opsi.textContent = total > 0
            ? `${opsi.dataset.label} — stok ${total} ${satuan} (${rincian})`
            : `${opsi.dataset.label} — tidak ada stok`;
        opsi.disabled = total === 0;
        if (total > 0) {
            adaStok.push(opsi);
        }
    });

    // Lokasi terpilih tidak punya stok barang ini: kosongkan; bila hanya ada satu lokasi, pilih otomatis
    if (pilihLokasi.selectedOptions[0]?.disabled) {
        pilihLokasi.value = '';
    }
    if (!pilihLokasi.value && adaStok.length === 1) {
        pilihLokasi.value = adaStok[0].value;
    }
}

// Info stok per kondisi untuk barang + lokasi asal yang dipilih
function tampilkanStok(form) {
    const info = form.querySelector('[data-info-stok]');
    const pilihBarang = form.querySelector('[data-pilih-barang]');
    const peta = JSON.parse(form.querySelector('[data-peta-stok]')?.textContent || '{}');
    perbaruiPilihanLokasi(form, peta);

    const idLokasi = form.querySelector('[data-pilih-lokasi-asal]')?.value;
    if (!info || !pilihBarang?.value || !idLokasi) {
        info?.classList.add('hidden');
        return;
    }

    const stok = peta[pilihBarang.value]?.[idLokasi] ?? {};
    const satuan = pilihBarang.selectedOptions[0]?.dataset.satuan ?? '';

    const awalan = form.matches('[data-form-pemeriksaan]') ? 'Tercatat di lokasi ini: ' : 'Tersedia di lokasi ini: ';
    info.textContent = awalan + KONDISI.map((k) => `${stok[k] ?? 0} ${k}`).join(' · ') + ` (${satuan})`;
    info.classList.remove('hidden');

    // Batas input mengikuti stok yang tersedia
    form.querySelectorAll('[data-jumlah-kondisi]').forEach((input) => {
        input.max = stok[input.dataset.jumlahKondisi] ?? 0;
    });
    const jumlahPemeriksaan = form.querySelector('[data-jumlah-pemeriksaan]');
    if (jumlahPemeriksaan) {
        jumlahPemeriksaan.max = stok[form.querySelector('[data-kondisi-asal]')?.value] ?? 0;
    }
}

function aturSemuaFormKeluar() {
    document.querySelectorAll('[data-form-keluar]').forEach(aturFormKeluar);
    document.querySelectorAll('[data-form-pemeriksaan]').forEach(tampilkanStok);
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
        const formPemeriksaan = event.target.closest('[data-form-pemeriksaan]');
        if (formPemeriksaan) {
            tampilkanStok(formPemeriksaan);
        }
    });

    // Halaman baru (Turbo) atau isi popup baru dimuat
    document.addEventListener('turbo:load', aturSemuaFormKeluar);
    document.addEventListener('turbo:frame-load', aturSemuaFormKeluar);
}
