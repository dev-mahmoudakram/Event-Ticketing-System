@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Invitation'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])
    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
        @if($invalid)
            <div class="max-w-xl rounded-2xl border border-white/10 bg-ccs-black px-6 py-8 text-center mx-auto">
                <h1 class="font-display text-2xl font-extrabold mb-3">{{ __('This invitation is no longer valid.') }}</h1>
                <p class="text-gray-400">{{ __('Please contact the person who invited you if you need help.') }}</p>
            </div>
        @else
            <div class="max-w-md mx-auto">
                <h1 class="font-display text-2xl font-extrabold mb-2">{{ __("You've been invited") }}</h1>
                <p class="text-gray-400 mb-8">{{ __('Enter the one-time code you were given to continue.') }}</p>
                <form method="POST" action="{{ route('invitations.verify.attempt', [$event, $token]) }}" class="flex flex-col gap-4">
                    @csrf
                    <div>
                        <label for="otp" class="block text-sm text-gray-300 mb-1">{{ __('Invitation code') }}</label>
                        <input id="otp" type="text" name="otp" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2 tracking-widest text-center text-lg">
                        @error('otp') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="px-6 py-3 rounded bg-ccs-red hover:bg-ccs-maroon text-white font-bold">{{ __('Continue') }}</button>
                </form>
            </div>
        @endif
    </section>
    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
