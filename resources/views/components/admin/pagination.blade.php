{{-- Previous / next for admin lists, styled like the rest of the admin. Renders nothing for a
     list that fits on one page. --}}
@props(['paginator'])

@if($paginator->hasPages())
    <nav class="mt-5 flex flex-wrap items-center justify-between gap-3 text-sm" aria-label="{{ __('Pagination') }}">
        <p class="text-hub-dark/60">
            {{ __('Showing :from–:to of :total', ['from' => $paginator->firstItem(), 'to' => $paginator->lastItem(), 'total' => $paginator->total()]) }}
        </p>

        <div class="flex items-center gap-2">
            @if($paginator->onFirstPage())
                <span class="adm-btn adm-btn-secondary opacity-50 cursor-not-allowed" aria-disabled="true">{{ __('Previous') }}</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="adm-btn adm-btn-secondary">{{ __('Previous') }}</a>
            @endif

            <span class="px-2 text-hub-dark/60">{{ __('Page :page of :pages', ['page' => $paginator->currentPage(), 'pages' => $paginator->lastPage()]) }}</span>

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="adm-btn adm-btn-secondary">{{ __('Next') }}</a>
            @else
                <span class="adm-btn adm-btn-secondary opacity-50 cursor-not-allowed" aria-disabled="true">{{ __('Next') }}</span>
            @endif
        </div>
    </nav>
@endif
