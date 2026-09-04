{{-- resources/views/admin/events/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$event->exists ? __('Edit Event') : __('New Event')" />

    <form method="POST" action="{{ $event->exists ? route('admin.events.update', $event) : route('admin.events.store') }}" enctype="multipart/form-data">
        @csrf
        @if($event->exists) @method('PUT') @endif

        <x-admin.field name="slug" label="{{ __('Slug') }}" :value="old('slug', $event->slug)" />
        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $event->name_ar)" :value-en="old('name_en', $event->name_en)" />
        <x-admin.bilingual-field name="tagline" label="{{ __('Tagline') }}" :value-ar="old('tagline_ar', $event->tagline_ar)" :value-en="old('tagline_en', $event->tagline_en)" />

        <x-admin.media-upload
            name="cover_image"
            :label="__('Cover Image')"
            :current="$event->coverImageUrl()"
            :hint="__('Shown wherever this event is listed, such as the event cards on the Creators Hub page. Up to :limit.', ['limit' => $uploadLimit])"
        />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-admin.field type="date" name="start_date" label="{{ __('Start Date') }}" :value="old('start_date', optional($event->start_date)->toDateString())" />
            <x-admin.field type="date" name="end_date" label="{{ __('End Date') }}" :value="old('end_date', optional($event->end_date)->toDateString())" />
        </div>

        <x-admin.bilingual-field name="venue_name" label="{{ __('Venue Name') }}" :value-ar="old('venue_name_ar', $event->venue_name_ar)" :value-en="old('venue_name_en', $event->venue_name_en)" />
        <x-admin.bilingual-field name="venue_address" label="{{ __('Venue Address') }}" :value-ar="old('venue_address_ar', $event->venue_address_ar)" :value-en="old('venue_address_en', $event->venue_address_en)" />

        <x-admin.field name="map_embed_url" label="{{ __('Map Embed URL') }}" :value="old('map_embed_url', $event->map_embed_url)" />

        <x-admin.field type="select" name="status" label="{{ __('Status') }}">
            <option value="draft" @selected(old('status', $event->status?->value) === 'draft')>{{ __('Draft') }}</option>
            <option value="published" @selected(old('status', $event->status?->value) === 'published')>{{ __('Published') }}</option>
        </x-admin.field>

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
