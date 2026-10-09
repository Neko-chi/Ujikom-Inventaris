{{-- Musik "Bad Apple!!" (file public/audio/bad-apple.mp3), dikendalikan tombol musik lewat resources/js/musik.js.
    data-turbo-permanent: elemen ini tidak diganti saat pindah halaman, sehingga lagu tidak terputus --}}
<audio id="musik-bad-apple" data-turbo-permanent loop preload="none" src="{{ asset('audio/bad-apple.mp3') }}"></audio>
