{{-- resources/views/admin/audience-tabs/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Who it is for'))

@section('content')
    <x-admin.page-header :title="__('Who it is for')">
        <x-admin.button href="{{ route('home') }}#audiences" variant="secondary" target="_blank" rel="noopener">{{ __('View on site') }}</x-admin.button>
        <x-admin.button href="{{ route('admin.audience-tabs.create') }}">{{ __('New Tab') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-hub-dark/60 mb-6 max-w-2xl">
        {{ __('Each tab is one side of the audience switch on the Creators Hub page, and holds its own cards. Add as many as you need — the heading above the switch is edited under Landing Page Content.') }}
    </p>

    @if(session('status'))
        <p class="mb-6 text-sm font-bold text-hub-purple">{{ session('status') }}</p>
    @endif

    @if($tabs->isEmpty())
        <x-admin.empty-state :message="__('No tabs yet. The section stays hidden on the site until you add one.')" />
    @else
        <div
            class="flex flex-col gap-4"
            data-sortable
            data-sortable-error="{{ __('The new order could not be saved. Reload and try again.') }}"
            data-sortable-url="{{ route('admin.audience-tabs.reorder') }}"
        >
            @foreach($tabs as $tab)
                <div class="adm-card p-5" data-sortable-item="{{ $tab->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <button type="button" class="adm-drag-handle" aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">⠿</button>

                            <div class="min-w-0">
                                <h2 class="font-display font-bold text-lg text-hub-purple truncate">{{ $tab->label_en }}</h2>
                                @if($tab->lede_en)
                                    <p class="text-sm text-hub-dark/55 mt-0.5">{{ $tab->lede_en }}</p>
                                @endif
                                <p class="text-xs text-hub-dark/40 mt-1">
                                    {{ trans_choice('{0} No cards|{1} :count card|[2,*] :count cards', $tab->cards->count(), ['count' => $tab->cards->count()]) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <a href="{{ route('admin.audience-tabs.edit', $tab) }}" class="text-sm font-semibold text-hub-purple hover:underline">{{ __('Edit tab and cards') }}</a>
                            <form method="POST" action="{{ route('admin.audience-tabs.destroy', $tab) }}" onsubmit="return confirm('{{ __('Delete this tab and all of its cards? This cannot be undone.') }}')">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger">{{ __('Delete') }}</x-admin.button>
                            </form>
                        </div>
                    </div>

                    {{-- The cards as thumbnails, so a tab can be recognised by its pictures
                         rather than only by reading down a list of titles. --}}
                    @if($tab->cards->isNotEmpty())
                        <div class="flex flex-wrap gap-2 mt-4 ps-9">
                            @foreach($tab->cards as $card)
                                <a href="{{ route('admin.audience-tabs.cards.edit', [$tab, $card]) }}" class="adm-thumb" title="{{ $card->title_en }}">
                                    @if($card->imageUrl())
                                        <img src="{{ $card->imageUrl() }}" alt="">
                                    @else
                                        <span class="adm-thumb-empty">{{ mb_substr((string) $card->title_en, 0, 1) }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
