{{-- resources/views/admin/sponsors/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$sponsor->exists ? __('Edit Sponsor') : __('New Sponsor')" />

    <form method="POST" action="{{ $sponsor->exists ? route('admin.events.sponsors.update', [$event, $sponsor]) : route('admin.events.sponsors.store', $event) }}" enctype="multipart/form-data">
        @csrf
        @if($sponsor->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $sponsor->name_ar)" :value-en="old('name_en', $sponsor->name_en)" />

        <x-admin.field type="select" name="sponsor_tier_id" label="{{ __('Tier') }}">
            <option value="" @selected(old('sponsor_tier_id', $sponsor->sponsor_tier_id) === null)>{{ __('No tier') }}</option>
            @foreach($sponsorTiers as $sponsorTier)
                <option value="{{ $sponsorTier->id }}" @selected((int) old('sponsor_tier_id', $sponsor->sponsor_tier_id) === $sponsorTier->id)>{{ $sponsorTier->name_en }}</option>
            @endforeach
        </x-admin.field>

        <x-admin.media-upload
            name="logo"
            :label="__('Logo')"
            :current="$sponsor->logoUrl()"
            :hint="__('Transparent PNG or SVG on a light background reads best.')"
        />

        <x-admin.field name="website_url" label="{{ __('Website URL') }}" :value="old('website_url', $sponsor->website_url)" />
        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $sponsor->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
