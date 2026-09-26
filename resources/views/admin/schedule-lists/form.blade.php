@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$item->exists ? __('Edit').' — '.$title : $newLabel" />

    <form method="POST" action="{{ $item->exists ? route($routePrefix.'.update', [$event, $item]) : route($routePrefix.'.store', $event) }}" class="adm-card p-6 max-w-xl">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $item->name_ar)" :value-en="old('name_en', $item->name_en)" />

        @if($showBreakToggle)
            <x-admin.field type="checkbox" name="is_break" label="{{ __('Show as a break (a slim line on the agenda)') }}" :checked="old('is_break', $item->is_break)" />
        @endif

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
