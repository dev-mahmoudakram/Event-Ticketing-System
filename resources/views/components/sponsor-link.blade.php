{{-- resources/views/components/sponsor-link.blade.php --}}
@props(['url'])

<a
    href="{{ $url }}"
    target="_blank"
    rel="noopener noreferrer"
    {{ $attributes->merge(['class' => 'group flex items-center justify-center rounded-lg border border-white/10 bg-white/5 transition-colors hover:border-ccs-coral/50 focus-visible:border-ccs-coral']) }}
    data-sponsor-logo
>
    {{ $slot }}
</a>
