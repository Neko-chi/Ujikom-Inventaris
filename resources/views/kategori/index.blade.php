@extends('layouts.app')

@section('title', 'Kategori')
@section('subtitle', 'Pengelompokan barang beserta prefix kode barangnya.')

@section('aksi')
    <a href="{{ route('kategori.create') }}" class="btn-primary"><x-icon name="plus" />Tambah Kategori</a>
@endsection

@section('content')
    @if ($kategori->isEmpty())
        <div class="card">
            <x-empty icon="tag" judul="Belum ada kategori" pesan="Tambahkan kategori terlebih dahulu sebelum mendaftarkan barang." />
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($kategori as $k)
                <div class="card group flex flex-col p-5 transition hover:border-brand-200 hover:shadow-md">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex size-12 items-center justify-center rounded-xl bg-brand-50 font-mono text-sm font-bold text-brand-700 ring-1 ring-brand-100">
                            {{ \App\Models\Barang::prefixKategori($k->nama_kategori) }}
                        </div>
                        <div class="flex gap-1">
                            <a href="{{ route('kategori.edit', $k) }}" class="btn-icon" title="Ubah"><x-icon name="pencil" /></a>
                            <form action="{{ route('kategori.destroy', $k) }}" method="POST" onsubmit="return confirm(@js('Hapus kategori '.$k->nama_kategori.'?'))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon hover:bg-rose-50 hover:text-rose-600" title="Hapus"><x-icon name="trash" /></button>
                            </form>
                        </div>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $k->nama_kategori }}</h3>
                    <p class="mt-1 text-sm text-slate-500">Kode barang: <span class="kode">{{ \App\Models\Barang::prefixKategori($k->nama_kategori) }}-001</span> dst.</p>
                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
                        <span class="text-slate-500">Jumlah barang</span>
                        <a href="{{ route('barang.index', ['kategori' => $k->id_kategori]) }}" class="font-semibold text-brand-600 hover:text-brand-700">{{ $k->barang_count }} jenis &rarr;</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
