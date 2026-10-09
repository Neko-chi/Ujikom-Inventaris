@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan kondisi gudang dan aktivitas terbaru.')

@section('content')
    @php
        $user = auth()->user();
        // Aksi cepat & keterangan peran berbeda untuk setiap role.
        [$aksiCepat, $peran] = match ($user->role) {
            \App\Models\User::ROLE_ADMIN => [[
                [route('barang.create'), 'plus', 'Tambah barang'],
                [route('kategori.index'), 'tag', 'Kelola kategori'],
                [route('user.create'), 'users', 'Tambah pengguna'],
            ], 'mengelola pengguna, kategori, data barang, dan mengoreksi barang masuk'],
            \App\Models\User::ROLE_MANAGER => [[
                [route('barang-keluar.index', ['verifikasi' => 'Pending']), 'clipboard', 'Verifikasi permintaan'],
                [route('barang.index'), 'cube', 'Lihat data barang'],
                [route('barang-masuk.index'), 'masuk', 'Lihat barang masuk'],
            ], 'memverifikasi (menyetujui / menolak) barang keluar dan memantau seluruh data'],
            default => [[
                [route('barang-masuk.create'), 'masuk', 'Catat barang masuk'],
                [route('barang-keluar.create'), 'keluar', 'Ajukan barang keluar'],
                [route('barang.create'), 'plus', 'Tambah barang'],
            ], 'mencatat barang masuk dan mengajukan barang keluar'],
        };
    @endphp

    {{-- Sapaan dan aksi cepat --}}
    {{-- Warna banner mengikuti tema: terang = abu-abu muda bertinta hitam, gelap = hitam bertinta putih --}}
    <section class="banner-sapaan relative mb-6 overflow-hidden rounded-2xl p-6 text-slate-900 ring-1 ring-slate-200 sm:p-8">
        <div class="pointer-events-none absolute -top-16 -right-16 size-64 rounded-full bg-slate-900/5 blur-2xl"></div>
        {{-- Penyihir sebagai hiasan banner (diam dan samar) --}}
        <div class="penyihir-banner pointer-events-none absolute top-1/2 right-6 hidden h-[78%] -translate-y-1/2 md:block" aria-hidden="true"></div>

        <div class="relative">
            <p class="text-sm font-medium text-slate-500">{{ $salam }},</p>
            <h2 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">{{ $user->nama_user }}</h2>
            <p class="mt-2 max-w-xl text-sm text-slate-500">
                Anda masuk sebagai <strong class="font-semibold text-slate-700">{{ $user->role }}</strong>: {{ $peran }}.
            </p>

            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($aksiCepat as [$url, $ikon, $label])
                    <a href="{{ $url }}" @if (str_ends_with((string) parse_url($url, PHP_URL_PATH), '/create')) data-turbo-frame="modal" @endif class="inline-flex items-center gap-2 rounded-xl bg-surface/70 px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 backdrop-blur transition hover:bg-surface hover:text-slate-900">
                        <x-icon :name="$ikon" class="size-4" />{{ $label }}
                        @if ($loop->first && $user->isManager() && $ringkasan['menunggu_verifikasi'] > 0)
                            <span class="rounded-full bg-amber-400 px-1.5 text-xs font-bold text-amber-950">{{ $ringkasan['menunggu_verifikasi'] }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Kartu statistik --}}
    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['Jenis Barang', $ringkasan['jenis_barang'], 'cube', 'bg-brand-50 text-brand-600', 'terdaftar di gudang'],
            ['Total Stok', $ringkasan['total_stok'], 'archive', 'bg-violet-50 text-violet-600', 'unit dari seluruh barang'],
            ['Unit Masuk', $ringkasan['total_masuk'], 'masuk', 'bg-emerald-50 text-emerald-600', 'sejak awal pencatatan'],
            ['Perlu Verifikasi', $ringkasan['menunggu_verifikasi'], 'clock', 'bg-amber-50 text-amber-600', 'permintaan barang keluar'],
        ] as [$judul, $angka, $ikon, $warna, $keterangan])
            <div class="card p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium text-slate-500">{{ $judul }}</p>
                    <div class="flex size-10 items-center justify-center rounded-xl {{ $warna }}">
                        <x-icon :name="$ikon" class="size-5" />
                    </div>
                </div>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($angka, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ $keterangan }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        {{-- Grafik stok per kategori --}}
        <div class="card xl:col-span-2">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Stok per Kategori</h3>
                    <p class="text-xs text-slate-500">Jumlah unit tersedia di setiap kategori</p>
                </div>
                <x-icon name="chart" class="size-5 text-slate-400" />
            </div>
            <div class="space-y-4 p-5">
                @php($stokTerbesar = max(1, (int) $stokPerKategori->max('total_stok')))
                @forelse ($stokPerKategori as $k)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ $k->nama_kategori }} <span class="text-xs font-normal text-slate-400">· {{ $k->barang_count }} jenis</span></span>
                            <span class="font-semibold text-slate-900">{{ number_format((int) $k->total_stok, 0, ',', '.') }}</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-600" style="width: {{ round((int) $k->total_stok / $stokTerbesar * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <x-empty icon="tag" judul="Belum ada kategori" />
                @endforelse
            </div>
        </div>

        {{-- Status permintaan barang keluar --}}
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Permintaan Barang Keluar</h3>
                    <p class="text-xs text-slate-500">Berdasarkan status verifikasi</p>
                </div>
            </div>
            <div class="p-5">
                @php($totalPermintaan = $statusKeluar->sum())
                @php($warnaStatus = ['Pending' => 'bg-amber-400', 'Disetujui' => 'bg-emerald-500', 'Ditolak' => 'bg-rose-500'])
                <p class="text-3xl font-bold tracking-tight text-slate-900">{{ $totalPermintaan }}</p>
                <p class="text-xs text-slate-500">total permintaan</p>

                <div class="mt-5 flex h-3 overflow-hidden rounded-full bg-slate-100">
                    @foreach ($statusKeluar as $status => $jumlah)
                        @if ($jumlah > 0)
                            <div class="{{ $warnaStatus[$status] }} border-r-2 border-surface last:border-r-0" style="width: {{ $jumlah / $totalPermintaan * 100 }}%" title="{{ $status }}: {{ $jumlah }}"></div>
                        @endif
                    @endforeach
                </div>

                <ul class="mt-5 space-y-3">
                    @foreach ($statusKeluar as $status => $jumlah)
                        <li>
                            <a href="{{ route('barang-keluar.index', ['verifikasi' => $status]) }}" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50">
                                <span class="flex items-center gap-2.5 text-slate-600">
                                    <span class="size-2.5 rounded-full {{ $warnaStatus[$status] }}"></span>{{ $status }}
                                </span>
                                <span class="font-semibold text-slate-900">{{ $jumlah }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-5">
        {{-- Stok menipis --}}
        <div class="card xl:col-span-2">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Stok Menipis</h3>
                    <p class="text-xs text-slate-500">Stok &le; {{ \App\Models\Barang::BATAS_STOK_MENIPIS }} unit, perlu segera diadakan</p>
                </div>
                @if ($stokMenipis->isNotEmpty())
                    <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">{{ $stokMenipis->count() }} barang</span>
                @endif
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($stokMenipis as $b)
                    <li>
                        <a href="{{ route('barang.show', $b) }}" data-turbo-frame="modal" class="flex items-center gap-4 px-5 py-3.5 transition hover:bg-slate-50">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-500">
                                <x-icon name="warning" class="size-5" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-800">{{ $b->nama_barang }}</p>
                                <p class="text-xs text-slate-500"><span class="kode">{{ $b->kode_barang }}</span> · {{ $b->kategori->nama_kategori }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-rose-600">{{ $b->stok }}</p>
                                <p class="text-xs text-slate-400">{{ $b->satuan }}</p>
                            </div>
                        </a>
                    </li>
                @empty
                    <li><x-empty icon="check-circle" judul="Semua stok aman" pesan="Tidak ada barang dengan stok menipis." /></li>
                @endforelse
            </ul>
        </div>

        {{-- Aktivitas terbaru --}}
        <div class="card xl:col-span-3">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Aktivitas Terbaru</h3>
                    <p class="text-xs text-slate-500">Gabungan barang masuk dan barang keluar</p>
                </div>
                <a href="{{ route('barang-keluar.index') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700">Lihat semua</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($aktivitas as $a)
                    @php($masuk = $a['jenis'] === 'masuk')
                    <li class="flex items-center gap-4 px-5 py-3.5">
                        <div @class([
                            'flex size-10 shrink-0 items-center justify-center rounded-xl',
                            'bg-emerald-50 text-emerald-600' => $masuk,
                            'bg-brand-50 text-brand-600' => ! $masuk,
                        ])>
                            <x-icon :name="$masuk ? 'masuk' : 'keluar'" class="size-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">
                                @if ($a['url'])
                                    <a href="{{ $a['url'] }}" class="hover:text-brand-700">{{ $a['barang'] }}</a>
                                @else
                                    {{ $a['barang'] }}
                                @endif
                            </p>
                            <p class="truncate text-xs text-slate-500">{{ $masuk ? 'Masuk' : 'Keluar' }} · {{ $a['tanggal']->translatedFormat('d M Y') }} · {{ $a['keterangan'] }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span @class(['text-sm font-bold', 'text-emerald-600' => $masuk, 'text-slate-800' => ! $masuk])>
                                {{ $masuk ? '+' : '−' }}{{ $a['jumlah'] }} <span class="text-xs font-normal text-slate-400">{{ $a['satuan'] }}</span>
                            </span>
                            @if ($a['status'])
                                <x-badge :nilai="$a['status']" />
                            @endif
                        </div>
                    </li>
                @empty
                    <li><x-empty icon="clock" judul="Belum ada aktivitas" /></li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
