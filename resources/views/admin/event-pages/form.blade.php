{{-- resources/views/admin/event-pages/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$page->exists ? __('Edit Page') : __('New Page')" />

    <form method="POST" action="{{ $page->exists ? route('admin.events.pages.update', [$event, $page]) : route('admin.events.pages.store', $event) }}">
        @csrf
        @if($page->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="title" label="{{ __('Title') }}" :value-ar="old('title_ar', $page->title_ar)" :value-en="old('title_en', $page->title_en)" />

        @if($page->isRequired())
            <p class="mb-5 text-sm text-hub-dark/60">{{ __('Address') }}: <span dir="ltr">/pages/{{ $page->slug }}</span> — {{ __('required pages keep their address and stay published.') }}</p>
        @else
            <x-admin.field name="slug" label="{{ __('Address') }}" :value="old('slug', $page->slug)" required />
        @endif

        @if($page->needsDetails())
            <div class="mb-5 rounded border border-[#f5b8b2] bg-[#fdecea] px-4 py-3 text-sm text-[#b42318]">{{ __('This is a starter draft. Replace every [placeholder] and have it reviewed before going live.') }}</div>
        @endif

        <x-admin.bilingual-field type="richtext" name="body" label="{{ __('Content') }}" :value-ar="old('body_ar', $page->body_ar)" :value-en="old('body_en', $page->body_en)" />

        <x-admin.field type="checkbox" name="show_in_footer" label="{{ __('Show in the event footer') }}" :checked="old('show_in_footer', $page->show_in_footer)" />
        @unless($page->isRequired())
            <x-admin.field type="checkbox" name="is_published" label="{{ __('Published') }}" :checked="old('is_published', $page->is_published)" />
        @endunless

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
