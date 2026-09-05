{{-- resources/views/admin/site-content/edit.blade.php --}}
@extends('layouts.admin')

@section('title', $definition['label'])

@section('content')
    <x-admin.page-header :title="$definition['label']">
        <x-admin.button href="{{ route('admin.site-content.index') }}" variant="secondary">{{ __('All sections') }}</x-admin.button>
    </x-admin.page-header>

    <div class="grid grid-cols-1 xl:grid-cols-[13rem_minmax(0,1fr)] gap-8 items-start">
        {{-- Every section, so moving between them does not mean going back to the index
             each time. --}}
        <nav class="adm-section-nav" aria-label="{{ __('Sections') }}">
            @foreach($sections as $key => $other)
                <a
                    href="{{ route('admin.site-content.edit', $key) }}"
                    class="adm-section-link {{ $key === $section ? 'is-active' : '' }}"
                    @if($key === $section) aria-current="page" @endif
                >
                    <span class="truncate">{{ $other['label'] }}</span>
                    @if(($filled[$key] ?? 0) > 0)
                        <span class="adm-section-dot" title="{{ __('Edited') }}"></span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="grid grid-cols-1 2xl:grid-cols-[minmax(0,1fr)_28rem] gap-8 items-start">
            <div>
                <p class="text-sm text-hub-dark/60 mb-6">{{ $definition['description'] }}</p>

                @if(session('status'))
                    <p class="mb-6 text-sm font-bold text-hub-purple">{{ session('status') }}</p>
                @endif

                {{-- Guards against losing a paragraph to a mistaken click on the sidebar. --}}
                <form
                    method="POST"
                    action="{{ route('admin.site-content.update', $section) }}"
                    enctype="multipart/form-data"
                    class="adm-card p-6"
                    x-data="unsavedGuard(@js(__('You have unsaved changes. Leave without saving?')))"
                    x-bind="form"
                >
                    @csrf
                    @method('PUT')

                    @foreach($definition['fields'] as $fieldKey => $field)
                        @php $record = $stored->get($fieldKey); @endphp

                        @if(($field['type'] ?? 'text') === 'image')
                            <x-admin.media-upload
                                :name="'images['.$fieldKey.']'"
                                :label="$field['label']"
                                :current="$record?->urlFor($record->value_en)"
                                accept="image/*,.svg"
                                :hint="__('Optional. Leave empty to keep the current look. A PNG, JPG or SVG up to :limit.', ['limit' => $uploadLimit])"
                            />
                        @elseif(($field['type'] ?? 'text') === 'single')
                            <x-admin.field
                                :type="$field['input'] ?? 'text'"
                                :name="'single['.$fieldKey.']'"
                                :label="$field['label']"
                                :value="old('single.'.$fieldKey, $record?->value_en)"
                                :placeholder="$field['placeholder'] ?? null"
                            />
                        @else
                            <x-admin.bilingual-field
                                :type="$field['type'] ?? 'text'"
                                :name="$fieldKey"
                                :name-ar="'fields['.$fieldKey.'][ar]'"
                                :name-en="'fields['.$fieldKey.'][en]'"
                                :label="$field['label']"
                                :value-ar="old('fields.'.$fieldKey.'.ar', $record?->value_ar)"
                                :value-en="old('fields.'.$fieldKey.'.en', $record?->value_en)"
                                :placeholder="isset($field['default']) ? __($field['default']) : null"
                            />
                        @endif
                    @endforeach

                    @if(empty($definition['fields']))
                        <p class="text-sm text-hub-dark/45 mb-5">{{ __('This section has nothing to edit here.') }}</p>
                    @endif

                    <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
                </form>
            </div>

            <aside class="2xl:sticky 2xl:top-24">
                <x-admin.live-preview
                    :url="route('home')"
                    :anchor="$definition['anchor'] ?? null"
                    :label="__('Open site')"
                />
            </aside>
        </div>
    </div>
@endsection
