@extends('layouts.app')

@section('title', 'Pemeriksaan Kondisi')
@section('subtitle', 'Menyesuaikan kondisi barang di data dengan kondisi fisiknya. Total stok tetap, diverifikasi Manager.')

@section('aksi')
    <button type="button" onclick="window.print()" class="btn-secondary"><x-icon name="printer" />Cetak</button>
    @if (auth()->user()->isOperator())
        <a href="{{ route('pemeriksaan-kondisi.create') }}" data-turbo-frame="modal" class="btn-primary"><x-icon name="plus" />Ajukan Pemeriksaan</a>
    @endif
@endsection

@section('content')
    <div class="card">
        {{-- Tab filter status verifikasi --}}
        <div class="flex gap-1 overflow-x-auto border-b border-slate-100 px-4 pt-3 print:hidden">
            @foreach (['' => 'Semua', 'Pending' => 'Pending', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak'] as $nilai => $label)
                @php($aktif = ($verifikasi ?? '') === $nilai)
                <a href="{{ route('pemeriksaan-kondisi.index', array_filter(['verifikasi' => $nilai])) }}"
                   @class([
                       '-mb-px border-b-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition',
                       'border-brand-600 text-brand-700' => $aktif,
                       'border-transparent text-slate-500 hover:text-slate-800' => ! $aktif,
                   ])>
                    {{ $label }}
                    @if ($nilai === 'Pending' && $jumlahPendingKondisi > 0)
                        <span class="ml-1 rounded-full bg-amber-100 px-1.5 text-xs text-amber-700">{{ $jumlahPendingKondisi }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto" tabindex="0" role="region" aria-label="Tabel pemeriksaan kondisi">
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Barang</th>
                        <th>Lokasi</th>
                        <th class="text-right">Jumlah</th>
                        <th>Perubahan Kondisi</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th class="text-right print:hidden">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pemeriksaan as $p)
                        <tr>
                            <td class="whitespace-nowrap">
                                <p class="font-medium text-slate-800">{{ $p->tanggal->translatedFormat('d M Y') }}</p>
                                <p class="text-xs text-slate-400">oleh {{ $p->user->nama_user }}</p>
                            </td>
                            <td>
                                <p class="font-semibold whitespace-nowrap text-slate-900">{{ $p->barang->nama_barang }}</p>
                                <p class="mt-0.5"><span class="kode">{{ $p->barang->kode_barang }}</span></p>
                            </td>
                            <td class="whitespace-nowrap text-slate-600">{{ $p->lokasi->nama_lokasi }}</td>
                            <td class="text-right font-semibold whitespace-nowrap text-slate-900">{{ $p->jumlah }} <span class="text-xs font-normal text-slate-400">{{ $p->barang->satuan }}</span></td>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <x-badge :nilai="$p->kondisi_asal" />
                                    <x-icon name="arrow-right" class="size-3.5 text-slate-400" />
                                    <x-badge :nilai="$p->kondisi_baru" />
                                </div>
                            </td>
                            <td class="max-w-56 text-sm text-slate-600">{{ $p->alasan }}</td>
                            <td><x-badge :nilai="$p->verifikasi" /></td>
                            <td class="print:hidden">
                                <div class="flex justify-end">
                                    @if ($p->isPending() && auth()->user()->isManager())
                                        <a href="{{ route('pemeriksaan-kondisi.show', $p) }}" data-turbo-frame="modal" class="btn-primary btn-sm"><x-icon name="clipboard" />Verifikasi</a>
                                    @else
                                        <a href="{{ route('pemeriksaan-kondisi.show', $p) }}" data-turbo-frame="modal" class="btn-icon" title="Detail"><x-icon name="eye" /></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-empty icon="clipboard" judul="Tidak ada data" pesan="Belum ada pemeriksaan kondisi dengan status ini." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pemeriksaan->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 print:hidden">{{ $pemeriksaan->links() }}</div>
        @endif
    </div>
@endsection
