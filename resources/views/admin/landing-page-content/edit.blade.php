{{-- resources/views/admin/landing-page-content/edit.blade.php --}}
@extends('layouts.admin')

@section('title', __('Landing Page Content').' — '.$event->name_en)

@section('content')
    <x-admin.page-header :title="__('Landing Page Content').' — '.$event->name_en">
        <x-admin.button href="{{ route('landing.show', $event) }}" variant="secondary" target="_blank" rel="noopener">{{ __('View page') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('status'))
        <p class="mb-6 text-sm font-bold text-hub-purple">{{ session('status') }}</p>
    @endif

    <div class="grid grid-cols-1 2xl:grid-cols-[minmax(0,1fr)_28rem] gap-8 items-start">
    <form
        method="POST"
        action="{{ route('admin.events.content.update', $event) }}"
        enctype="multipart/form-data"
        class="adm-card p-6"
        x-data="unsavedGuard(@js(__('You have unsaved changes. Leave without saving?')))"
        x-bind="form"
    >
        @csrf
        @method('PUT')

        <h2 class="font-display text-lg font-bold mt-6 mb-2">{{ __('Hero Headline') }}</h2>
        <x-admin.bilingual-field name="hero_headline" :value-ar="old('hero_headline_ar', $values['hero_headline_ar'])" :value-en="old('hero_headline_en', $values['hero_headline_en'])" />

        <h2 class="font-display text-lg font-bold mt-6 mb-2">{{ __('About Body') }}</h2>
        <x-admin.bilingual-field type="textarea" name="about_body" :value-ar="old('about_body_ar', $values['about_body_ar'])" :value-en="old('about_body_en', $values['about_body_en'])" />

        <x-admin.media-upload
            name="about_image"
            :label="__('About Image')"
            :current="$aboutImage"
            :hint="__('Shown beside the About text on the event page. Up to :limit.', ['limit' => $uploadLimit])"
        />
        @if($aboutImage)
            <label class="flex items-center gap-2 text-sm text-hub-dark/75 -mt-3 mb-5">
                <input type="checkbox" name="remove_about_image" value="1" class="rounded border-hub-purple/20 bg-white">
                {{ __('Remove the current image') }}
            </label>
        @endif

        <h2 class="font-display text-lg font-bold mt-6 mb-2">{{ __('Location Intro') }}</h2>
        <x-admin.bilingual-field type="textarea" name="location_intro" :value-ar="old('location_intro_ar', $values['location_intro_ar'])" :value-en="old('location_intro_en', $values['location_intro_en'])" />

        <h2 class="font-display text-lg font-bold mt-6 mb-2">{{ __('Awards Teaser Blurb') }}</h2>
        <x-admin.bilingual-field type="textarea" name="awards_teaser_blurb" :value-ar="old('awards_teaser_blurb_ar', $values['awards_teaser_blurb_ar'])" :value-en="old('awards_teaser_blurb_en', $values['awards_teaser_blurb_en'])" />

        <h2 class="font-display text-lg font-bold mt-6 mb-2">{{ __('Stats') }}</h2>
        <x-admin.bilingual-field name="stats_attendees_count" label="{{ __('Attendees') }}" :value-ar="old('stats_attendees_count_ar', $values['stats_attendees_count_ar'])" :value-en="old('stats_attendees_count_en', $values['stats_attendees_count_en'])" />
        <x-admin.bilingual-field name="stats_countries_count" label="{{ __('Countries') }}" :value-ar="old('stats_countries_count_ar', $values['stats_countries_count_ar'])" :value-en="old('stats_countries_count_en', $values['stats_countries_count_en'])" />

        <h2 class="font-display text-lg font-bold mt-6 mb-2">{{ __('Visible Sections') }}</h2>
        <p class="text-sm text-hub-dark/60 mb-3">{{ __('Uncheck a section to hide it from the public landing page. Hero always shows.') }}</p>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-4">
            @foreach($sections as $section)
                <label class="flex items-center gap-2 text-sm text-hub-dark/75">
                    <input type="checkbox" name="visible_sections[]" value="{{ $section }}" class="rounded border-hub-purple/20 bg-white" @checked(in_array($section, old('visible_sections', $visibleSections), true))>
                    {{ __(ucwords(str_replace('-', ' ', $section))) }}
                </label>
            @endforeach
        </div>

        <x-admin.button type="submit" class="mt-4">{{ __('Save') }}</x-admin.button>
    </form>

    <aside class="2xl:sticky 2xl:top-24">
        <x-admin.live-preview
            :url="route('landing.show', $event)"
            :label="__('Open page')"
        />
    </aside>
    </div>
@endsection
