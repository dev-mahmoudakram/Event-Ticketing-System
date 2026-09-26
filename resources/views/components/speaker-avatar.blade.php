{{-- A speaker's round photo, or their initial on a coral disc when there is no photo. --}}
@props(['speaker', 'size' => 'w-10 h-10'])

@php $name = app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en; @endphp

@if($speaker->photoUrl())
    <img src="{{ $speaker->photoUrl() }}" alt="" loading="lazy" {{ $attributes->merge(['class' => $size.' rounded-full object-cover shrink-0 border border-white/15']) }}>
@else
    <span {{ $attributes->merge(['class' => $size.' rounded-full shrink-0 bg-ccs-coral/20 text-ccs-coral font-bold flex items-center justify-center']) }} aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
@endif
