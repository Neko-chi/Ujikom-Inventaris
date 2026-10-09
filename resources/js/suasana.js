// Halaman login: salam, jam berjalan, warna cahaya apel, dan suara suasana (audio)
// yang menyesuaikan waktu pagi / siang / sore / malam.
// Data tiap suasana (jam mulai, salam, kalimat, file audio) dikirim dari PHP
// (App\Support\Suasana::DAFTAR) lewat <script id="data-suasana"> berformat JSON.

const FORMAT_JAM = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' });
const FORMAT_TANGGAL = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

// Sama dengan Suasana::dariJam() di PHP: ambil suasana terakhir yang jam mulainya sudah lewat.
export function waktuDariJam(jam, daftar) {
    let hasil = 'malam';
    for (const [kunci, data] of Object.entries(daftar)) {
        if (jam >= data.mulai) {
            hasil = kunci;
        }
    }
    return hasil;
}

// Pewaktu jam; dihentikan saat pindah halaman (Turbo) agar tidak berjalan ganda
let pewaktu = null;

export function pasangSuasanaLogin() {
    clearInterval(pewaktu);
    const halaman = document.querySelector('[data-suasana]');
    if (!halaman) {
        return; // bukan halaman login
    }

    const daftar = JSON.parse(document.getElementById('data-suasana').textContent);
    const audio = document.getElementById('audio-suasana');
    const tombolAudio = document.querySelector('[data-audio-toggle]');
    const elemen = {
        salam: document.querySelector('[data-salam]'),
        kalimat: document.querySelector('[data-kalimat]'),
        labelAudio: document.querySelector('[data-label-audio]'),
        jam: document.querySelector('[data-jam]'),
        tanggal: document.querySelector('[data-tanggal]'),
    };

    let waktuAktif = halaman.dataset.waktu;

    // Mengganti suasana: atribut data-waktu (warna cahaya apel), salam, kalimat, dan file audio
    function terapkan(waktu) {
        if (waktu === waktuAktif) {
            return;
        }
        waktuAktif = waktu;
        const data = daftar[waktu];

        halaman.dataset.waktu = waktu;
        elemen.salam.textContent = data.salam;
        elemen.kalimat.textContent = data.kalimat;

        // Bila suara sedang diputar, langsung lanjut dengan suara suasana yang baru
        const sedangDiputar = !audio.paused;
        audio.src = data.audio;
        if (sedangDiputar) {
            putar();
        }
        perbaruiTombolAudio();
    }

    function perbaruiJam() {
        const sekarang = new Date();
        elemen.jam.textContent = FORMAT_JAM.format(sekarang);
        elemen.tanggal.textContent = FORMAT_TANGGAL.format(sekarang);
        terapkan(waktuDariJam(sekarang.getHours(), daftar));
    }

    // ---- Audio suasana ----
    function putar() {
        document.dispatchEvent(new Event('suasana-diputar')); // musik Bad Apple dijeda
        audio.volume = 0;
        audio.play().then(() => {
            // Volume naik perlahan (fade in) agar tidak mengagetkan
            let v = 0;
            const naik = setInterval(() => {
                v = Math.min(0.6, v + 0.05);
                audio.volume = v;
                if (v >= 0.6) clearInterval(naik);
            }, 80);
        }).catch(perbaruiTombolAudio); // browser menolak memutar, misalnya file gagal dimuat
    }

    function perbaruiTombolAudio() {
        const main = !audio.paused;
        tombolAudio.setAttribute('aria-pressed', String(main));
        tombolAudio.querySelector('[data-ikon-putar]').classList.toggle('hidden', main);
        tombolAudio.querySelector('[data-ikon-henti]').classList.toggle('hidden', !main);
        elemen.labelAudio.textContent = main ? 'Matikan suara' : `Putar suara ${waktuAktif}`;
    }

    tombolAudio.addEventListener('click', () => (audio.paused ? putar() : audio.pause()));
    audio.addEventListener('play', perbaruiTombolAudio);
    audio.addEventListener('pause', perbaruiTombolAudio);

    perbaruiJam();
    pewaktu = setInterval(perbaruiJam, 1000);
}
