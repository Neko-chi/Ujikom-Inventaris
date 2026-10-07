{{-- Pesan hasil proses (sukses / gagal) yang dikirim controller lewat session --}}
@foreach (['sukses' => ['check-circle', 'border-emerald-200 bg-emerald-50 text-emerald-800', 'text-emerald-500'], 'gagal' => ['x-circle', 'border-rose-200 bg-rose-50 text-rose-800', 'text-rose-500']] as $jenis => [$ikon, $warna, $warnaIkon])
    @if (session($jenis))
        <div data-alert class="mb-6 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $warna }} print:hidden" role="alert">
            <x-icon :name="$ikon" class="mt-px size-5 {{ $warnaIkon }}" />
            <p class="flex-1">{{ session($jenis) }}</p>
            <button type="button" data-dismiss class="rounded-md p-0.5 opacity-60 hover:opacity-100" aria-label="Tutup">
                <x-icon name="x" class="size-4" />
            </button>
        </div>
    @endif
@endforeach
