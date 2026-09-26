{{-- resources/views/speaker-requests/create.blade.php --}}
@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Become a Speaker'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
        <a href="{{ route('landing.show', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
            <span aria-hidden="true">&larr;</span> {{ __('Back to :event', ['event' => app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en]) }}
        </a>

        <div class="ccs-eyebrow text-ccs-coral" data-reveal>{{ __('Featured Speakers') }}</div>
        <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-6 max-w-2xl" data-reveal>{{ __('Become a Speaker') }}</h1>

        @if(session('speaker_request_success'))
            <div class="max-w-xl rounded-2xl border border-ccs-teal-light/40 bg-ccs-teal-light/10 px-6 py-5" data-reveal>
                <p class="font-display font-bold text-lg text-ccs-teal-light mb-1">{{ __('Request received!') }}</p>
                <p class="text-gray-300">{{ __("Thanks — we'll review your request and be in touch soon.") }}</p>
            </div>
        @else
            <p class="text-gray-400 max-w-xl mb-10 leading-relaxed" data-reveal>{{ __("Tell us about yourself and what you'd like to speak about — our team will review your request and follow up.") }}</p>

            <form method="POST" action="{{ route('speaker-requests.store', $event) }}" enctype="multipart/form-data" class="max-w-xl flex flex-col gap-4" data-reveal data-reveal-delay="1">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="speaker-name-en" class="block text-sm text-gray-300 mb-1">{{ __('Name (English)') }}</label>
                        <input id="speaker-name-en" type="text" name="name_en" value="{{ old('name_en') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('name_en') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="speaker-name-ar" class="block text-sm text-gray-300 mb-1">{{ __('Name (Arabic)') }}</label>
                        <input id="speaker-name-ar" type="text" name="name_ar" dir="rtl" value="{{ old('name_ar') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('name_ar') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="speaker-title-en" class="block text-sm text-gray-300 mb-1">{{ __('Title (English)') }}</label>
                        <input id="speaker-title-en" type="text" name="title_en" value="{{ old('title_en') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('title_en') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="speaker-title-ar" class="block text-sm text-gray-300 mb-1">{{ __('Title (Arabic)') }}</label>
                        <input id="speaker-title-ar" type="text" name="title_ar" dir="rtl" value="{{ old('title_ar') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('title_ar') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="speaker-bio-en" class="block text-sm text-gray-300 mb-1">{{ __('Bio (English)') }}</label>
                    <textarea id="speaker-bio-en" name="bio_en" rows="3" maxlength="2000" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">{{ old('bio_en') }}</textarea>
                    @error('bio_en') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="speaker-bio-ar" class="block text-sm text-gray-300 mb-1">{{ __('Bio (Arabic)') }}</label>
                    <textarea id="speaker-bio-ar" name="bio_ar" dir="rtl" rows="3" maxlength="2000" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">{{ old('bio_ar') }}</textarea>
                    @error('bio_ar') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <x-file-dropzone name="photo" accept="image/*" :label="__('Photo')" />
                @error('photo') <p class="text-red-400 text-sm -mt-2">{{ $message }}</p> @enderror

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="block text-sm text-gray-300 mb-1">{{ __('Email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('email') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm text-gray-300 mb-1">{{ __('Phone') }}</label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('phone') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="speaker-message" class="block text-sm text-gray-300 mb-1">{{ __('What would you like to speak about?') }}</label>
                    <textarea id="speaker-message" name="message" rows="3" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">{{ old('message') }}</textarea>
                    @error('message') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="self-start px-6 py-3 rounded bg-ccs-red hover:bg-ccs-maroon text-white font-bold transition-opacity">{{ __('Submit Request') }}</button>
            </form>
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
