{{-- resources/views/components/social-icon.blade.php --}}
@props(['platform'])

{{-- Bootstrap Icons, so the marks are the real ones and stay maintained upstream. --}}
@php $icon = \App\Support\SocialPlatforms::icon($platform); @endphp

@if($icon)
    @svg($icon, $attributes->merge(['class' => 'w-5 h-5'])->getAttributes() + ['aria-hidden' => 'true'])
@endif
