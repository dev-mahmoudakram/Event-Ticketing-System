{{-- resources/views/admin/speakers/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$speaker->exists ? __('Edit Speaker') : __('New Speaker')" />

    <form method="POST" action="{{ $speaker->exists ? route('admin.events.speakers.update', [$event, $speaker]) : route('admin.events.speakers.store', $event) }}" enctype="multipart/form-data">
        @csrf
        @if($speaker->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $speaker->name_ar)" :value-en="old('name_en', $speaker->name_en)" />
        <x-admin.bilingual-field name="title" label="{{ __('Title') }}" :value-ar="old('title_ar', $speaker->title_ar)" :value-en="old('title_en', $speaker->title_en)" />
        <x-admin.bilingual-field type="richtext" name="bio" label="{{ __('Bio') }}" :value-ar="old('bio_ar', $speaker->bio_ar)" :value-en="old('bio_en', $speaker->bio_en)" />
        <x-admin.media-upload
            name="photo"
            :label="__('Photo')"
            :current="$speaker->photoUrl()"
            :hint="__('Square headshot works best.')"
        />

        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $speaker->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
