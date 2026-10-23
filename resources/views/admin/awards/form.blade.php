{{-- resources/views/admin/awards/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$award->exists ? __('Edit Award') : __('New Award')" />

    <form method="POST" action="{{ $award->exists ? route('admin.events.awards.update', [$event, $award]) : route('admin.events.awards.store', $event) }}">
        @csrf
        @if($award->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $award->name_ar)" :value-en="old('name_en', $award->name_en)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
