<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Inventaris Gudang Sekolah</title>
    @include('partials.tema-awal')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    Halaman login bergaya siluet monokrom: mode terang = siluet hitam di latar putih,
    mode gelap = siluet putih di latar hitam (warna diatur app.css bagian HALAMAN LOGIN).
    Atribut data-waktu (pagi/siang/sore/malam) mengatur warna cahaya apel dan suara suasana.
--}}
<body class="login font-sans antialiased" data-suasana data-waktu="{{ $suasana }}">
    <script type="application/json" id="data-suasana">@json($daftarSuasana)</script>

    {{-- Seni latar: cahaya lembut, partikel melayang, dan siluet --}}
    <div class="panggung" aria-hidden="true">
        <div class="sorot"></div>
        <p class="teks-raksasa">Gudang<br>Sekolah</p>
        <div class="apel-apel">
            @foreach ([[34, 0, 11, 22], [48, -4, 14, 16], [58, -8, 12, 26], [42, -10, 16, 14]] as [$kiri, $tunda, $durasi, $ukuran])
                <svg class="apel-jatuh" style="left: {{ $kiri }}%; width: {{ $ukuran }}px; animation-delay: {{ $tunda }}s; animation-duration: {{ $durasi }}s" viewBox="0 0 24 24">
                    <path d="M12 7.2c-1.6-1.2-4-1.5-5.7-.3C3.8 8.6 4 12.6 5.5 15.6c1.4 2.8 3.4 5 5 4.6.7-.2 1-.6 1.5-.6s.8.4 1.5.6c1.6.4 3.6-1.8 5-4.6 1.5-3 1.7-7-.8-8.7-1.7-1.2-4.1-.9-5.7.3Z" />
                    <path d="M12 7.2c-.1-2 .5-3.5 1.8-4.6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
                    <path d="M13.2 5.4c1-1.9 3.2-2.5 4.6-2-.5 1.7-2.6 2.8-4.6 2Z" />
                </svg>
            @endforeach
        </div>
        <div class="partikel">
            @foreach ([[12, 0, 14], [24, -6, 18], [38, -2, 16], [52, -9, 20], [63, -4, 15], [71, -12, 22], [80, -7, 17], [88, -3, 19], [33, -14, 21], [46, -11, 16]] as [$kiri, $tunda, $durasi])
                <span style="left: {{ $kiri }}%; animation-delay: {{ $tunda }}s; animation-duration: {{ $durasi }}s"></span>
            @endforeach
        </div>
        <div class="siluet">
            <div class="cahaya-apel"></div>
            <div class="siluet-gambar"></div>
        </div>
    </div>

    <div class="relative z-10 flex min-h-screen flex-col px-5 py-6 sm:px-10 lg:px-[7vw]">
        <header class="flex items-center justify-end gap-2">
            <button type="button" data-musik-toggle aria-pressed="false" class="tombol-garis bg-kaca inline-flex size-10 cursor-pointer items-center justify-center rounded-xl" title="Putar / jeda musik Bad Apple!!" aria-label="Putar / jeda musik Bad Apple!!">
                <x-icon name="musik" class="size-5" data-ikon-musik />
                <x-icon name="pause" class="hidden size-5" data-ikon-jeda />
            </button>
            <button type="button" data-ganti-tema class="tombol-garis bg-kaca inline-flex size-10 cursor-pointer items-center justify-center rounded-xl" title="Ganti mode terang / gelap" aria-label="Ganti mode terang / gelap">
                <x-icon name="moon" class="size-5 dark:hidden" />
                <x-icon name="sun" class="hidden size-5 dark:block" />
            </button>
        </header>

        <main class="area-kartu flex flex-1 items-center py-10">
            <div class="kartu-login w-full max-w-sm rounded-3xl p-7 sm:p-8">
                <div class="flex items-start justify-between gap-3">
                    <p class="teks-redup flex items-center gap-2 text-xs font-medium tracking-wide uppercase">
                        <span class="titik-waktu size-2 rounded-full"></span>
                        <span data-salam>{{ $daftarSuasana[$suasana]['salam'] }}</span>
                    </p>
                    <p class="text-right leading-tight">
                        <span data-jam class="block text-sm font-semibold tabular-nums">--.--</span>
                        <span data-tanggal class="teks-redup text-[11px]"></span>
                    </p>
                </div>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight">Masuk ke akun</h1>
                <p data-kalimat class="teks-redup mt-2 text-sm">{{ $daftarSuasana[$suasana]['kalimat'] }}</p>

                @if ($errors->any())
                    <div class="mt-5 flex items-start gap-3 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-600 dark:text-rose-300" role="alert">
                        <x-icon name="x-circle" class="mt-px size-5" />
                        <p>{{ $errors->first() }}</p>
                    </div>
                @endif

                <form action="{{ route('login.proses') }}" method="POST" class="mt-7 space-y-4">
                    @csrf
                    <div>
                        <label for="username" class="label-login">Username</label>
                        <div class="relative">
                            <x-icon name="user" class="teks-redup pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2" />
                            <input id="username" name="username" type="text" class="isian-login pl-11" value="{{ old('username') }}" placeholder="Masukkan username" autocomplete="username" required autofocus>
                        </div>
                    </div>

                    <div>
                        <label for="password" class="label-login">Password</label>
                        <div class="relative">
                            <x-icon name="lock" class="teks-redup pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2" />
                            <input id="password" name="password" type="password" class="isian-login pr-11 pl-11" placeholder="Masukkan password" autocomplete="current-password" required>
                            <button type="button" data-toggle-password="password" class="teks-redup absolute top-1/2 right-2 -translate-y-1/2 cursor-pointer rounded-lg p-1.5 hover:opacity-70" aria-label="Tampilkan password">
                                <x-icon name="eye" class="size-5" data-icon-show />
                                <x-icon name="eye-slash" class="hidden size-5" data-icon-hide />
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="tombol-masuk group flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl py-3 text-sm font-semibold">
                        Masuk
                        <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" />
                    </button>
                </form>

                {{-- Suara suasana (audio) --}}
                <button type="button" data-audio-toggle aria-pressed="false" class="tombol-garis mt-4 flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl py-2.5 text-xs font-semibold">
                    <x-icon name="speaker" class="size-4" data-ikon-putar />
                    <x-icon name="speaker-off" class="hidden size-4" data-ikon-henti />
                    <span data-label-audio>Putar suara {{ $suasana }}</span>
                </button>
                <audio id="audio-suasana" loop preload="none" src="{{ $daftarSuasana[$suasana]['audio'] }}"></audio>
            </div>
        </main>

        <footer class="area-kartu teks-redup flex text-xs">&copy; {{ date('Y') }} Inventaris Gudang Sekolah</footer>
    </div>

    @include('partials.pemutar-musik')
</body>
</html>
