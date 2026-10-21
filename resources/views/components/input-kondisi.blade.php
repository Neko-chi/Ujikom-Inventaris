{{--
    Input jumlah per kondisi (Baik / Rusak Ringan / Rusak Berat) beserta totalnya.
    Nama input: jumlah_baik, jumlah_rusak_ringan, jumlah_rusak_berat (Barang::KOLOM_KONDISI).
    Pemakaian: <x-input-kondisi :nilai="$transaksi->rincian()" label="Jumlah per Kondisi" />
    - :nilai     nilai awal ['Baik' => 5, ...]
    - :hanya     daftar kondisi yang boleh diisi (lainnya dikunci 0), mis. ['Rusak Berat'] untuk pemusnahan
    - satuan     teks satuan untuk total (opsional)
--}}
@props(['nilai' => [], 'label' => 'Jumlah per Kondisi', 'hanya' => null, 'satuan' => null, 'petunjuk' => null])

@php
    $warna = [
        'Baik' => 'bg-emerald-500',
        'Rusak Ringan' => 'bg-amber-500',
        'Rusak Berat' => 'bg-rose-500',
    ];
    $kolomError = array_values(\App\Models\Barang::KOLOM_KONDISI);
@endphp

<div data-input-kondisi {{ $attributes }}>
    <span class="label">{{ $label }}</span>
    <div class="grid grid-cols-3 gap-3">
        @foreach (\App\Models\Barang::KOLOM_KONDISI as $kondisi => $kolom)
            @php($terkunci = $hanya !== null && ! in_array($kondisi, $hanya, true))
            <div>
                <label for="{{ $kolom }}" class="mb-1 flex items-center gap-1.5 text-xs font-medium text-slate-600">
                    <span class="size-2 rounded-full {{ $warna[$kondisi] }}"></span>{{ $kondisi }}
                </label>
                <input id="{{ $kolom }}" name="{{ $kolom }}" type="number" min="0" inputmode="numeric"
                       class="input text-center tabular-nums" data-jumlah-kondisi="{{ $kondisi }}"
                       value="{{ $terkunci ? 0 : old($kolom, $nilai[$kondisi] ?? 0) }}" @readonly($terkunci) @class(['opacity-50' => $terkunci])>
            </div>
        @endforeach
    </div>
    <p class="hint flex flex-wrap items-center justify-between gap-2">
        <span>{{ $petunjuk ?? 'Isi 0 untuk kondisi yang tidak ada.' }}</span>
        <span>Total: <strong class="text-slate-900" data-total-kondisi>{{ array_sum(array_map('intval', $nilai)) }}</strong> {{ $satuan }}</span>
    </p>
    @foreach (array_merge(['jumlah'], $kolomError) as $f)
        <x-error :field="$f" />
    @endforeach
</div>
