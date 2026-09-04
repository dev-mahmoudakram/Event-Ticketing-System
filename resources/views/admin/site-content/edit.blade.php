{{-- resources/views/admin/site-content/edit.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$definition['label']">
        <x-admin.button href="{{ route('admin.site-content.index') }}" variant="secondary">{{ __('Back') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-gray-400 mb-6 max-w-2xl">{{ $definition['description'] }}</p>

    @if(session('status'))
        <p class="mb-6 text-sm font-bold text-ccs-teal-light">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.site-content.update', $section) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @foreach($definition['fields'] as $fieldKey => $field)
            @php $record = $stored->get($fieldKey); @endphp

            @if(($field['type'] ?? 'text') === 'image')
                <x-admin.media-upload
                    :name="'images['.$fieldKey.']'"
                    :label="$field['label']"
                    :current="$record?->urlFor($record->value_en)"
                    :hint="__('Optional. Leave empty to keep the current look. Up to :limit.', ['limit' => $uploadLimit])"
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

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
