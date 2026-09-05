{{-- resources/views/components/social-icon.blade.php --}}
@props(['platform'])

{{-- Drawn at a common weight so a row of them reads as one set rather than a pile of logos. --}}
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => 'w-5 h-5']) }} aria-hidden="true">
    @switch($platform)
        @case('instagram')
            <rect x="3" y="3" width="18" height="18" rx="5.5"/>
            <circle cx="12" cy="12" r="4"/>
            <circle cx="17.3" cy="6.7" r="1" fill="currentColor" stroke="none"/>
            @break

        @case('facebook')
            <circle cx="12" cy="12" r="9.2"/>
            <path d="M14.8 7.8h-1.4a2 2 0 0 0-2 2v9.4"/>
            <path d="M9.4 12.6h4.8"/>
            @break

        @case('x')
            <path d="M4 4.2 19.2 19.8"/>
            <path d="M19.2 4.2 4 19.8"/>
            @break

        @case('linkedin')
            <rect x="3" y="3" width="18" height="18" rx="4"/>
            <path d="M7.6 10.6v6.2"/>
            <circle cx="7.6" cy="7.6" r="1" fill="currentColor" stroke="none"/>
            <path d="M11.6 16.8v-6.2M11.6 13.1a2.6 2.6 0 0 1 5.2 0v3.7"/>
            @break

        @case('youtube')
            <rect x="2.4" y="5.6" width="19.2" height="12.8" rx="4.4"/>
            <path d="M10.4 9.6 15 12l-4.6 2.4V9.6Z" fill="currentColor" stroke="none"/>
            @break

        @case('tiktok')
            <path d="M14.2 3.2v10.6a3.9 3.9 0 1 1-3.3-3.85"/>
            <path d="M14.2 3.2c.4 2.4 1.9 3.9 4.4 4.2"/>
            @break

        @case('whatsapp')
            <path d="M3.4 20.6 4.8 16.6A8.5 8.5 0 1 1 8 19.5l-4.6 1.1Z"/>
            <path d="M9.2 9c.3-.6 1.4-.5 1.7.1.3.7-.4 1.1-.2 1.7.3.9 1.1 1.7 2 2 .6.2 1-.5 1.7-.2.6.3.7 1.4.1 1.7-2.7 1.3-6.6-2.6-5.3-5.3Z" fill="currentColor" stroke="none"/>
            @break
    @endswitch
</svg>
