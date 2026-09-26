{{-- resources/views/admin/agenda-items/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$item->exists ? __('Edit Agenda Item') : __('New Agenda Item')" />

    <form method="POST" action="{{ $item->exists ? route('admin.events.agenda-items.update', [$event, $item]) : route('admin.events.agenda-items.store', $event) }}">
        @csrf
        @if($item->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-admin.field type="date" name="day_date" label="{{ __('Day') }}" :value="old('day_date', optional($item->day_date)->toDateString())" />
            <x-admin.field type="time" name="start_time" label="{{ __('Start Time') }}" :value="old('start_time', optional($item->start_time)->format('H:i'))" />
            <x-admin.field type="time" name="end_time" label="{{ __('End Time') }}" :value="old('end_time', optional($item->end_time)->format('H:i'))" />
        </div>

        <x-admin.bilingual-field name="title" label="{{ __('Title') }}" :value-ar="old('title_ar', $item->title_ar)" :value-en="old('title_en', $item->title_en)" />

        <x-admin.field type="select" name="type" label="{{ __('Type') }}">
            @foreach($types as $type)
                <option value="{{ $type->value }}" @selected(old('type', $item->type?->value) === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </x-admin.field>

        <x-admin.field type="select" name="speaker_id" label="{{ __('Speaker') }}">
            <option value="">{{ __('None') }}</option>
            @foreach($speakers as $speaker)
                <option value="{{ $speaker->id }}" @selected(old('speaker_id', $item->speaker_id) === $speaker->id)>{{ $speaker->name_en }}</option>
            @endforeach
        </x-admin.field>

        <x-admin.field type="select" name="workshop_id" label="{{ __('Workshop') }}">
            <option value="">{{ __('None') }}</option>
            @foreach($workshops as $workshop)
                <option value="{{ $workshop->id }}" @selected(old('workshop_id', $item->workshop_id) === $workshop->id)>{{ $workshop->name_en }}</option>
            @endforeach
        </x-admin.field>

        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $item->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
