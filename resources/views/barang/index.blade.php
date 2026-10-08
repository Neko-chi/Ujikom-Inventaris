@extends('layouts.app')

@section('title', 'Data Barang')
@section('subtitle', 'Daftar seluruh barang di gudang beserta stok dan kondisinya.')

@section('aksi')
    <button type="button" onclick="window.print()" class="btn-secondary"><x-icon name="printer" />Cetak</button>
    @unless (auth()->user()->isManager())
        <a href="{{ route('barang.create') }}" class="btn-primary"><x-icon name="plus" />Tambah Barang</a>
    @endunless
@endsection

@section('content')
    <div class="card">
        {{-- Filter pencarian --}}
        <form method="GET" action="{{ route('barang.index') }}" class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 print:hidden">
            <div class="relative min-w-56 flex-1">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-slate-400" />
                <input id="cari" name="cari" type="search" class="input pl-10" value="{{ $cari }}" placeholder="Cari nama atau kode barang..." aria-label="Cari barang">
            </div>
            <select id="kategori" name="kategori" class="input w-auto min-w-48" aria-label="Filter kategori">
                <option value="">Semua kategori</option>
                @foreach ($kategori as $k)
                    <option value="{{ $k->id_kategori }}" @selected($idKategori == $k->id_kategori)>{{ $k->nama_kategori }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn-primary"><x-icon name="search" />Cari</button>
            @if ($cari || $idKategori)
                <a href="{{ route('barang.index') }}" class="btn-secondary"><x-icon name="refresh" />Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Barang</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th class="text-right">Stok</th>
                        <th>Kondisi</th>
                        <th class="text-right print:hidden">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($barang as $b)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($b->gambar_url)
                                        <img src="{{ $b->gambar_url }}" alt="{{ $b->nama_barang }}" class="size-11 shrink-0 rounded-xl object-cover ring-1 ring-slate-200 print:hidden" loading="lazy">
                                    @else
                                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-400 print:hidden">
                                            <x-icon name="cube" class="size-5" />
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('barang.show', $b) }}" class="font-semibold whitespace-nowrap text-slate-900 hover:text-brand-700">{{ $b->nama_barang }}</a>
                                        <p class="mt-0.5"><span class="kode">{{ $b->kode_barang }}</span></p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">{{ $b->kategori->nama_kategori }}</td>
                            <td>
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-slate-600"><x-icon name="map-pin" class="size-4 text-slate-400" />{{ $b->lokasi }}</span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <span @class(['font-semibold', 'text-rose-600' => $b->stokMenipis(), 'text-slate-900' => ! $b->stokMenipis()])>{{ $b->stok }}</span>
                                <span class="text-xs text-slate-400">{{ $b->satuan }}</span>
                                @if ($b->stokMenipis())
                                    <p class="text-[11px] font-medium text-rose-500">Stok menipis</p>
                                @endif
                            </td>
                            <td><x-badge :nilai="$b->status_barang" /></td>
                            <td class="print:hidden">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('barang.show', $b) }}" class="btn-icon" title="Detail"><x-icon name="eye" /></a>
                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('barang.edit', $b) }}" class="btn-icon" title="Ubah"><x-icon name="pencil" /></a>
                                        <form action="{{ route('barang.destroy', $b) }}" method="POST" onsubmit="return confirm(@js('Hapus barang '.$b->nama_barang.'?'))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon hover:bg-rose-50 hover:text-rose-600" title="Hapus"><x-icon name="trash" /></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <x-empty icon="search" judul="Barang tidak ditemukan" pesan="Coba ubah kata kunci atau filter kategori." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($barang->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 print:hidden">{{ $barang->links() }}</div>
        @endif
    </div>
@endsection
