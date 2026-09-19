{{-- resources/views/admin/influencer-categories/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$influencerCategory->exists ? __('Edit Influencer Category') : __('New Influencer Category')" />

    <form method="POST" action="{{ $influencerCategory->exists ? route('admin.events.influencer-categories.update', [$event, $influencerCategory]) : route('admin.events.influencer-categories.store', $event) }}">
        @csrf
        @if($influencerCategory->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $influencerCategory->name_ar)" :value-en="old('name_en', $influencerCategory->name_en)" />
        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $influencerCategory->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
