@extends('layouts.app')

@section('title', 'Barang Keluar')
@section('subtitle', 'Peminjaman, pemindahan ke ruangan lain, dan pemusnahan barang beserta status verifikasinya.')

@section('aksi')
    <button type="button" onclick="window.print()" class="btn-secondary"><x-icon name="printer" />Cetak</button>
    @if (auth()->user()->isOperator())
        <a href="{{ route('barang-keluar.create') }}" data-turbo-frame="modal" class="btn-primary"><x-icon name="plus" />Ajukan Barang Keluar</a>
    @endif
@endsection

@section('content')
    <div class="card">
        {{-- Tab filter status verifikasi --}}
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-100 px-4 pt-3 print:hidden">
            <div class="flex gap-1 overflow-x-auto">
            @foreach (['' => 'Semua', 'Pending' => 'Pending', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak', 'Dipinjam' => 'Sedang Dipinjam'] as $nilai => $label)
                @php($aktif = ($verifikasi ?? '') === $nilai)
                <a href="{{ route('barang-keluar.index', array_filter(['verifikasi' => $nilai, 'jenis' => $jenis])) }}"
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

            {{-- Filter jenis barang keluar --}}
            <form method="GET" action="{{ route('barang-keluar.index') }}" class="mb-2 flex items-center gap-2">
                @if ($verifikasi)
                    <input type="hidden" name="verifikasi" value="{{ $verifikasi }}">
                @endif
                <select name="jenis" class="input w-auto py-1.5! text-xs" aria-label="Filter jenis" onchange="this.form.requestSubmit()">
                    <option value="">Semua jenis</option>
                    @foreach (\App\Models\BarangKeluar::JENIS as $j)
                        <option value="{{ $j }}" @selected($jenis === $j)>{{ $j }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="table-data">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Barang</th>
                        <th class="text-right">Jumlah</th>
                        <th>Lokasi</th>
                        <th>Peminjam / Penanggung Jawab</th>
                        <th>Status</th>
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
                            <td><x-badge :nilai="$k->jenis_keluar" /></td>
                            <td>
                                <p class="font-semibold whitespace-nowrap text-slate-900">{{ $k->barang->nama_barang }}</p>
                                <p class="mt-0.5"><span class="kode">{{ $k->barang->kode_barang }}</span></p>
                            </td>
                            <td class="text-right">
                                <p class="font-semibold whitespace-nowrap text-slate-900">{{ $k->jumlah }} <span class="text-xs font-normal text-slate-400">{{ $k->barang->satuan }}</span></p>
                                <x-rincian-kondisi :rincian="$k->rincian()" class="mt-1 justify-end" />
                            </td>
                            <td class="text-xs whitespace-nowrap text-slate-600">
                                <p class="flex items-center gap-1"><x-icon name="archive" class="size-3.5 text-slate-400" />{{ $k->lokasiAsal?->nama_lokasi ?? '-' }}</p>
                                @if ($k->lokasiTujuan)
                                    <p class="mt-0.5 flex items-center gap-1"><x-icon name="arrow-right" class="size-3.5 text-slate-400" />{{ $k->lokasiTujuan->nama_lokasi }}</p>
                                @endif
                            </td>
                            <td>
                                <p class="font-medium text-slate-800">{{ $k->pemohon }}</p>
                                <p class="text-xs text-slate-500">{{ $k->tujuan }}</p>
                            </td>
                            <td class="space-y-1">
                                <x-badge :nilai="$k->verifikasi" />
                                @if ($k->statusPinjam())
                                    <div><x-badge :nilai="$k->statusPinjam()" /></div>
                                @endif
                                @if ($k->terlambat())
                                    <div><x-badge nilai="Terlambat" /></div>
                                @endif
                            </td>
                            <td class="print:hidden">
                                <div class="flex justify-end">
                                    @if ($k->isPending() && auth()->user()->isManager())
                                        <a href="{{ route('barang-keluar.show', $k) }}" data-turbo-frame="modal" class="btn-primary btn-sm"><x-icon name="clipboard" />Verifikasi</a>
                                    @else
                                        <a href="{{ route('barang-keluar.show', $k) }}" data-turbo-frame="modal" class="btn-icon" title="Detail"><x-icon name="eye" /></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-empty icon="keluar" judul="Tidak ada data" pesan="Belum ada barang keluar dengan status / jenis ini." />
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
