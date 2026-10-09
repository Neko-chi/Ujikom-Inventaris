// Mode terang / gelap.
// Class "dark" di <html> sudah dipasang lebih awal oleh partials/tema-awal.blade.php;
// file ini menangani tombol ganti tema dan saat halaman dicetak.

const html = document.documentElement;

// Pilihan disimpan di sessionStorage: berlaku selama tab browser terbuka.
// Saat aplikasi dibuka lagi, tema kembali mengikuti jam (lihat partials/tema-awal.blade.php).
function simpanTema(tema) {
    try {
        sessionStorage.setItem('tema', tema);
    } catch (e) {
        // sessionStorage bisa diblokir; tema tetap berlaku sampai halaman dimuat ulang.
    }
}

function tukarTema() {
    const gelap = html.classList.toggle('dark');
    simpanTema(gelap ? 'gelap' : 'terang');
}

/**
 * Mengganti tema. Bila browser mendukung View Transitions API, tema baru muncul
 * sebagai lingkaran yang membesar dari titik tombol yang diklik.
 * Bila tidak didukung (atau pengguna memilih "kurangi animasi"), warna berganti dengan transisi biasa.
 */
export function gantiTema(tombol) {
    const kurangiAnimasi = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!document.startViewTransition || kurangiAnimasi) {
        html.classList.add('ganti-tema');
        tukarTema();
        setTimeout(() => html.classList.remove('ganti-tema'), 350);
        return;
    }

    // Titik pusat lingkaran = tengah tombol; jari-jari = jarak ke sudut layar terjauh
    const kotak = tombol.getBoundingClientRect();
    const x = kotak.left + kotak.width / 2;
    const y = kotak.top + kotak.height / 2;
    const r = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

    const transisi = document.startViewTransition(tukarTema);
    transisi.ready.then(() => {
        html.animate(
            { clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${r}px at ${x}px ${y}px)`] },
            { duration: 750, easing: 'cubic-bezier(0.65, 0, 0.35, 1)', pseudoElement: '::view-transition-new(root)' },
        );
    });
}

export function pasangTombolTema() {
    // Event delegation: tetap berfungsi walau isi halaman diganti oleh Turbo
    document.addEventListener('click', (event) => {
        const tombol = event.target.closest('[data-ganti-tema]');
        if (tombol) {
            gantiTema(tombol);
        }
    });

    // Cetak selalu dalam mode terang agar hemat tinta dan mudah dibaca
    let gelapSebelumCetak = false;
    window.addEventListener('beforeprint', () => {
        gelapSebelumCetak = html.classList.contains('dark');
        html.classList.remove('dark');
    });
    window.addEventListener('afterprint', () => html.classList.toggle('dark', gelapSebelumCetak));
}
