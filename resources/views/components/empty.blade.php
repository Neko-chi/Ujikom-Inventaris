{{-- Tampilan saat data kosong. Pemakaian: <x-empty icon="cube" judul="..." pesan="..." /> --}}
@props(['icon' => 'archive', 'judul' => 'Belum ada data', 'pesan' => null])

<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
        <x-icon :name="$icon" class="size-6" />
    </div>
    <p class="text-sm font-semibold text-slate-700">{{ $judul }}</p>
    @if ($pesan)
        <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $pesan }}</p>
    @endif
    {{ $slot }}
</div>
