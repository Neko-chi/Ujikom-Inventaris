@extends('layouts.app')

@section('title', 'Barang Masuk')
@section('subtitle', 'Riwayat penerimaan barang ke gudang. Setiap barang masuk langsung menambah stok.')

@section('aksi')
    <button type="button" onclick="window.print()" class="btn-secondary"><x-icon name="printer" />Cetak</button>
    @if (auth()->user()->isOperator())
        <a href="{{ route('barang-masuk.create') }}" data-turbo-frame="modal" class="btn-primary"><x-icon name="plus" />Catat Barang Masuk</a>
    @endif
@endsection

@section('content')
    <div class="card">
        {{-- Filter tanggal --}}
        <form method="GET" action="{{ route('barang-masuk.index') }}" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-4 print:hidden">
            <div>
                <label for="dari" class="label text-xs!">Dari tanggal</label>
                <input id="dari" name="dari" type="date" class="input" value="{{ $dari }}">
            </div>
            <div>
                <label for="sampai" class="label text-xs!">Sampai tanggal</label>
                <input id="sampai" name="sampai" type="date" class="input" value="{{ $sampai }}">
            </div>
            <button type="submit" class="btn-primary"><x-icon name="calendar" />Tampilkan</button>
            @if ($dari || $sampai)
                <a href="{{ route('barang-masuk.index') }}" class="btn-secondary"><x-icon name="refresh" />Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th class="text-right">Jumlah</th>
                        <th>Sumber Barang</th>
                        <th>Bukti</th>
                        <th>Kondisi</th>
                        <th>Dicatat oleh</th>
                        @if (auth()->user()->isAdmin())
                            <th class="text-right print:hidden">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($barangMasuk as $m)
                        <tr>
                            <td class="whitespace-nowrap">
                                <p class="font-medium text-slate-800">{{ $m->tanggal->translatedFormat('d M Y') }}</p>
                                <p class="text-xs text-slate-400">{{ $m->tanggal->translatedFormat('l') }}</p>
                            </td>
                            <td>
                                <a href="{{ route('barang.show', $m->barang) }}" data-turbo-frame="modal" class="font-semibold whitespace-nowrap text-slate-900 hover:text-brand-700">{{ $m->barang->nama_barang }}</a>
                                <p class="mt-0.5"><span class="kode">{{ $m->barang->kode_barang }}</span></p>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2 py-1 text-sm font-semibold text-emerald-700">+{{ $m->jumlah }} <span class="text-xs font-normal">{{ $m->barang->satuan }}</span></span>
                            </td>
                            <td>{{ $m->sumber_barang }}</td>
                            <td>
                                @if ($m->foto_url)
                                    <a href="{{ $m->foto_url }}" target="_blank" rel="noopener" title="Lihat foto bukti">
                                        <img src="{{ $m->foto_url }}" alt="Foto bukti" class="size-10 rounded-lg object-cover ring-1 ring-slate-200" loading="lazy">
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">&mdash;</span>
                                @endif
                            </td>
                            <td><x-badge :nilai="$m->status_barang" /></td>
                            <td>
                                <span class="inline-flex items-center gap-2 whitespace-nowrap">
                                    <span class="flex size-7 items-center justify-center rounded-full bg-slate-100 text-[10px] font-bold text-slate-600">{{ $m->user->inisial() }}</span>
                                    {{ $m->user->nama_user }}
                                </span>
                            </td>
                            @if (auth()->user()->isAdmin())
                                <td class="print:hidden">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('barang-masuk.edit', $m) }}" data-turbo-frame="modal" class="btn-icon" title="Koreksi"><x-icon name="pencil" /></a>
                                        <form action="{{ route('barang-masuk.destroy', $m) }}" method="POST" onsubmit="return confirm('Hapus data ini? Stok barang akan dikurangi kembali.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon hover:bg-rose-50 hover:text-rose-600" title="Hapus"><x-icon name="trash" /></button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-empty icon="masuk" judul="Belum ada data barang masuk" pesan="Data akan muncul setelah Operator mencatat penerimaan barang." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($barangMasuk->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 print:hidden">{{ $barangMasuk->links() }}</div>
        @endif
    </div>
@endsection
