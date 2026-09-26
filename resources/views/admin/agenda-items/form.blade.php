{{-- resources/views/admin/agenda-items/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$item->exists ? __('Edit Agenda Item') : __('New Agenda Item')" />

    <form method="POST" action="{{ $item->exists ? route('admin.events.agenda-items.update', [$event, $item]) : route('admin.events.agenda-items.store', $event) }}">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-admin.field type="date" name="day_date" label="{{ __('Day') }}" :value="old('day_date', optional($item->day_date)->toDateString())" required />
            <x-admin.field type="time" name="start_time" label="{{ __('Start Time') }}" :value="old('start_time', optional($item->start_time)->format('H:i'))" required />
            <x-admin.field type="time" name="end_time" label="{{ __('End Time') }}" :value="old('end_time', optional($item->end_time)->format('H:i'))" required />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-admin.field type="select" name="session_type_id" label="{{ __('Type') }}" required>
                @foreach($types as $type)
                    <option value="{{ $type->id }}" @selected((string) old('session_type_id', $item->session_type_id) === (string) $type->id)>{{ $type->name() }}</option>
                @endforeach
            </x-admin.field>
            <x-admin.field type="select" name="location_id" label="{{ __('Location') }}">
                <option value="">{{ __('No location') }}</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected((string) old('location_id', $item->location_id) === (string) $location->id)>{{ $location->name() }}</option>
                @endforeach
            </x-admin.field>
        </div>
        <p class="-mt-3 mb-5 text-xs text-hub-dark/55">
            <a class="text-hub-purple hover:underline" href="{{ route('admin.events.session-types.index', $event) }}">{{ __('Manage types') }}</a> ·
            <a class="text-hub-purple hover:underline" href="{{ route('admin.events.locations.index', $event) }}">{{ __('Manage locations') }}</a>
        </p>

        <x-admin.bilingual-field name="title" label="{{ __('Title') }}" :value-ar="old('title_ar', $item->title_ar)" :value-en="old('title_en', $item->title_en)" />
        <x-admin.bilingual-field type="richtext" name="description" label="{{ __('Description') }}" :value-ar="old('description_ar', $item->description_ar)" :value-en="old('description_en', $item->description_en)" />

        <x-admin.speaker-picker :speakers="$speakers" :selected="$errors->any() ? old('speaker_ids', []) : ($item->exists ? $item->speakers->pluck('id')->all() : [])" />

        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $item->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
