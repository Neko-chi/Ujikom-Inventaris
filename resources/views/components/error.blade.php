{{-- Pesan error validasi untuk satu field. Pemakaian: <x-error field="nama_barang" /> --}}
@props(['field'])

@error($field)
    <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600">
        <x-icon name="warning" class="size-3.5" />{{ $message }}
    </p>
@enderror
