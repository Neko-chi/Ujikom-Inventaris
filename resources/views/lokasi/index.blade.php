@extends('layouts.app')

@section('title', 'Lokasi')
@section('subtitle', 'Tempat penyimpanan barang: gudang dan ruangan (lab, kelas, kantor).')

@section('aksi')
    <a href="{{ route('lokasi.create') }}" data-turbo-frame="modal" class="btn-primary"><x-icon name="plus" />Tambah Lokasi</a>
@endsection

@section('content')
    @if ($lokasi->isEmpty())
        <div class="card">
            <x-empty icon="map-pin" judul="Belum ada lokasi" pesan="Tambahkan lokasi gudang atau ruangan terlebih dahulu." />
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($lokasi as $l)
                <div class="card flex flex-col p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div @class([
                            'flex size-12 items-center justify-center rounded-xl ring-1',
                            'bg-brand-50 text-brand-700 ring-brand-100' => $l->jenis === \App\Models\Lokasi::GUDANG,
                            'bg-violet-50 text-violet-700 ring-violet-200' => $l->jenis === \App\Models\Lokasi::RUANGAN,
                        ])>
                            <x-icon :name="$l->jenis === \App\Models\Lokasi::GUDANG ? 'archive' : 'map-pin'" class="size-6" />
                        </div>
                        <div class="flex gap-1">
                            <a href="{{ route('lokasi.edit', $l) }}" data-turbo-frame="modal" class="btn-icon" title="Ubah"><x-icon name="pencil" /></a>
                            <form action="{{ route('lokasi.destroy', $l) }}" method="POST" onsubmit="return confirm(@js('Hapus lokasi '.$l->nama_lokasi.'?'))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon hover:bg-rose-50 hover:text-rose-600" title="Hapus"><x-icon name="trash" /></button>
                            </form>
                        </div>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $l->nama_lokasi }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $l->jenis }}{{ $l->keterangan ? ' · '.$l->keterangan : '' }}</p>
                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4 text-sm">
                        <span class="text-slate-500">{{ $jenisBarang[$l->id_lokasi] ?? 0 }} jenis barang</span>
                        <a href="{{ route('barang.index', ['lokasi' => $l->id_lokasi]) }}" class="font-semibold text-brand-600 hover:text-brand-700">{{ number_format((int) $l->total_stok, 0, ',', '.') }} unit &rarr;</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
