<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Inventaris Gudang Sekolah</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Panel kiri: identitas aplikasi --}}
        <section class="relative hidden overflow-hidden bg-gradient-to-br from-slate-900 via-brand-950 to-brand-800 p-12 text-white lg:flex lg:flex-col">
            {{-- Pola kotak dekoratif --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]" style="background-image: linear-gradient(white 1px, transparent 1px), linear-gradient(90deg, white 1px, transparent 1px); background-size: 40px 40px;"></div>
            <div class="pointer-events-none absolute -right-24 -bottom-24 size-96 rounded-full bg-brand-500/30 blur-3xl"></div>

            <div class="relative flex items-center gap-3">
                <div class="flex size-11 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                    <x-icon name="archive" class="size-6" />
                </div>
                <div class="leading-tight">
                    <p class="font-bold">Gudang Sekolah</p>
                    <p class="text-sm text-brand-200">Sistem Inventaris</p>
                </div>
            </div>

            <div class="relative mt-auto max-w-md">
                <h1 class="text-4xl leading-tight font-bold tracking-tight">Kelola barang gudang sekolah dengan rapi dan terawasi.</h1>
                <p class="mt-4 text-brand-100/80">Catat barang masuk, ajukan barang keluar, dan pantau stok secara real-time dengan alur persetujuan yang jelas.</p>

                <ul class="mt-10 space-y-4">
                    @foreach ([
                        ['masuk', 'Pencatatan barang masuk', 'Stok bertambah otomatis setiap barang diterima.'],
                        ['shield', 'Persetujuan berjenjang', 'Barang keluar wajib diverifikasi oleh Admin.'],
                        ['chart', 'Pantauan stok', 'Peringatan dini untuk stok yang menipis.'],
                    ] as [$ikon, $judul, $teks])
                        <li class="flex gap-4">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                                <x-icon :name="$ikon" class="size-5 text-brand-200" />
                            </div>
                            <div>
                                <p class="font-semibold">{{ $judul }}</p>
                                <p class="text-sm text-brand-100/70">{{ $teks }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="relative mt-12 text-xs text-brand-200/60">&copy; {{ date('Y') }} Inventaris Gudang Sekolah</p>
        </section>

        {{-- Panel kanan: form login --}}
        <section class="flex items-center justify-center bg-slate-50 p-6 sm:p-12 lg:bg-white">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-lg shadow-brand-600/30">
                        <x-icon name="archive" class="size-6" />
                    </div>
                    <div class="leading-tight">
                        <p class="font-bold text-slate-900">Gudang Sekolah</p>
                        <p class="text-sm text-slate-500">Sistem Inventaris</p>
                    </div>
                </div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Selamat datang kembali</h2>
                <p class="mt-1.5 text-sm text-slate-500">Masuk menggunakan akun yang diberikan Admin.</p>

                @if ($errors->any())
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                        <x-icon name="x-circle" class="mt-px size-5 text-rose-500" />
                        <p>{{ $errors->first() }}</p>
                    </div>
                @endif

                <form action="{{ route('login.proses') }}" method="POST" class="mt-8 space-y-5">
                    @csrf
                    <div>
                        <label for="username" class="label">Username</label>
                        <div class="relative">
                            <x-icon name="user" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-slate-400" />
                            <input id="username" name="username" type="text" class="input pl-11" value="{{ old('username') }}" placeholder="Masukkan username" autocomplete="username" required autofocus>
                        </div>
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <div class="relative">
                            <x-icon name="lock" class="pointer-events-none absolute top-1/2 left-3.5 size-5 -translate-y-1/2 text-slate-400" />
                            <input id="password" name="password" type="password" class="input pr-11 pl-11" placeholder="Masukkan password" autocomplete="current-password" required>
                            <button type="button" data-toggle-password="password" class="absolute top-1/2 right-2 -translate-y-1/2 rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tampilkan password">
                                <x-icon name="eye" class="size-5" data-icon-show />
                                <x-icon name="eye-slash" class="hidden size-5" data-icon-hide />
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3">
                        Masuk
                        <x-icon name="arrow-right" />
                    </button>
                </form>
            </div>
        </section>
    </div>
</body>
</html>
