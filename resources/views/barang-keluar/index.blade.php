@extends('layouts.app')

@section('title', 'Barang Keluar')
@section('subtitle', 'Permintaan pengeluaran barang dan status verifikasinya.')

@section('aksi')
    <button type="button" onclick="window.print()" class="btn-secondary"><x-icon name="printer" />Cetak</button>
    @if (auth()->user()->isOperator())
        <a href="{{ route('barang-keluar.create') }}" class="btn-primary"><x-icon name="plus" />Ajukan Barang Keluar</a>
    @endif
@endsection

@section('content')
    <div class="card">
        {{-- Tab filter status verifikasi --}}
        <div class="flex gap-1 overflow-x-auto border-b border-slate-100 px-4 pt-3 print:hidden">
            @foreach (['' => 'Semua', 'Pending' => 'Pending', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak'] as $nilai => $label)
                @php($aktif = ($verifikasi ?? '') === $nilai)
                <a href="{{ route('barang-keluar.index', $nilai ? ['verifikasi' => $nilai] : []) }}"
                   @class([
                       '-mb-px border-b-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition',
                       'border-brand-600 text-brand-700' => $aktif,
                       'border-transparent text-slate-500 hover:text-slate-800' => ! $aktif,
                   ])>
                    {{ $label }}
                    @if ($nilai === 'Pending' && $jumlahPending > 0)
                        <span class="ml-1 rounded-full bg-amber-100 px-1.5 text-xs text-amber-700">{{ $jumlahPending }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th class="text-right">Jumlah</th>
                        <th>Bukti</th>
                        <th>Pemohon</th>
                        <th>Verifikasi</th>
                        <th class="text-right print:hidden">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($barangKeluar as $k)
                        <tr>
                            <td class="whitespace-nowrap">
                                <p class="font-medium text-slate-800">{{ $k->tanggal->translatedFormat('d M Y') }}</p>
                                <p class="text-xs text-slate-400">oleh {{ $k->user->nama_user }}</p>
                            </td>
                            <td>
                                <p class="font-semibold whitespace-nowrap text-slate-900">{{ $k->barang->nama_barang }}</p>
                                <p class="mt-0.5 flex items-center gap-1.5 text-xs whitespace-nowrap text-slate-500"><span class="kode">{{ $k->barang->kode_barang }}</span> stok {{ $k->barang->stok }}</p>
                            </td>
                            <td class="text-right font-semibold whitespace-nowrap text-slate-900">{{ $k->jumlah }} <span class="text-xs font-normal text-slate-400">{{ $k->barang->satuan }}</span></td>
                            <td>
                                @if ($k->foto_url)
                                    <a href="{{ $k->foto_url }}" target="_blank" rel="noopener" title="Lihat foto bukti">
                                        <img src="{{ $k->foto_url }}" alt="Foto bukti" class="size-10 rounded-lg object-cover ring-1 ring-slate-200" loading="lazy">
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                <p class="font-medium text-slate-800">{{ $k->pemohon }}</p>
                                <p class="flex items-center gap-1 text-xs text-slate-500"><x-icon name="map-pin" class="size-3.5" />{{ $k->tujuan }}</p>
                            </td>
                            <td>
                                <x-badge :nilai="$k->verifikasi" />
                                @if ($k->verifikator)
                                    <p class="mt-1 text-xs whitespace-nowrap text-slate-400">oleh {{ $k->verifikator->nama_user }}</p>
                                @endif
                            </td>
                            <td class="print:hidden">
                                <div class="flex justify-end">
                                    @if ($k->isPending() && auth()->user()->isManager())
                                        <a href="{{ route('barang-keluar.show', $k) }}" class="btn-primary btn-sm"><x-icon name="clipboard" />Verifikasi</a>
                                    @else
                                        <a href="{{ route('barang-keluar.show', $k) }}" class="btn-icon" title="Detail"><x-icon name="eye" /></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <x-empty icon="keluar" judul="Tidak ada permintaan" pesan="Belum ada permintaan barang keluar dengan status ini." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($barangKeluar->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 print:hidden">{{ $barangKeluar->links() }}</div>
        @endif
    </div>
@endsection
