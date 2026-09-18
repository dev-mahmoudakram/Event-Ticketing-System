{{-- resources/views/admin/testimonials/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$testimonial->exists ? __('Edit Testimonial') : __('New Testimonial')" />

    <form method="POST" action="{{ $testimonial->exists ? route('admin.events.testimonials.update', [$event, $testimonial]) : route('admin.events.testimonials.store', $event) }}" enctype="multipart/form-data">
        @csrf
        @if($testimonial->exists) @method('PUT') @endif

        <x-admin.media-upload
            name="photo"
            :label="__('Photo')"
            :current="$testimonial->photoUrl()"
            :hint="__('A square photo of the person giving the testimonial.')"
        />
        <x-admin.bilingual-field type="richtext" name="quote" label="{{ __('Quote') }}" :value-ar="old('quote_ar', $testimonial->quote_ar)" :value-en="old('quote_en', $testimonial->quote_en)" />
        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $testimonial->name_ar)" :value-en="old('name_en', $testimonial->name_en)" />
        <x-admin.bilingual-field name="title" label="{{ __('Title') }}" :value-ar="old('title_ar', $testimonial->title_ar)" :value-en="old('title_en', $testimonial->title_en)" />
        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $testimonial->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
