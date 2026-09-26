{{-- resources/views/components/social-field.blade.php --}}
{{-- A social profile link with its follower count beside it, used by the ticket request and the
     invitation forms. Error lines carry id="error-<name>" so the ticket form's script can fill
     them after an AJAX submit, and are filled server-side on a normal round trip. --}}
@props([
    'platform' => null,
    'name',
    'followersName',
    'label',
    'required' => false,
    'placeholder' => null,
])

@php
    $placeholder ??= \App\Support\SocialPlatforms::all()[$platform]['placeholder'] ?? 'https://';
@endphp

<div class="ccs-form-field {{ $errors->has($name) || $errors->has($followersName) ? 'has-error' : '' }}">
    <label for="{{ $name }}" class="ccs-form-label">
        {{ $label }}
        @if($required)<span class="ccs-form-required" aria-hidden="true">*</span>@endif
    </label>

    <div class="ccs-form-social">
        <div>
            <div class="ccs-form-input ccs-form-affix">
                @if($platform)
                    <x-social-icon :platform="$platform" class="ccs-form-affix-icon" />
                @endif
                <input id="{{ $name }}" type="url" name="{{ $name }}" value="{{ old($name) }}" placeholder="{{ $placeholder }}" dir="ltr" inputmode="url" @if($required) required aria-required="true" @endif>
            </div>
            <p id="error-{{ $name }}" class="ccs-form-error {{ $errors->has($name) ? '' : 'hidden' }}">{{ $errors->first($name) }}</p>
        </div>

        <div>
            <label for="{{ $followersName }}" class="sr-only">{{ __('Follower count') }}</label>
            <div class="ccs-form-input ccs-form-affix">
                {{-- Text, not number: "30k" and "1.2m" are accepted and read on the server
                     (App\Support\FollowerCount). --}}
                <input id="{{ $followersName }}" type="text" name="{{ $followersName }}" value="{{ old($followersName) }}" placeholder="{{ __('e.g. 30k') }}" dir="ltr" autocomplete="off" spellcheck="false"
                    data-follower-count data-follower-hint="{{ __(':count followers') }}" aria-describedby="{{ $followersName }}-hint">
                <span class="ccs-form-affix-text">{{ __('followers') }}</span>
            </div>
            <p id="{{ $followersName }}-hint" class="ccs-form-hint" hidden></p>
            <p id="error-{{ $followersName }}" class="ccs-form-error {{ $errors->has($followersName) ? '' : 'hidden' }}">{{ $errors->first($followersName) }}</p>
        </div>
    </div>
</div>
