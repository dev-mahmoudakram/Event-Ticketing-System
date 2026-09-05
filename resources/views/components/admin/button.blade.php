@props([
    'href' => null,
    'type' => 'submit',
    'variant' => 'primary',
])

@php
$classes = match($variant) {
    'secondary' => 'adm-btn adm-btn-secondary',
    'danger' => 'adm-btn-danger',
    default => 'adm-btn adm-btn-primary',
};
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
