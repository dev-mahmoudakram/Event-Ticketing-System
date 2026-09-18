{{-- resources/views/admin/sponsor-tiers/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$sponsorTier->exists ? __('Edit Sponsor Tier') : __('New Sponsor Tier')" />

    <form method="POST" action="{{ $sponsorTier->exists ? route('admin.events.sponsor-tiers.update', [$event, $sponsorTier]) : route('admin.events.sponsor-tiers.store', $event) }}">
        @csrf
        @if($sponsorTier->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $sponsorTier->name_ar)" :value-en="old('name_en', $sponsorTier->name_en)" />
        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $sponsorTier->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
