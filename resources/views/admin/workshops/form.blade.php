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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-admin.field type="date" name="day_date" label="{{ __('Day') }}" :value="old('day_date', optional($workshop->day_date)->toDateString())" required />
            <x-admin.field type="time" name="start_time" label="{{ __('Start Time') }}" :value="old('start_time', optional($workshop->start_time)->format('H:i'))" required />
            <x-admin.field type="time" name="end_time" label="{{ __('End Time') }}" :value="old('end_time', optional($workshop->end_time)->format('H:i'))" required />
        </div>

        <x-admin.field type="select" name="location_id" label="{{ __('Location') }}">
            <option value="">{{ __('No location') }}</option>
            @foreach($locations as $location)
                <option value="{{ $location->id }}" @selected((string) old('location_id', $workshop->location_id) === (string) $location->id)>{{ $location->name() }}</option>
            @endforeach
        </x-admin.field>

        <x-admin.speaker-picker :speakers="$speakers" :selected="$errors->any() ? old('speaker_ids', []) : ($workshop->exists ? $workshop->speakers->pluck('id')->all() : [])" />

        <x-admin.field type="number" name="capacity" label="{{ __('Capacity') }}" :value="old('capacity', $workshop->capacity ?? 0)" />
        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $workshop->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
