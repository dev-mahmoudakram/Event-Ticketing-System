{{-- resources/views/home/partials/hero.blade.php --}}
@php $heroImage = \App\Support\SiteText::image('hero', 'image'); @endphp

<section id="hero" class="hub-shell pt-4 pb-6">
    {{-- A full-bleed rounded well. An uploaded photograph fills it; without one it falls back
         to the brand gradient, since this brand has no photography of its own yet. --}}
    <div class="relative overflow-hidden rounded-[2.5rem] min-h-[62vh] flex items-center justify-center text-center px-6 py-24 md:py-28 hub-media-well">
        @if($heroImage)
            <img src="{{ $heroImage }}" alt="" aria-hidden="true" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-hub-dark/55" aria-hidden="true"></div>
        @else
            <img
                src="{{ asset('images/creators-hub/mark-white.png') }}"
                alt=""
                aria-hidden="true"
                class="absolute -right-16 -bottom-20 w-56 md:w-72 max-w-none opacity-[0.08] pointer-events-none select-none"
            >
        @endif

        <div class="relative max-w-4xl mx-auto text-white" data-hero-copy>
            <h1 class="font-display text-[clamp(2.25rem,5.4vw,4.5rem)] font-extrabold leading-[1.08] tracking-tight mb-6">
                @site('hero.headline')
            </h1>

            <p class="text-lg md:text-xl text-white/85 leading-relaxed max-w-2xl mx-auto mb-10">
                @site('hero.body')
            </p>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('events.index') }}" class="hub-pill hub-pill-light">@site('hero.primary_cta')</a>
                <a href="#about" class="hub-pill border border-white/50 text-white hover:bg-white/10">@site('hero.secondary_cta')</a>
            </div>
        </div>
    </div>
</section>
