@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Request Your Ticket'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])
    <section class="ccs-section scroll-mt-24 pt-32 pb-24" x-data="{ influencerCategoryId: @js(old('influencer_category_id', '')) }">
        <div class="max-w-2xl mx-auto">
            <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-8">{{ __('Request Your Ticket') }}</h1>

            <form method="POST" action="{{ route('invitations.store', [$event, $token]) }}" class="ccs-form bg-white/[0.03] border border-white/10 rounded-2xl p-6 md:p-8">
                @csrf

                <div>
                    <label for="name" class="ccs-form-label">{{ __('Name') }} <span class="ccs-form-required" aria-hidden="true">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('e.g. Ahmed Hassan') }}" autocomplete="name" required class="ccs-form-input" @error('name') aria-invalid="true" @enderror>
                    @error('name') <p class="ccs-form-error">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="email" class="ccs-form-label">{{ __('Email') }} <span class="ccs-form-required" aria-hidden="true">*</span></label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('name@example.com') }}" autocomplete="email" dir="ltr" required class="ccs-form-input" @error('email') aria-invalid="true" @enderror>
                        @error('email') <p class="ccs-form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="ccs-form-label">{{ __('Phone') }} <span class="ccs-form-required" aria-hidden="true">*</span></label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" required class="ccs-form-input" @error('phone') aria-invalid="true" @enderror>
                        @error('phone') <p class="ccs-form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="influencer_category_id" class="ccs-form-label">
                        {{ __('Influencer Category') }}
                        @if($event->require_influencer_category)
                            <span class="ccs-form-required" aria-hidden="true">*</span>
                        @else
                            <span class="ccs-form-optional">({{ __('optional') }})</span>
                        @endif
                    </label>
                    <x-nice-select
                        id="influencer_category_id"
                        name="influencer_category_id"
                        model="influencerCategoryId"
                        :placeholder="__('Choose a category')"
                        :options="$event->influencerCategories->map(fn ($category) => [
                            'value' => (string) $category->id,
                            'label' => app()->getLocale() === 'ar' ? $category->name_ar : $category->name_en,
                        ])->push(['value' => 'other', 'label' => __('Other')])->values()->all()"
                    />
                    @error('influencer_category_id') <p class="ccs-form-error">{{ $message }}</p> @enderror

                    <div x-show="influencerCategoryId === 'other'" x-cloak class="mt-3">
                        <label for="influencer_category_other" class="sr-only">{{ __('Tell us your category') }}</label>
                        <input id="influencer_category_other" type="text" name="influencer_category_other" value="{{ old('influencer_category_other') }}" placeholder="{{ __('Tell us your category') }}" class="ccs-form-input">
                        @error('influencer_category_other') <p class="ccs-form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                @foreach(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'] as $platform => $label)
                    <x-social-field :platform="$platform" :name="$platform.'_url'" :followers-name="$platform.'_followers'" :label="__($label.' profile URL')" />
                @endforeach

                <button type="submit" class="ccs-form-submit">{{ __('Submit Request') }}</button>
            </form>
        </div>
    </section>
    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
