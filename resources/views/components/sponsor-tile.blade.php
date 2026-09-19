{{-- resources/views/components/sponsor-tile.blade.php --}}
@props(['url' => null])

<div {{ $attributes->merge(['class' => 'group flex items-center justify-center rounded-lg border border-white/10 bg-white/5 transition-colors hover:border-ccs-coral/50']) }} data-sponsor-logo>
    {{ $slot }}
</div>
