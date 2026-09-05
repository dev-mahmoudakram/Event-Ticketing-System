{{-- resources/views/workshops/book.blade.php --}}
@extends('layouts.app')

@php $eventName = app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en; @endphp

@section('title', $eventName.' — '.__('Book your workshops'))

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
        <a href="{{ route('workshops.index', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
            <span aria-hidden="true">&larr;</span> {{ __('All workshops') }}
        </a>

        <div class="max-w-md">
            <div class="ccs-eyebrow text-ccs-gold">{{ __('Workshops') }}</div>
            <h1 class="font-display text-3xl md:text-4xl font-extrabold mb-4">{{ __('Book your workshops.') }}</h1>
            <p class="text-gray-300 leading-relaxed mb-8">
                {{ __('Your ticket email carries a reference number and a booking key. Enter both and pick the workshops you want — there is no account to sign into.') }}
            </p>

            <form method="POST" action="{{ route('workshops.authenticate', $event) }}" class="flex flex-col gap-4">
                @csrf

                <div>
                    <label for="reference" class="block text-sm text-gray-300 mb-1">{{ __('Reference number') }}</label>
                    <input id="reference" name="reference" type="text" value="{{ old('reference') }}" dir="ltr" autocomplete="off" required
                        class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2" placeholder="CCS2026-000042">
                </div>

                <div>
                    <label for="booking_key" class="block text-sm text-gray-300 mb-1">{{ __('Workshop Booking Key') }}</label>
                    <input id="booking_key" name="booking_key" type="text" value="{{ old('booking_key') }}" dir="ltr" autocomplete="off" required
                        class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2 uppercase tracking-wider" placeholder="A1B2-C3D4-E5F6">
                </div>

                @error('reference')
                    <p class="text-sm text-red-300">{{ $message }}</p>
                @enderror

                <button type="submit" class="px-6 py-3 rounded-lg bg-ccs-teal hover:bg-ccs-teal-light hover:text-ccs-black text-white font-bold transition-colors w-fit">
                    {{ __('Open my workshops') }}
                </button>
            </form>
        </div>
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
