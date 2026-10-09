@php
    /*
     * Turbo Frame: bila halaman diminta untuk dimuat di dalam popup ("modal") atau panel
     * kanan ("laci"), dan halamannya menandai diri dengan @section('popup'), layout hanya
     * mengirim isi halaman tanpa sidebar. Halaman lain tetap dikirim utuh.
     */
    $bingkai = in_array(request()->header('Turbo-Frame'), ['modal', 'laci'], true) ? request()->header('Turbo-Frame') : null;
@endphp

@if ($bingkai && View::hasSection('popup'))
    {{-- ====================== Isi popup / panel kanan ====================== --}}
    <turbo-frame id="{{ $bingkai }}">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="text-lg font-bold tracking-tight text-slate-900">@yield('title')</h2>
                @hasSection('subtitle')
                    <p class="mt-0.5 text-sm text-slate-500">@yield('subtitle')</p>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <div class="aksi-popup flex flex-wrap gap-2">@yield('aksi')</div>
                <button type="button" data-tutup-popup class="btn-icon" title="Tutup" aria-label="Tutup"><x-icon name="x" /></button>
            </div>
        </div>
        <div class="p-5 sm:p-6">
            @include('partials.flash')
            @yield('content')
        </div>
    </turbo-frame>
@else
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Turbo tidak memuat halaman lebih awal saat kursor menyentuh tautan --}}
    <meta name="turbo-prefetch" content="false">
    <title>@yield('title') - Inventaris Gudang Sekolah</title>
    @include('partials.tema-awal')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    @php
        $user = auth()->user();
        // Struktur menu: [grup => [[route, pola aktif, label, ikon, hanya admin?, badge]]]
        $menu = [
            'Utama' => [
                ['dashboard', 'dashboard', 'Dashboard', 'home', false, null],
            ],
            'Transaksi' => [
                ['barang-masuk.index', 'barang-masuk.*', 'Barang Masuk', 'masuk', false, null],
                ['barang-keluar.index', 'barang-keluar.*', 'Barang Keluar', 'keluar', false, $jumlahPending ?: null],
            ],
            'Data Master' => [
                ['barang.index', 'barang.*', 'Data Barang', 'cube', false, null],
                ['kategori.index', 'kategori.*', 'Kategori', 'tag', true, null],
            ],
            'Pengaturan' => [
                ['user.index', 'user.*', 'Pengguna', 'users', true, null],
            ],
        ];
    @endphp

    {{-- Karakter tersenyum sebagai latar (warnanya kebalikan tema) --}}
    <div class="latar-karakter print:hidden" aria-hidden="true"></div>

    {{-- Percikan cahaya yang naik lalu menghilang (posisi acak tetapi sama setiap dimuat) --}}
    <div class="percikan print:hidden" aria-hidden="true">
        @php(mt_srand(11))
        @for ($i = 0; $i < 34; $i++)
            <i @class(['bintang' => $i % 4 === 0])
               style="left: {{ mt_rand(0, 1000) / 10 }}%; --ukuran: {{ $i % 4 === 0 ? mt_rand(8, 13) : mt_rand(2, 5) }}px; --durasi: {{ mt_rand(90, 180) / 10 }}s; --tunda: -{{ mt_rand(0, 180) / 10 }}s; --goyang: {{ mt_rand(-60, 60) }}px"></i>
        @endfor
    </div>

    {{-- Latar gelap saat sidebar terbuka di layar kecil --}}
    <div id="sidebar-overlay" data-sidebar-tutup class="fixed inset-0 z-30 hidden bg-black/50 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar (warnanya mengikuti tema; bisa ditutup dengan tombol burger) --}}
    <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-surface text-slate-600 transition-transform duration-300 lg:translate-x-0 print:hidden">
        <div class="flex justify-end px-4 pt-4 lg:hidden">
            <button type="button" data-sidebar-tutup class="btn-icon" aria-label="Tutup menu"><x-icon name="x" /></button>
        </div>

        {{-- Karakter samar sebagai latar sidebar --}}
        <div class="karakter-sidebar" aria-hidden="true"></div>

        <nav class="relative flex-1 space-y-6 overflow-y-auto px-4 pt-2 pb-6 lg:pt-8">
            @foreach ($menu as $grup => $items)
                @php($items = array_filter($items, fn ($item) => ! $item[4] || $user->isAdmin()))
                @continue(empty($items))
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $grup }}</p>
                    <div class="space-y-1">
                        @foreach ($items as [$route, $pola, $label, $ikon, $adminSaja, $badge])
                            @php($aktif = request()->routeIs($pola))
                            <a href="{{ route($route) }}"
                               @class([
                                   'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                   'bg-slate-100 text-slate-900' => $aktif,
                                   'hover:bg-slate-50 hover:text-slate-900' => ! $aktif,
                               ])>
                                @if ($aktif)
                                    <span class="absolute inset-y-2 -left-4 w-1 rounded-r-full bg-brand-600"></span>
                                @endif
                                <x-icon :name="$ikon" :class="\Illuminate\Support\Arr::toCssClasses(['size-5', 'text-slate-900' => $aktif, 'text-slate-400 group-hover:text-slate-700' => ! $aktif])" />
                                <span class="flex-1">{{ $label }}</span>
                                @if ($badge)
                                    <span class="rounded-full bg-amber-400 px-2 py-0.5 text-[11px] font-bold text-amber-950">{{ $badge }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        {{-- Kartu pengguna: klik untuk membuka profil di panel kanan --}}
        <div class="relative m-4 rounded-2xl bg-slate-50 p-3 ring-1 ring-slate-200">
            <div class="flex items-center gap-3">
                <a href="{{ route('profil.edit') }}" data-turbo-frame="laci" class="flex min-w-0 flex-1 items-center gap-3 rounded-xl hover:opacity-80" title="Profil saya">
                    <x-avatar :user="$user" class="size-10" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->nama_user }}</p>
                        <p class="text-xs text-slate-500">{{ $user->role }}</p>
                    </div>
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="cursor-pointer rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" title="Keluar" aria-label="Keluar">
                        <x-icon name="logout" class="size-5" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="isi-utama relative transition-[padding] duration-300 lg:pl-72">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200/80 bg-surface/80 px-4 backdrop-blur-md sm:px-6 lg:px-8 print:hidden">
            <button type="button" data-sidebar-toggle class="btn-icon -ml-1" title="Buka / tutup menu" aria-label="Buka / tutup menu">
                <x-icon name="menu" class="size-6" />
            </button>
            <div class="hidden items-center gap-2 text-sm text-slate-500 sm:flex">
                <x-icon name="calendar" class="size-4" />
                <span>{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
            <div class="ml-auto flex items-center gap-3">
                <x-tombol-musik />
                <x-tombol-tema />
                <span @class([
                    'hidden rounded-full px-2.5 py-1 text-xs font-semibold sm:inline-flex',
                    'bg-brand-50 text-brand-700 ring-1 ring-brand-200' => $user->isAdmin(),
                    'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' => $user->isOperator(),
                    'bg-violet-50 text-violet-700 ring-1 ring-violet-200' => $user->isManager(),
                ])>{{ $user->role }}</span>
                <a href="{{ route('profil.edit') }}" data-turbo-frame="laci" title="Profil saya" class="rounded-full ring-2 ring-transparent transition hover:ring-slate-300">
                    <x-avatar :user="$user" class="size-9" />
                </a>
            </div>
        </header>

        <main class="relative mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
            {{-- Judul halaman --}}
            <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">@yield('title')</h1>
                    @hasSection('subtitle')
                        <p class="mt-1 text-sm text-slate-500">@yield('subtitle')</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2 print:hidden">@yield('aksi')</div>
            </div>

            @include('partials.flash')

            @yield('content')
        </main>
    </div>

    {{--
        Wadah popup (tengah) dan panel kanan; isinya dimuat Turbo Frame tanpa pindah halaman.
        Elemen <turbo-frame> di dalamnya dibuat oleh resources/js/popup.js, sehingga halaman biasa
        tidak pernah berisi frame "modal"/"laci" (penanda bahwa popup harus ditutup).
    --}}
    <dialog id="dialog-modal" class="popup popup-tengah"></dialog>
    <dialog id="dialog-laci" class="popup popup-laci"></dialog>

    @include('partials.pemutar-musik')
</body>
</html>
@endif
