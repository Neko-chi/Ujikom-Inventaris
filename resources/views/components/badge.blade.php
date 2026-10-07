{{-- Label berwarna untuk status_barang dan verifikasi. Pemakaian: <x-badge :nilai="$item->verifikasi" /> --}}
@props(['nilai'])

@php
    [$warna, $titik] = match ($nilai) {
        'Baik', 'Disetujui' => ['bg-emerald-50 text-emerald-700 ring-emerald-600/20', 'bg-emerald-500'],
        'Rusak Ringan', 'Pending' => ['bg-amber-50 text-amber-700 ring-amber-600/20', 'bg-amber-500'],
        'Rusak Berat', 'Ditolak' => ['bg-rose-50 text-rose-700 ring-rose-600/20', 'bg-rose-500'],
        default => ['bg-slate-50 text-slate-700 ring-slate-600/20', 'bg-slate-400'],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset $warna"]) }}>
    <span class="size-1.5 rounded-full {{ $titik }}"></span>{{ $nilai }}
</span>
