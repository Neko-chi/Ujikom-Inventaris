{{-- Tombol putar / jeda musik "Bad Apple!!" (pemutar ada di partials/pemutar-musik, logika di resources/js/musik.js) --}}
<button type="button" data-musik-toggle aria-pressed="false" title="Putar / jeda musik Bad Apple!!" aria-label="Putar / jeda musik Bad Apple!!"
        {{ $attributes->merge(['class' => 'inline-flex size-9 cursor-pointer items-center justify-center rounded-xl text-slate-500 ring-1 ring-slate-200 transition hover:bg-slate-100 hover:text-slate-900 aria-pressed:bg-brand-600 aria-pressed:text-on-brand aria-pressed:ring-brand-600']) }}>
    <x-icon name="musik" class="size-5" data-ikon-musik />
    <x-icon name="pause" class="hidden size-5" data-ikon-jeda />
</button>
