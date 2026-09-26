@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Request Your Ticket'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])
    <section class="ccs-section scroll-mt-24 pt-32 pb-24" x-data="{ influencerCategoryId: @js(old('influencer_category_id', '')) }">
        <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-6 max-w-2xl">{{ __('Request Your Ticket') }}</h1>

        <form method="POST" action="{{ route('invitations.store', [$event, $token]) }}" class="max-w-xl flex flex-col gap-4">
            @csrf
            @foreach(['name' => __('Name'), 'email' => __('Email'), 'phone' => __('Phone')] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="block text-sm text-gray-300 mb-1">{{ $label }}</label>
                    <input id="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text') }}" name="{{ $field }}" value="{{ old($field) }}" required class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error($field) <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            @endforeach

            <div>
                <label for="influencer_category_id" class="block text-sm text-gray-300 mb-1">
                    {{ __('Influencer Category') }}
                    @unless($event->require_influencer_category)<span class="text-gray-500">({{ __('optional') }})</span>@endunless
                </label>
                <x-nice-select
                    id="influencer_category_id"
                    name="influencer_category_id"
                    model="influencerCategoryId"
                    :options="$event->influencerCategories->map(fn ($category) => [
                        'value' => (string) $category->id,
                        'label' => app()->getLocale() === 'ar' ? $category->name_ar : $category->name_en,
                    ])->push(['value' => 'other', 'label' => __('Other')])->values()->all()"
                />
                @error('influencer_category_id') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                <div x-show="influencerCategoryId === 'other'" x-cloak class="mt-3">
                    <label for="influencer_category_other" class="block text-sm text-gray-300 mb-1">{{ __('Tell us your category') }}</label>
                    <input id="influencer_category_other" type="text" name="influencer_category_other" value="{{ old('influencer_category_other') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                    @error('influencer_category_other') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            @foreach(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'] as $platform => $label)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="{{ $platform }}_url" class="block text-sm text-gray-300 mb-1">{{ __($label.' profile URL') }}</label>
                        <input id="{{ $platform }}_url" type="url" name="{{ $platform }}_url" value="{{ old($platform.'_url') }}" placeholder="https://" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error($platform.'_url') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="{{ $platform }}_followers" class="block text-sm text-gray-300 mb-1">{{ __('Followers') }}</label>
                        <input id="{{ $platform }}_followers" type="number" min="0" name="{{ $platform }}_followers" value="{{ old($platform.'_followers') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        @error($platform.'_followers') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach

            <button type="submit" class="px-6 py-3 rounded bg-ccs-red hover:bg-ccs-maroon text-white font-bold">{{ __('Submit Request') }}</button>
        </form>
    </section>
    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
