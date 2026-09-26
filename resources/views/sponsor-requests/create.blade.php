{{-- resources/views/sponsor-requests/create.blade.php --}}
@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Become a Sponsor'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
        <a href="{{ route('landing.show', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
            <span aria-hidden="true">&larr;</span> {{ __('Back to :event', ['event' => app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en]) }}
        </a>

        <div class="ccs-eyebrow text-ccs-gold" data-reveal>{{ __('Partners') }}</div>
        <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-6 max-w-2xl" data-reveal>{{ __('Become a Sponsor') }}</h1>

        @if(session('sponsor_request_success'))
            <div class="max-w-xl rounded-2xl border border-ccs-gold/40 bg-ccs-gold/10 px-6 py-5" data-reveal>
                <p class="font-display font-bold text-lg text-ccs-gold mb-1">{{ __('Request received!') }}</p>
                <p class="text-gray-300">{{ __("Thanks — we'll review your request and be in touch soon.") }}</p>
            </div>
        @else
            <p class="text-gray-400 max-w-xl mb-10 leading-relaxed" data-reveal>{{ __("Tell us about your company and how you'd like to support this event — our team will review your request and follow up.") }}</p>

            <form method="POST" action="{{ route('sponsor-requests.store', $event) }}" enctype="multipart/form-data" class="max-w-xl flex flex-col gap-4" data-reveal data-reveal-delay="1">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="sponsor-name-en" class="block text-sm text-gray-300 mb-1">{{ __('Company Name (English)') }}</label>
                        <input id="sponsor-name-en" type="text" name="name_en" value="{{ old('name_en') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('name_en') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sponsor-name-ar" class="block text-sm text-gray-300 mb-1">{{ __('Company Name (Arabic)') }}</label>
                        <input id="sponsor-name-ar" type="text" name="name_ar" dir="rtl" value="{{ old('name_ar') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('name_ar') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="sponsor-contact-name" class="block text-sm text-gray-300 mb-1">{{ __('Contact Name') }}</label>
                    <input id="sponsor-contact-name" type="text" name="contact_name" value="{{ old('contact_name') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error('contact_name') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

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

                <x-file-dropzone name="logo" accept="image/*" :label="__('Logo')" />
                @error('logo') <p class="text-red-400 text-sm -mt-2">{{ $message }}</p> @enderror

                <div>
                    <label for="sponsor-website" class="block text-sm text-gray-300 mb-1">{{ __('Website URL') }} <span class="text-gray-500">({{ __('optional') }})</span></label>
                    <input id="sponsor-website" type="url" name="website_url" value="{{ old('website_url') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error('website_url') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="sponsor-instagram" class="block text-sm text-gray-300 mb-1">{{ __('Instagram URL') }}</label>
                        <input id="sponsor-instagram" type="url" name="instagram_url" value="{{ old('instagram_url') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('instagram_url') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sponsor-facebook" class="block text-sm text-gray-300 mb-1">{{ __('Facebook URL') }}</label>
                        <input id="sponsor-facebook" type="url" name="facebook_url" value="{{ old('facebook_url') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error('facebook_url') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="sponsor-message" class="block text-sm text-gray-300 mb-1">{{ __('Message') }}</label>
                    <textarea id="sponsor-message" name="message" rows="3" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">{{ old('message') }}</textarea>
                    @error('message') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="self-start px-6 py-3 rounded bg-ccs-red hover:bg-ccs-maroon text-white font-bold transition-opacity">{{ __('Submit Request') }}</button>
            </form>
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
