<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Inventaris Gudang Sekolah</title>
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

    {{-- Latar gelap saat sidebar terbuka di layar kecil --}}
    <div id="sidebar-overlay" data-sidebar-close class="fixed inset-0 z-30 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

    {{-- Sidebar --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-gradient-to-b from-slate-900 via-slate-900 to-brand-950 text-slate-300 transition-transform duration-200 lg:translate-x-0 print:hidden">
        <div class="flex items-center gap-3 px-6 py-6">
            <div class="flex size-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 text-white shadow-lg shadow-brand-900/50">
                <x-icon name="archive" class="size-5" />
            </div>
            <div class="leading-tight">
                <p class="text-[15px] font-bold text-white">Gudang Sekolah</p>
                <p class="text-xs text-slate-400">Sistem Inventaris</p>
            </div>
            <button type="button" data-sidebar-close class="ml-auto rounded-lg p-1.5 text-slate-400 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Tutup menu">
                <x-icon name="x" />
            </button>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-4 pb-6">
            @foreach ($menu as $grup => $items)
                @php($items = array_filter($items, fn ($item) => ! $item[4] || $user->isAdmin()))
                @continue(empty($items))
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $grup }}</p>
                    <div class="space-y-1">
                        @foreach ($items as [$route, $pola, $label, $ikon, $adminSaja, $badge])
                            @php($aktif = request()->routeIs($pola))
                            <a href="{{ route($route) }}"
                               @class([
                                   'group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                   'bg-white/10 text-white' => $aktif,
                                   'hover:bg-white/5 hover:text-white' => ! $aktif,
                               ])>
                                @if ($aktif)
                                    <span class="absolute inset-y-2 -left-4 w-1 rounded-r-full bg-brand-400"></span>
                                @endif
                                <x-icon :name="$ikon" :class="\Illuminate\Support\Arr::toCssClasses(['size-5', 'text-brand-300' => $aktif, 'text-slate-500 group-hover:text-slate-300' => ! $aktif])" />
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

        {{-- Kartu pengguna --}}
        <div class="m-4 rounded-2xl bg-white/5 p-3 ring-1 ring-white/10">
            <div class="flex items-center gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-500/20 text-sm font-bold text-brand-200 ring-1 ring-brand-400/30">
                    {{ $user->inisial() }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-white">{{ $user->nama_user }}</p>
                    <p class="text-xs text-slate-400">{{ $user->role }}</p>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-500/15 hover:text-rose-300" title="Keluar" aria-label="Keluar">
                        <x-icon name="logout" class="size-5" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="lg:pl-72">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200/80 bg-white/80 px-4 backdrop-blur-md sm:px-6 lg:px-8 print:hidden">
            <button type="button" data-sidebar-open class="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Buka menu">
                <x-icon name="menu" class="size-6" />
            </button>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <x-icon name="calendar" class="size-4" />
                <span>{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
            <div class="ml-auto flex items-center gap-3">
                <span @class([
                    'hidden rounded-full px-2.5 py-1 text-xs font-semibold sm:inline-flex',
                    'bg-brand-50 text-brand-700 ring-1 ring-brand-200' => $user->isAdmin(),
                    'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' => $user->isOperator(),
                ])>{{ $user->role }}</span>
                <div class="flex size-9 items-center justify-center rounded-full bg-slate-900 text-xs font-bold text-white">{{ $user->inisial() }}</div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
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
</body>
</html>
