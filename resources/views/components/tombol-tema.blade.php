{{-- Tombol ganti mode terang / gelap. Ikon matahari tampil di mode gelap, bulan di mode terang. --}}
<button type="button" data-ganti-tema title="Ganti mode terang / gelap" aria-label="Ganti mode terang / gelap"
        {{ $attributes->merge(['class' => 'inline-flex size-9 cursor-pointer items-center justify-center rounded-xl text-slate-500 ring-1 ring-slate-200 transition hover:bg-slate-100 hover:text-slate-900']) }}>
    <x-icon name="moon" class="size-5 dark:hidden" />
    <x-icon name="sun" class="hidden size-5 dark:block" />
</button>
