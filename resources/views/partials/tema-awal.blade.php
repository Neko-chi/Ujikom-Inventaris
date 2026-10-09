{{--
    Dijalankan paling awal (sebelum CSS tampil) agar halaman tidak berkedip.
    Bila pengguna sudah memilih tema di tab ini, pakai pilihan itu (sessionStorage).
    Bila belum, tema mengikuti jam: malam (18.00-03.59, sama dengan App\Support\Suasana) = gelap, selain itu terang.
--}}
<script>
    (function () {
        var tema = null;
        try { tema = sessionStorage.getItem('tema'); } catch (e) {}
        if (!tema) {
            var jam = new Date().getHours();
            tema = (jam >= 18 || jam < 4) ? 'gelap' : 'terang';
        }
        if (tema === 'gelap') {
            document.documentElement.classList.add('dark');
        }
        // Sidebar yang ditutup lewat tombol burger tetap tertutup setelah pindah halaman
        try {
            if (localStorage.getItem('sidebar-tutup') === '1') {
                document.documentElement.classList.add('sidebar-tutup');
            }
        } catch (e) {}
    })();
</script>
