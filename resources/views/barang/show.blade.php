@extends('layouts.app')

{{-- Dapat dibuka sebagai popup (Turbo Frame) tanpa pindah halaman --}}
@section('popup', true)

@section('title', $barang->nama_barang)
@section('subtitle', 'Detail barang dan riwayat transaksinya.')

@section('aksi')
    <a href="{{ route('barang.index') }}" class="btn-secondary" data-tutup-popup><x-icon name="arrow-left" />Kembali</a>
    @if (auth()->user()->isAdmin())
        <a href="{{ route('barang.edit', $barang) }}" data-turbo-frame="modal" class="btn-primary"><x-icon name="pencil" />Ubah</a>
    @endif
@endsection

@section('content')
    {{-- Ringkasan barang --}}
    <div class="card mb-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-5 p-6">
            @if ($barang->gambar_url)
                <a href="{{ $barang->gambar_url }}" target="_blank" rel="noopener" title="Lihat gambar ukuran penuh">
                    <img src="{{ $barang->gambar_url }}" alt="{{ $barang->nama_barang }}" class="size-28 rounded-2xl object-cover ring-1 ring-slate-200">
                </a>
            @else
                <div class="flex size-28 flex-col items-center justify-center gap-1 rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-200">
                    <x-icon name="photo" class="size-8" />
                    <span class="text-[11px]">Belum ada gambar</span>
                </div>
            @endif
            <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="kode">{{ $barang->kode_barang }}</span>
                    <x-badge :nilai="$barang->status_barang" />
                </div>
                <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $barang->nama_barang }}</h2>
                <p class="text-sm text-slate-500">{{ $barang->kategori->nama_kategori }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">Stok tersedia</p>
                <p @class(['text-4xl font-bold tracking-tight', 'text-rose-600' => $barang->stokMenipis(), 'text-slate-900' => ! $barang->stokMenipis()])>{{ $barang->stok }}</p>
                <p class="text-sm text-slate-500">{{ $barang->satuan }}</p>
            </div>
        </div>
        <dl class="grid grid-cols-2 divide-x divide-slate-100 border-t border-slate-100 bg-slate-50/60 text-sm sm:grid-cols-4">
            @foreach ([
                ['Lokasi', $barang->lokasi],
                ['Satuan', $barang->satuan],
                ['Total masuk', $barang->barangMasuk->sum('jumlah').' '.$barang->satuan],
                ['Total keluar (disetujui)', $barang->barangKeluar->where('verifikasi', 'Disetujui')->sum('jumlah').' '.$barang->satuan],
            ] as [$label, $nilai])
                <div class="px-6 py-4">
                    <dt class="text-xs text-slate-500">{{ $label }}</dt>
                    <dd class="mt-0.5 font-semibold text-slate-800">{{ $nilai }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2"><x-icon name="masuk" class="size-5 text-emerald-600" />Riwayat Barang Masuk</h3>
                <span class="text-xs text-slate-500">{{ $barang->barangMasuk->count() }} transaksi</span>
            </div>
            <div class="overflow-x-auto">
                <table class="table-data">
                    <thead><tr><th>Tanggal</th><th class="text-right">Jumlah</th><th>Sumber</th><th>Dicatat oleh</th></tr></thead>
                    <tbody>
                        @forelse ($barang->barangMasuk as $m)
                            <tr>
                                <td class="whitespace-nowrap">{{ $m->tanggal->translatedFormat('d M Y') }}</td>
                                <td class="text-right font-semibold text-emerald-600">+{{ $m->jumlah }}</td>
                                <td>{{ $m->sumber_barang }}</td>
                                <td>{{ $m->user->nama_user }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty icon="masuk" judul="Belum ada barang masuk" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2"><x-icon name="keluar" class="size-5 text-brand-600" />Riwayat Barang Keluar</h3>
                <span class="text-xs text-slate-500">{{ $barang->barangKeluar->count() }} transaksi</span>
            </div>
            <div class="overflow-x-auto">
                <table class="table-data">
                    <thead><tr><th>Tanggal</th><th class="text-right">Jumlah</th><th>Tujuan</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($barang->barangKeluar as $k)
                            <tr>
                                <td class="whitespace-nowrap"><a href="{{ route('barang-keluar.show', $k) }}" data-turbo-frame="modal" class="hover:text-brand-700">{{ $k->tanggal->translatedFormat('d M Y') }}</a></td>
                                <td class="text-right font-semibold">−{{ $k->jumlah }}</td>
                                <td>{{ $k->tujuan }}</td>
                                <td><x-badge :nilai="$k->verifikasi" /></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty icon="keluar" judul="Belum ada barang keluar" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
