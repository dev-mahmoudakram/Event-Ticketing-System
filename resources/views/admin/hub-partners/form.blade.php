{{-- resources/views/admin/hub-partners/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$partner->exists ? __('Edit Partner') : __('New Partner')">
        <x-admin.button href="{{ route('admin.hub-partners.index') }}" variant="secondary">{{ __('Back') }}</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $partner->exists ? route('admin.hub-partners.update', $partner) : route('admin.hub-partners.store') }}" enctype="multipart/form-data">
        @csrf
        @if($partner->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" :label="__('Name')" :value-ar="old('name_ar', $partner->name_ar)" :value-en="old('name_en', $partner->name_en)" />

        <x-admin.media-upload
            name="logo"
            :label="__('Logo')"
            :current="$partner->logoUrl()"
            :required="!$partner->exists"
            :hint="__('A white or light logo on a transparent background reads best against the dark strip. Up to :limit.', ['limit' => $uploadLimit])"
        />

        <x-admin.field name="website_url" :label="__('Website URL')" :value="old('website_url', $partner->website_url)" />
        <x-admin.field type="number" name="sort_order" :label="__('Sort Order')" :value="old('sort_order', $partner->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
