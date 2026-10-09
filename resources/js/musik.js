// Musik "Bad Apple!!" dari file public/audio/bad-apple.mp3 (elemen <audio> di partials/pemutar-musik).
// Berkat Turbo, elemen audio tidak diganti saat pindah menu (data-turbo-permanent),
// sehingga lagu terus berjalan. Bila halaman dimuat ulang penuh, posisi lagu
// diambil dari sessionStorage agar bisa dilanjutkan.

const KUNCI_DETIK = 'musik-detik';
const KUNCI_MAIN = 'musik-main';

function simpan(kunci, nilai) {
    try {
        sessionStorage.setItem(kunci, nilai);
    } catch (e) {
        // sessionStorage bisa diblokir; musik tetap bisa diputar.
    }
}

function baca(kunci) {
    try {
        return sessionStorage.getItem(kunci);
    } catch (e) {
        return null;
    }
}

const musik = () => document.getElementById('musik-bad-apple');

// Ikon tombol mengikuti status lagu (dipanggil juga setiap halaman baru tampil)
function perbaruiTombol() {
    const m = musik();
    const main = m ? !m.paused : false;
    document.querySelectorAll('[data-musik-toggle]').forEach((t) => {
        t.setAttribute('aria-pressed', String(main));
        t.querySelector('[data-ikon-musik]').classList.toggle('hidden', main);
        t.querySelector('[data-ikon-jeda]').classList.toggle('hidden', !main);
    });
}

function putar() {
    const m = musik();
    document.getElementById('audio-suasana')?.pause(); // suara suasana login dijeda agar tidak bertabrakan
    m.volume = 0.7;
    return m.play();
}

// Memasang event pada elemen audio (sekali per elemen)
function siapkanAudio() {
    const m = musik();
    if (!m || m.dataset.siap) {
        return;
    }
    m.dataset.siap = '1';
    m.addEventListener('play', () => { perbaruiTombol(); simpan(KUNCI_MAIN, '1'); });
    m.addEventListener('pause', () => { perbaruiTombol(); simpan(KUNCI_MAIN, '0'); });

    // Halaman dimuat ulang penuh: lanjutkan dari detik terakhir
    const detik = Number(baca(KUNCI_DETIK)) || 0;
    if (detik > 0) {
        m.addEventListener('loadedmetadata', () => { m.currentTime = detik; }, { once: true });
    }
    if (baca(KUNCI_MAIN) === '1') {
        // Browser bisa menolak memutar otomatis; bila begitu, cukup klik tombol musik lagi.
        putar().catch(() => {});
    }
}

export function pasangMusik() {
    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-musik-toggle]')) {
            return;
        }
        const m = musik();
        if (m.paused) {
            putar().catch(() => alert('Musik tidak dapat diputar.'));
        } else {
            m.pause();
        }
    });

    // Suara suasana login diputar -> musik dijeda
    document.addEventListener('suasana-diputar', () => musik()?.pause());

    // Simpan posisi lagu saat halaman ditutup / dimuat ulang
    window.addEventListener('pagehide', () => {
        const m = musik();
        if (m && !m.paused) {
            simpan(KUNCI_DETIK, String(m.currentTime));
        }
    });

    document.addEventListener('turbo:load', () => {
        siapkanAudio();
        perbaruiTombol();
    });
}
