{{-- resources/views/admin/audience-tabs/form.blade.php --}}
@extends('layouts.admin')

@php $editing = $tab->exists; @endphp

@section('title', $editing ? $tab->label_en : __('New Tab'))

@section('content')
    <x-admin.page-header :title="$editing ? __('Edit tab') : __('New Tab')">
        <x-admin.button href="{{ route('admin.audience-tabs.index') }}" variant="secondary">{{ __('Back') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('status'))
        <p class="mb-6 text-sm font-bold text-hub-purple">{{ session('status') }}</p>
    @endif

    {{-- Form on the left, the tab drawn as the site draws it on the right, updating as you
         type. It is the real card layout at a smaller size, not a diagram of it. --}}
    <div
        class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_24rem] gap-8 items-start"
        x-data="{
            label: @js(old('label_en', $tab->label_en)),
            lede: @js(old('lede_en', $tab->lede_en)),
            cta: @js(old('cta_en', $tab->cta_en)),
            init() {
                for (const [field, name] of [['label', 'label_en'], ['lede', 'lede_en'], ['cta', 'cta_en']]) {
                    const input = this.$el.querySelector('[name=&quot;' + name + '&quot;]');
                    input?.addEventListener('input', () => { this[field] = input.value; });
                }
            },
        }"
    >
        <div>
            <form method="POST" action="{{ $editing ? route('admin.audience-tabs.update', $tab) : route('admin.audience-tabs.store') }}" class="adm-card p-6">
                @csrf
                @if($editing) @method('PUT') @endif

                <x-admin.bilingual-field
                    name="label"
                    :label="__('Tab label')"
                    :value-ar="old('label_ar', $tab->label_ar)"
                    :value-en="old('label_en', $tab->label_en)"
                />

                <x-admin.bilingual-field
                    type="textarea"
                    name="lede"
                    :label="__('Intro line')"
                    :value-ar="old('lede_ar', $tab->lede_ar)"
                    :value-en="old('lede_en', $tab->lede_en)"
                />

                <x-admin.bilingual-field
                    name="cta"
                    :label="__('Button text')"
                    :value-ar="old('cta_ar', $tab->cta_ar)"
                    :value-en="old('cta_en', $tab->cta_en)"
                />

                <p class="text-xs text-hub-dark/45 mb-5">{{ __('The button links to the contact section. Leave it empty to show no button.') }}</p>

                <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
            </form>

            @if($editing)
                <div class="flex flex-wrap items-center justify-between gap-3 mt-10 mb-4">
                    <h2 class="font-display font-bold text-lg text-hub-purple">{{ __('Cards') }}</h2>
                    <x-admin.button href="{{ route('admin.audience-tabs.cards.create', $tab) }}">{{ __('New Card') }}</x-admin.button>
                </div>

                @if($tab->cards->isEmpty())
                    <x-admin.empty-state :message="__('No cards in this tab yet.')" />
                @else
                    <div
                        class="flex flex-col gap-3"
                        data-sortable
                        data-sortable-error="{{ __('The new order could not be saved. Reload and try again.') }}"
                        data-sortable-url="{{ route('admin.audience-tabs.cards.reorder', $tab) }}"
                    >
                        @foreach($tab->cards as $card)
                            <div class="adm-card p-4 flex items-center gap-4" data-sortable-item="{{ $card->id }}">
                                <button type="button" class="adm-drag-handle" aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">⠿</button>

                                <div class="adm-thumb shrink-0">
                                    @if($card->imageUrl())
                                        <img src="{{ $card->imageUrl() }}" alt="">
                                    @else
                                        <span class="adm-thumb-empty">{{ mb_substr((string) $card->title_en, 0, 1) }}</span>
                                    @endif
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold truncate">{{ $card->title_en }}</p>
                                    @if($card->body_en)
                                        <p class="text-sm text-hub-dark/50 truncate">{{ $card->body_en }}</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    <a href="{{ route('admin.audience-tabs.cards.edit', [$tab, $card]) }}" class="text-sm font-semibold text-hub-purple hover:underline">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('admin.audience-tabs.cards.destroy', [$tab, $card]) }}" onsubmit="return confirm('{{ __('Delete this card? This cannot be undone.') }}')">
                                        @csrf @method('DELETE')
                                        <x-admin.button type="submit" variant="danger">{{ __('Delete') }}</x-admin.button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="text-sm text-hub-dark/50 mt-6">{{ __('Save the tab first, then add its cards.') }}</p>
            @endif
        </div>

        {{-- The preview. Sticky, so it stays beside the fields while a long card list scrolls. --}}
        <aside class="xl:sticky xl:top-24">
            <p class="adm-label">{{ __('Preview') }}</p>

            <div class="adm-preview">
                <div class="adm-preview-switch">
                    <span class="adm-preview-tab is-active" x-text="label || '{{ __('Tab label') }}'"></span>
                    <span class="adm-preview-tab">{{ __('Another tab') }}</span>
                </div>

                <p class="adm-preview-lede" x-text="lede"></p>

                @foreach($tab->cards->take(2) as $card)
                    <div class="adm-preview-card">
                        <div class="adm-preview-card-body">
                            <p class="adm-preview-card-title">{{ $card->title_en }}</p>
                            <p class="adm-preview-card-text">{{ Str::limit((string) $card->body_en, 70) }}</p>
                            <span class="adm-preview-pill" x-show="cta" x-text="cta"></span>
                        </div>
                        <div class="adm-preview-card-media">
                            @if($card->imageUrl())
                                <img src="{{ $card->imageUrl() }}" alt="">
                            @endif
                        </div>
                    </div>
                @endforeach

                @if($tab->cards->count() > 2)
                    <p class="adm-preview-more">
                        {{ trans_choice('{1} and :count more card|[2,*] and :count more cards', $tab->cards->count() - 2, ['count' => $tab->cards->count() - 2]) }}
                    </p>
                @endif
            </div>

            <p class="text-xs text-hub-dark/45 mt-3">
                {{ __('A rough sketch of the section. Open the site to see it exactly.') }}
                <a href="{{ route('home') }}#audiences" target="_blank" rel="noopener" class="text-hub-purple font-semibold hover:underline">{{ __('View on site') }}</a>
            </p>
        </aside>
    </div>
@endsection
