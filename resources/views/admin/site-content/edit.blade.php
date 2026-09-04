{{-- resources/views/admin/site-content/edit.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Creators Hub Content')" />

    <p class="text-sm text-gray-400 mb-6 max-w-2xl">{{ __('Copy for the Creators Hub landing page at the root of the site. Leave a section blank and it stays hidden — nothing half-filled is shown to visitors.') }}</p>

    @if(session('status'))
        <p class="mb-6 text-sm font-bold text-ccs-teal-light">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.site-content.update') }}">
        @csrf
        @method('PUT')

        @foreach($sections as $section)
            <section class="mb-10 border-b border-gray-800 pb-8">
                <h2 class="font-display text-lg font-bold mb-1">{{ $section->label() }}</h2>
                <p class="text-sm text-gray-500 mb-5 max-w-2xl">{{ $section->description() }}</p>

                @foreach($section->fields() as $fieldKey => $label)
                    @php $existing = $stored->get($section->value.'.'.$fieldKey); @endphp
                    <x-admin.bilingual-field
                        :name="'content['.$section->value.']['.$fieldKey.']'"
                        :label="$label"
                        :value-ar="old('content.'.$section->value.'.'.$fieldKey.'.ar', $existing?->value_ar)"
                        :value-en="old('content.'.$section->value.'.'.$fieldKey.'.en', $existing?->value_en)"
                    />
                @endforeach
            </section>
        @endforeach

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
