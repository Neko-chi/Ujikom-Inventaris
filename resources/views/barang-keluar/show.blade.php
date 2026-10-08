@extends('layouts.app')

@section('title', 'Detail Permintaan #'.$barangKeluar->id_keluar)
@section('subtitle', 'Informasi pengajuan barang keluar dan hasil verifikasinya.')

@section('aksi')
    <a href="{{ route('barang-keluar.index') }}" class="btn-secondary"><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    @php($k = $barangKeluar)
    @php($stokCukup = $k->jumlah <= $k->barang->stok)

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        {{-- Data pengajuan --}}
        <div class="card xl:col-span-2">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <x-icon name="keluar" />
                    </div>
                    <div>
                        <h3 class="card-title">Data Pengajuan</h3>
                        <p class="text-xs text-slate-500">Diajukan {{ $k->tanggal->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>
                <x-badge :nilai="$k->verifikasi" />
            </div>

            {{-- Barang yang diminta --}}
            <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-5">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                    <x-icon name="cube" class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <span class="kode">{{ $k->barang->kode_barang }}</span>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $k->barang->nama_barang }}</p>
                    <p class="text-sm text-slate-500">{{ $k->barang->kategori->nama_kategori }}</p>
                </div>
                <div class="grid w-full grid-cols-2 gap-3 text-center sm:w-auto">
                    <div class="rounded-xl bg-slate-50 px-4 py-2 ring-1 ring-slate-100">
                        <p class="text-xs text-slate-500">Diminta</p>
                        <p class="text-xl font-bold text-slate-900">{{ $k->jumlah }}</p>
                    </div>
                    <div @class(['rounded-xl px-4 py-2 ring-1', 'bg-emerald-50 ring-emerald-100' => $stokCukup, 'bg-rose-50 ring-rose-100' => ! $stokCukup])>
                        <p class="text-xs text-slate-500">Stok saat ini</p>
                        <p @class(['text-xl font-bold', 'text-emerald-700' => $stokCukup, 'text-rose-700' => ! $stokCukup])>{{ $k->barang->stok }}</p>
                    </div>
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-5 p-5 sm:grid-cols-2">
                @foreach ([
                    ['user', 'Pemohon', $k->pemohon],
                    ['map-pin', 'Tujuan', $k->tujuan],
                    ['clipboard', 'Diajukan oleh (Operator)', $k->user->nama_user],
                    ['calendar', 'Tanggal pengajuan', $k->tanggal->translatedFormat('d F Y')],
                ] as [$ikon, $label, $nilai])
                    <div class="flex gap-3">
                        <x-icon :name="$ikon" class="mt-0.5 size-5 text-slate-400" />
                        <div>
                            <dt class="text-xs text-slate-500">{{ $label }}</dt>
                            <dd class="font-medium text-slate-900">{{ $nilai }}</dd>
                        </div>
                    </div>
                @endforeach
                <div class="flex gap-3">
                    <x-icon name="shield" class="mt-0.5 size-5 text-slate-400" />
                    <div>
                        <dt class="text-xs text-slate-500">Kondisi barang</dt>
                        <dd class="mt-0.5"><x-badge :nilai="$k->status_barang" /></dd>
                    </div>
                </div>
            </dl>

            {{-- Foto bukti yang diunggah Operator saat pengajuan --}}
            <div class="border-t border-slate-100 p-5">
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="photo" class="size-5 text-slate-400" />Foto Bukti Permintaan</p>
                @if ($k->foto_url)
                    <a href="{{ $k->foto_url }}" target="_blank" rel="noopener" title="Buka ukuran penuh" class="group block w-fit">
                        <img src="{{ $k->foto_url }}" alt="Foto bukti permintaan" class="max-h-80 rounded-xl object-contain ring-1 ring-slate-200 transition group-hover:ring-brand-300">
                    </a>
                    <p class="hint">Klik gambar untuk melihat ukuran penuh.</p>
                @else
                    <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Tidak ada foto. Permintaan ini berasal dari data sampel sebelum fitur foto bukti diwajibkan.</p>
                @endif
            </div>
        </div>

        {{-- Panel verifikasi --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Verifikasi Manager</h3>
            </div>

            <div class="p-5">
                @if (! $k->isPending())
                    @php($disetujui = $k->verifikasi === \App\Models\BarangKeluar::DISETUJUI)
                    <div @class(['mb-5 flex items-center gap-3 rounded-xl p-4', 'bg-emerald-50 text-emerald-800' => $disetujui, 'bg-rose-50 text-rose-800' => ! $disetujui])>
                        <x-icon :name="$disetujui ? 'check-circle' : 'x-circle'" class="size-8" />
                        <div>
                            <p class="font-semibold">Permintaan {{ strtolower($k->verifikasi) }}</p>
                            <p class="text-sm opacity-80">{{ $disetujui ? 'Stok telah dikurangi.' : 'Stok tidak berubah.' }}</p>
                        </div>
                    </div>
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-slate-500">Diverifikasi oleh</dt>
                            <dd class="mt-1 flex items-center gap-2 font-medium text-slate-900">
                                @if ($k->verifikator)
                                    <span class="flex size-7 items-center justify-center rounded-full bg-slate-900 text-[10px] font-bold text-white">{{ $k->verifikator->inisial() }}</span>
                                @endif
                                {{ $k->verifikator?->nama_user ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Tanggal verifikasi</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $k->tanggal_verifikasi?->translatedFormat('d F Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Catatan</dt>
                            <dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-700">{{ $k->catatan_verifikasi ?: 'Tidak ada catatan.' }}</dd>
                        </div>
                    </dl>
                @elseif (auth()->user()->isManager())
                    @unless ($stokCukup)
                        <div class="mb-4 flex gap-2 rounded-xl bg-rose-50 p-3 text-sm text-rose-700">
                            <x-icon name="warning" class="size-5" />
                            <p>Stok saat ini tidak mencukupi, permintaan tidak dapat disetujui.</p>
                        </div>
                    @endunless

                    <form method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="catatan_verifikasi" class="label">Catatan <span class="font-normal text-slate-400">(wajib jika ditolak)</span></label>
                            <textarea id="catatan_verifikasi" name="catatan_verifikasi" rows="4" maxlength="255" class="input resize-none" placeholder="Contoh: Silakan ambil di gudang utama.">{{ old('catatan_verifikasi') }}</textarea>
                            <x-error field="catatan_verifikasi" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="submit" formaction="{{ route('barang-keluar.tolak', $k) }}" class="btn-secondary text-rose-600! hover:bg-rose-50!"
                                    onclick="return confirm('Tolak permintaan ini?')"><x-icon name="x" />Tolak</button>
                            <button type="submit" formaction="{{ route('barang-keluar.setujui', $k) }}" class="btn-success" @disabled(! $stokCukup)
                                    onclick="return confirm('Setujui permintaan ini? Stok akan dikurangi.')"><x-icon name="check" />Setujui</button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-col items-center py-6 text-center">
                        <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                            <x-icon name="clock" class="size-6" />
                        </div>
                        <p class="font-semibold text-slate-800">Menunggu verifikasi</p>
                        <p class="mt-1 text-sm text-slate-500">Stok belum dikurangi sampai Manager menyetujui permintaan ini.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
