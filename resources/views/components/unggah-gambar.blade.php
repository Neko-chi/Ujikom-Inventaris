{{--
    Input unggah gambar dengan pratinjau sebelum disimpan.
    Pemakaian: <x-unggah-gambar name="foto" label="Foto Bukti" :url="$data->foto_url" wajib />
    - url   : gambar yang sudah tersimpan (mode ubah), boleh kosong
    - wajib : gambar harus diisi
    - hapus : nama checkbox untuk menghapus gambar lama (opsional)
--}}
@props(['name', 'label', 'url' => null, 'wajib' => false, 'hapus' => null, 'keterangan' => null])

<div>
    <span class="label">{{ $label }} @if ($wajib)<span class="text-rose-500">*</span>@else<span class="font-normal text-slate-400">(opsional)</span>@endif</span>

    <div class="flex flex-wrap items-start gap-4">
        {{-- Area pratinjau --}}
        <div class="relative flex size-32 shrink-0 items-center justify-center overflow-hidden rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50">
            <img id="pratinjau-{{ $name }}" src="{{ $url }}" alt="Pratinjau {{ strtolower($label) }}"
                 @class(['size-full object-cover', 'hidden' => ! $url])>
            <div id="kosong-{{ $name }}" @class(['flex flex-col items-center gap-1 text-slate-400', 'hidden' => $url])>
                <x-icon name="photo" class="size-8" />
                <span class="text-[11px]">Belum ada gambar</span>
            </div>
        </div>

        <div class="min-w-0 flex-1 space-y-2">
            <label for="{{ $name }}" class="btn-secondary cursor-pointer">
                <x-icon name="upload" />{{ $url ? 'Ganti gambar' : 'Pilih gambar' }}
            </label>
            <input id="{{ $name }}" name="{{ $name }}" type="file" accept="image/jpeg,image/png,image/webp"
                   class="sr-only" data-pratinjau="{{ $name }}" @required($wajib)>
            <p id="nama-file-{{ $name }}" class="truncate text-xs text-slate-600"></p>
            <p class="hint mt-0!">{{ $keterangan ?? 'Format JPG, PNG, atau WEBP. Maksimal 2 MB.' }}</p>

            @if ($hapus && $url)
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="{{ $hapus }}" value="1" class="size-4 rounded accent-rose-600">
                    Hapus gambar ini
                </label>
            @endif
        </div>
    </div>
    <x-error :field="$name" />
</div>
