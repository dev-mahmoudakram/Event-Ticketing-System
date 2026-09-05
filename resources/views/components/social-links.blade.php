{{-- resources/views/components/social-links.blade.php --}}
@props([
    'links' => [],
    'label' => null,
    // Both footers are dark; a lighter surface can pass its own colours.
    'linkClass' => 'border-white/25 text-white/70 hover:text-white hover:border-white/60',
])

@php $links = \App\Support\SocialPlatforms::filled($links); @endphp

@if($links !== [])
    <ul {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3']) }} @if($label) aria-label="{{ $label }}" @endif>
        @foreach($links as $platform => $url)
            <li>
                <a
                    href="{{ $url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="grid place-items-center w-10 h-10 rounded-full border transition-colors {{ $linkClass }}"
                    aria-label="{{ \App\Support\SocialPlatforms::label($platform) }}"
                >
                    <x-social-icon :platform="$platform" />
                </a>
            </li>
        @endforeach
    </ul>
@endif
