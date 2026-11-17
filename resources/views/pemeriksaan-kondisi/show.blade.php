@extends('layouts.app')

{{-- Dapat dibuka sebagai popup --}}
@section('popup', true)

@section('title', 'Pemeriksaan Kondisi #'.$pemeriksaan->id_pemeriksaan)
@section('subtitle', 'Perubahan kondisi barang yang diajukan Operator dan hasil verifikasi Manager.')

@section('aksi')
    <a href="{{ route('pemeriksaan-kondisi.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
@endsection

@section('content')
    @php
        $p = $pemeriksaan;
        $satuan = $p->barang->satuan;
    @endphp

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        {{-- Data pengajuan --}}
        <div class="card xl:col-span-2">
            <div class="card-header">
                <div class="flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <x-icon name="clipboard" />
                    </div>
                    <div>
                        <h3 class="card-title">Data Pemeriksaan</h3>
                        <p class="text-xs text-slate-500">Diperiksa {{ $p->tanggal->translatedFormat('l, d F Y') }}</p>
                    </div>
                </div>
                <x-badge :nilai="$p->verifikasi" />
            </div>

            {{-- Barang dan perubahan kondisi --}}
            <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-5">
                <div class="flex size-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                    <x-icon name="cube" class="size-7" />
                </div>
                <div class="min-w-0 flex-1">
                    <span class="kode">{{ $p->barang->kode_barang }}</span>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $p->barang->nama_barang }}</p>
                    <div class="mt-1 flex items-center gap-1.5">
                        <x-badge :nilai="$p->kondisi_asal" />
                        <x-icon name="arrow-right" class="size-3.5 text-slate-400" />
                        <x-badge :nilai="$p->kondisi_baru" />
                    </div>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-2 text-center ring-1 ring-slate-100">
                    <p class="text-xs text-slate-500">Jumlah</p>
                    <p class="text-xl font-bold text-slate-900">{{ $p->jumlah }} <span class="text-sm font-normal text-slate-500">{{ $satuan }}</span></p>
                </div>
            </div>

            <dl class="grid gap-x-6 gap-y-5 p-5 sm:grid-cols-2">
                @foreach ([
                    ['map-pin', 'Lokasi', $p->lokasi->nama_lokasi],
                    ['users', 'Diajukan oleh (Operator)', $p->user->nama_user],
                    ['clipboard', 'Alasan / temuan', $p->alasan],
                ] as [$ikon, $label, $nilai])
                    <div class="flex gap-3">
                        <x-icon :name="$ikon" class="mt-0.5 size-5 text-slate-400" />
                        <div>
                            <dt class="text-xs text-slate-500">{{ $label }}</dt>
                            <dd class="font-medium text-slate-900">{{ $nilai }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>

            {{-- Foto bukti kondisi fisik --}}
            <div class="border-t border-slate-100 p-5">
                <p class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="photo" class="size-5 text-slate-400" />Foto Bukti</p>
                @if ($p->foto_url)
                    <a href="{{ $p->foto_url }}" target="_blank" rel="noopener" title="Buka ukuran penuh" class="group block w-fit">
                        <img src="{{ $p->foto_url }}" alt="Foto bukti kondisi" class="max-h-80 rounded-xl object-contain ring-1 ring-slate-200 transition group-hover:ring-brand-300">
                    </a>
                    <p class="hint">Klik gambar untuk melihat ukuran penuh.</p>
                @else
                    <p class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">Tidak ada foto. Data ini berasal dari data sampel.</p>
                @endif
            </div>
        </div>

        {{-- Panel verifikasi --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Verifikasi Manager</h3>
            </div>

            <div class="p-5">
                @if (! $p->isPending())
                    @php($disetujui = $p->verifikasi === \App\Models\BarangKeluar::DISETUJUI)
                    <div @class(['mb-5 flex items-center gap-3 rounded-xl p-4', 'bg-emerald-50 text-emerald-800' => $disetujui, 'bg-rose-50 text-rose-800' => ! $disetujui])>
                        <x-icon :name="$disetujui ? 'check-circle' : 'x-circle'" class="size-8" />
                        <div>
                            <p class="font-semibold">Pemeriksaan {{ strtolower($p->verifikasi) }}</p>
                            <p class="text-sm opacity-80">{{ $disetujui ? 'Kondisi stok telah diperbarui.' : 'Stok tidak berubah.' }}</p>
                        </div>
                    </div>
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-slate-500">Diverifikasi oleh</dt>
                            <dd class="mt-1 flex items-center gap-2 font-medium text-slate-900">
                                @if ($p->verifikator)
                                    <span class="flex size-7 items-center justify-center rounded-full bg-brand-600 text-[10px] font-bold text-on-brand">{{ $p->verifikator->inisial() }}</span>
                                @endif
                                {{ $p->verifikator?->nama_user ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Tanggal verifikasi</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $p->tanggal_verifikasi?->translatedFormat('d F Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Catatan</dt>
                            <dd class="mt-1 rounded-xl bg-slate-50 p-3 text-slate-700">{{ $p->catatan_verifikasi ?: 'Tidak ada catatan.' }}</dd>
                        </div>
                    </dl>
                @elseif (auth()->user()->isManager())
                    <div class="mb-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-600 ring-1 ring-slate-200">
                        <p class="font-semibold text-slate-800">Stok {{ $p->kondisi_asal }} di {{ $p->lokasi->nama_lokasi }} saat ini</p>
                        <p class="mt-1 text-sm font-bold text-slate-900">{{ $tersedia }} {{ $satuan }}</p>
                    </div>
                    @if ($tersedia < $p->jumlah)
                        <div class="mb-4 flex gap-2 rounded-xl bg-rose-50 p-3 text-sm text-rose-700">
                            <x-icon name="warning" class="size-5" />
                            <p>Stok {{ $p->kondisi_asal }} tidak mencukupi. Tolak pengajuan ini.</p>
                        </div>
                    @endif

                    <form method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="catatan_verifikasi" class="label">Catatan <span class="font-normal text-slate-400">(wajib jika ditolak)</span></label>
                            <textarea id="catatan_verifikasi" name="catatan_verifikasi" rows="3" maxlength="255" class="input resize-none" placeholder="Contoh: Sudah dicek langsung, sesuai foto.">{{ old('catatan_verifikasi') }}</textarea>
                            <x-error field="catatan_verifikasi" />
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="submit" formaction="{{ route('pemeriksaan-kondisi.tolak', $p) }}" class="btn-secondary text-rose-600! hover:bg-rose-50!"
                                    onclick="return confirm('Tolak pemeriksaan ini?')"><x-icon name="x" />Tolak</button>
                            <button type="submit" formaction="{{ route('pemeriksaan-kondisi.setujui', $p) }}" class="btn-success"
                                    onclick="return confirm('Setujui pemeriksaan ini? Kondisi stok akan diperbarui.')"><x-icon name="check" />Setujui</button>
                        </div>
                    </form>
                @else
                    <div class="flex flex-col items-center py-6 text-center">
                        <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                            <x-icon name="clock" class="size-6" />
                        </div>
                        <p class="font-semibold text-slate-800">Menunggu verifikasi</p>
                        <p class="mt-1 text-sm text-slate-500">Kondisi stok belum berubah sampai Manager menyetujui.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
