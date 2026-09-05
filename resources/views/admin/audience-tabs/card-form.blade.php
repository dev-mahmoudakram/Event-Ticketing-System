{{-- resources/views/admin/audience-tabs/card-form.blade.php --}}
@extends('layouts.admin')

@php $editing = $card->exists; @endphp

@section('title', $editing ? $card->title_en : __('New Card'))

@section('content')
    <x-admin.page-header :title="($editing ? __('Edit card') : __('New Card')).' — '.$tab->label_en">
        <x-admin.button href="{{ route('admin.audience-tabs.edit', $tab) }}" variant="secondary">{{ __('Back') }}</x-admin.button>
    </x-admin.page-header>

    <div
        class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_24rem] gap-8 items-start"
        x-data="{
            title: @js(old('title_en', $card->title_en)),
            body: @js(old('body_en', $card->body_en)),
            image: @js($card->imageUrl()),
            init() {
                for (const [field, name] of [['title', 'title_en'], ['body', 'body_en']]) {
                    const input = this.$el.querySelector('[name=&quot;' + name + '&quot;]');
                    input?.addEventListener('input', () => { this[field] = input.value; });
                }

                // Show the picked file straight away rather than after saving.
                const file = this.$el.querySelector('input[type=&quot;file&quot;]');
                file?.addEventListener('change', () => {
                    const picked = file.files?.[0];
                    if (picked) {
                        this.image = URL.createObjectURL(picked);
                    }
                });
            },
        }"
    >
        <form
            method="POST"
            action="{{ $editing ? route('admin.audience-tabs.cards.update', [$tab, $card]) : route('admin.audience-tabs.cards.store', $tab) }}"
            enctype="multipart/form-data"
            class="adm-card p-6"
        >
            @csrf
            @if($editing) @method('PUT') @endif

            <x-admin.bilingual-field
                name="title"
                :label="__('Title')"
                :value-ar="old('title_ar', $card->title_ar)"
                :value-en="old('title_en', $card->title_en)"
            />

            <x-admin.bilingual-field
                type="textarea"
                name="body"
                :label="__('Text')"
                :value-ar="old('body_ar', $card->body_ar)"
                :value-en="old('body_en', $card->body_en)"
            />

            <x-admin.media-upload
                name="image"
                :label="__('Image')"
                :current="$card->imageUrl()"
                :hint="__('Optional. A PNG or JPG up to :limit. The card shows a plain panel without one.', ['limit' => $uploadLimit])"
            />

            <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
        </form>

        {{-- The card as the site draws it, at a smaller size. --}}
        <aside class="xl:sticky xl:top-24">
            <p class="adm-label">{{ __('Preview') }}</p>

            <div class="adm-preview">
                <div class="adm-preview-card">
                    <div class="adm-preview-card-body">
                        <p class="adm-preview-card-title" x-text="title || '{{ __('Card title') }}'"></p>
                        <p class="adm-preview-card-text" x-text="body"></p>
                        @if(trim((string) $tab->cta_en) !== '')
                            <span class="adm-preview-pill">{{ $tab->cta_en }}</span>
                        @endif
                    </div>
                    <div class="adm-preview-card-media">
                        <template x-if="image">
                            <img :src="image" alt="">
                        </template>
                    </div>
                </div>
            </div>

            <p class="text-xs text-hub-dark/45 mt-3">
                {{ __('A rough sketch of the card. Open the site to see it exactly.') }}
                <a href="{{ route('home') }}#audiences" target="_blank" rel="noopener" class="text-hub-purple font-semibold hover:underline">{{ __('View on site') }}</a>
            </p>
        </aside>
    </div>
@endsection
