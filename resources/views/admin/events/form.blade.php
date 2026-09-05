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

        <h2 class="font-display text-lg font-bold mt-8 mb-1">{{ __('Branding') }}</h2>
        <p class="text-sm text-gray-400 mb-4 max-w-2xl">{{ __('This event carries its own icon and link preview, separate from the platform. Leave a field empty to use the Creators Hub one.') }}</p>

        <x-admin.media-upload
            name="logo"
            :label="__('Logo')"
            accept="image/*,.svg"
            :current="$event->logoUrl()"
            :hint="__('Shown in the navigation bar in place of the event name. A PNG or SVG with a transparent background.')"
        />

        <x-admin.media-upload
            name="footer_logo"
            :label="__('Footer Logo')"
            accept="image/*,.svg"
            :current="$event->footerLogoUrl()"
            :hint="__('The footer sits on a dark panel, so a light version reads best. Falls back to the logo above.')"
        />

        <x-admin.media-upload
            name="favicon"
            :label="__('Favicon')"
            accept="image/png,image/svg+xml,image/x-icon"
            :current="$event->faviconUrl()"
            :hint="__('The small icon in the browser tab. A square PNG or SVG.')"
        />

        <x-admin.media-upload
            name="apple_touch_icon"
            :label="__('Apple Touch Icon')"
            :current="$event->appleTouchIconUrl()"
            :hint="__('The icon iPhones and iPads use when the page is added to the home screen. A 180 by 180 pixel PNG.')"
        />

        <x-admin.media-upload
            name="share_image"
            :label="__('Link Preview Image')"
            :current="$event->shareImageUrl()"
            :hint="__('Shown when a link to this event is shared. Works best at 1200 by 630 pixels. Falls back to the cover image.')"
        />

        <h2 class="font-display text-lg font-bold mt-8 mb-1">{{ __('Contact and social links') }}</h2>
        <p class="text-sm text-gray-400 mb-4 max-w-2xl">{{ __("Shown in this event's footer. Anything left empty is simply not shown.") }}</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-admin.field type="email" name="contact_email" :label="__('Email address')" :value="old('contact_email', $event->contact_email)" />
            <x-admin.field type="tel" name="contact_phone" :label="__('Phone number')" :value="old('contact_phone', $event->contact_phone)" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach(\App\Support\SocialPlatforms::all() as $platform => $meta)
                <x-admin.field
                    type="url"
                    :name="'social_links['.$platform.']'"
                    :label="$meta['label']"
                    :value="old('social_links.'.$platform, $event->social_links[$platform] ?? null)"
                    :placeholder="$meta['placeholder']"
                />
            @endforeach
        </div>

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
