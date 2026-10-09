{{-- Navigasi halaman (dipasang sebagai default lewat AppServiceProvider) --}}
@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3 text-sm" aria-label="Navigasi halaman">
        <p class="text-slate-500">
            Menampilkan <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            dari <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span> data
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn-icon cursor-not-allowed opacity-40"><x-icon name="chevron-left" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn-icon" aria-label="Sebelumnya"><x-icon name="chevron-left" /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-slate-400">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="inline-flex size-8 items-center justify-center rounded-lg bg-brand-600 text-xs font-semibold text-on-brand">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="inline-flex size-8 items-center justify-center rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-100">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn-icon" aria-label="Berikutnya"><x-icon name="chevron-right" /></a>
            @else
                <span class="btn-icon cursor-not-allowed opacity-40"><x-icon name="chevron-right" /></span>
            @endif
        </div>
    </nav>
@endif
