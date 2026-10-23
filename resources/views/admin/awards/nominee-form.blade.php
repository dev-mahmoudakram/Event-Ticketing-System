{{-- resources/views/admin/awards/nominee-form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$nominee->exists ? __('Edit Nominee') : __('New Nominee')" />

    <form method="POST" action="{{ $nominee->exists ? route('admin.events.awards.nominees.update', [$event, $award, $nominee]) : route('admin.events.awards.nominees.store', [$event, $award]) }}" enctype="multipart/form-data">
        @csrf
        @if($nominee->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $nominee->name_ar)" :value-en="old('name_en', $nominee->name_en)" />
        <x-admin.bilingual-field type="textarea" name="bio" label="{{ __('Bio') }}" :value-ar="old('bio_ar', $nominee->bio_ar)" :value-en="old('bio_en', $nominee->bio_en)" />
        <x-admin.media-upload
            name="image"
            :label="__('Photo')"
            :current="$nominee->imageUrl()"
            :hint="__('Square headshot works best.')"
        />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
