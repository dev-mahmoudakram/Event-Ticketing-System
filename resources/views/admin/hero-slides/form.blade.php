{{-- resources/views/admin/hero-slides/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$slide->exists ? __('Edit Slide') : __('New Slide')">
        <x-admin.button href="{{ route('admin.hero-slides.index') }}" variant="secondary">{{ __('Back') }}</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $slide->exists ? route('admin.hero-slides.update', $slide) : route('admin.hero-slides.store') }}" enctype="multipart/form-data">
        @csrf
        @if($slide->exists) @method('PUT') @endif

        <x-admin.media-upload
            name="image"
            :label="__('Image')"
            :current="$slide->imageUrl()"
            :required="!$slide->exists"
            :hint="__('Wide landscape works best, since it fills the whole screen. Up to :limit.', ['limit' => $uploadLimit])"
        />

        <p class="text-sm text-gray-400 mb-4 max-w-2xl">{{ __('Leave the headline and text blank to reuse the hero copy from Creators Hub Content.') }}</p>

        <x-admin.bilingual-field name="headline" :label="__('Headline')" :value-ar="old('headline_ar', $slide->headline_ar)" :value-en="old('headline_en', $slide->headline_en)" />
        <x-admin.bilingual-field type="textarea" name="body" :label="__('Supporting text')" :value-ar="old('body_ar', $slide->body_ar)" :value-en="old('body_en', $slide->body_en)" />
        <x-admin.field type="number" name="sort_order" :label="__('Sort Order')" :value="old('sort_order', $slide->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
