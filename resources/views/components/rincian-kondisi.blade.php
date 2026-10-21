{{--
    Rincian jumlah per kondisi sebagai label berwarna, kondisi bernilai 0 tidak ditampilkan.
    Pemakaian: <x-rincian-kondisi :rincian="$barang->stokPerKondisi()" />
--}}
@props(['rincian' => []])

@php
    $warna = [
        'Baik' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        'Rusak Ringan' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        'Rusak Berat' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
    ];
    $ada = array_filter($rincian, fn ($j) => $j > 0);
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1']) }}>
    @forelse ($ada as $kondisi => $jumlah)
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium whitespace-nowrap ring-1 ring-inset {{ $warna[$kondisi] ?? '' }}">{{ $jumlah }} {{ $kondisi }}</span>
    @empty
        <span class="text-xs text-slate-400">-</span>
    @endforelse
</div>
