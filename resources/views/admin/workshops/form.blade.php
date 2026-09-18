{{-- resources/views/admin/workshops/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$workshop->exists ? __('Edit Workshop') : __('New Workshop')" />

    <form method="POST" action="{{ $workshop->exists ? route('admin.events.workshops.update', [$event, $workshop]) : route('admin.events.workshops.store', $event) }}">
        @csrf
        @if($workshop->exists) @method('PUT') @endif

        <x-admin.field name="slug" label="{{ __('Slug') }}" :value="old('slug', $workshop->slug)" />
        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $workshop->name_ar)" :value-en="old('name_en', $workshop->name_en)" />
        <x-admin.bilingual-field type="richtext" name="description" label="{{ __('Description') }}" :value-ar="old('description_ar', $workshop->description_ar)" :value-en="old('description_en', $workshop->description_en)" />

        <x-admin.field type="select" name="speaker_id" label="{{ __('Speaker') }}">
            <option value="">{{ __('None') }}</option>
            @foreach($speakers as $speaker)
                <option value="{{ $speaker->id }}" @selected(old('speaker_id', $workshop->speaker_id) === $speaker->id)>{{ $speaker->name_en }}</option>
            @endforeach
        </x-admin.field>

        <x-admin.field type="number" name="capacity" label="{{ __('Capacity') }}" :value="old('capacity', $workshop->capacity ?? 0)" />
        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $workshop->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
