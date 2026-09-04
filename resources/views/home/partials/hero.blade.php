{{-- resources/views/home/partials/hero.blade.php --}}
@php
    // Falls back to the single hero image from site content when no slides are set up, and to
    // the brand panel when there is no imagery at all.
    $fallbackImage = \App\Support\SiteText::image('hero', 'image');
    $hasSlides = $heroSlides->isNotEmpty();
@endphp

<section
    id="hero"
    class="relative"
    x-data="{
        slide: 0,
        count: {{ max($heroSlides->count(), 1) }},
        timer: null,
        go(index) {
            this.slide = (index + this.count) % this.count;
            this.restart();
        },
        restart() {
            clearInterval(this.timer);
            // A single slide has nothing to advance to, and auto-advancing motion is
            // withheld when the visitor has asked for reduced motion.
            if (this.count < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            this.timer = setInterval(() => { this.slide = (this.slide + 1) % this.count; }, 6500);
        },
    }"
    x-init="restart()"
>
    <div
        class="relative overflow-hidden w-full h-[100svh] flex items-center justify-center text-center px-6 hub-media-well"
        role="region"
        aria-roledescription="{{ __('carousel') }}"
        aria-label="{{ __('Creators Hub') }}"
        @keydown.right.prevent="go(slide + 1)"
        @keydown.left.prevent="go(slide - 1)"
        tabindex="0"
    >
        @if($hasSlides)
            {{-- Slides cross-fade in place; only the current one is exposed to assistive tech. --}}
            @foreach($heroSlides as $index => $heroSlide)
                <div
                    class="absolute inset-0 transition-opacity duration-[1200ms] ease-out motion-reduce:transition-none"
                    :class="slide === {{ $index }} ? 'opacity-100' : 'opacity-0'"
                    :aria-hidden="slide === {{ $index }} ? 'false' : 'true'"
                >
                    @if($heroSlide->imageUrl())
                        <img src="{{ $heroSlide->imageUrl() }}" alt="" class="w-full h-full object-cover" @if($index > 0) loading="lazy" @endif>
                    @endif
                    <div class="absolute inset-0 bg-hub-dark/55"></div>
                </div>
            @endforeach
        @elseif($fallbackImage)
            <img src="{{ $fallbackImage }}" alt="" aria-hidden="true" class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-hub-dark/55" aria-hidden="true"></div>
        @else
            <img
                src="{{ asset('images/creators-hub/mark-white.png') }}"
                alt=""
                aria-hidden="true"
                class="absolute -right-16 -bottom-20 w-56 md:w-72 max-w-none opacity-[0.08] pointer-events-none select-none"
            >
        @endif

        {{-- Copy sits above the slides. A slide can carry its own words; those that do not
             inherit the shared hero copy, so only the image changes between them. --}}
        <div class="relative max-w-4xl mx-auto text-white" data-hero-copy>
            @if($hasSlides)
                @foreach($heroSlides as $index => $heroSlide)
                    <div
                        x-show="slide === {{ $index }}"
                        x-transition:enter="transition ease-out duration-700 delay-200"
                        x-transition:enter-start="opacity-0 translate-y-4"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        @if($index > 0) x-cloak @endif
                    >
                        <h1 class="font-display text-[clamp(2.25rem,5.4vw,4.5rem)] font-extrabold leading-[1.08] tracking-tight mb-6">{{ $heroSlide->headline() }}</h1>
                        <p class="text-lg md:text-xl text-white/85 leading-relaxed max-w-2xl mx-auto">{{ $heroSlide->body() }}</p>
                    </div>
                @endforeach
            @else
                <h1 class="font-display text-[clamp(2.25rem,5.4vw,4.5rem)] font-extrabold leading-[1.08] tracking-tight mb-6">@site('hero.headline')</h1>
                <p class="text-lg md:text-xl text-white/85 leading-relaxed max-w-2xl mx-auto">@site('hero.body')</p>
            @endif

            <div class="flex flex-wrap justify-center gap-4 mt-10">
                <a href="{{ route('events.index') }}" class="hub-pill hub-pill-light">@site('hero.primary_cta')</a>
                <a href="#about" class="hub-pill border border-white/50 text-white hover:bg-white/10">@site('hero.secondary_cta')</a>
            </div>
        </div>

        @if($heroSlides->count() > 1)
            <div class="absolute inset-x-0 bottom-10 flex justify-center gap-3" role="group" aria-label="{{ __('Choose a slide') }}">
                @foreach($heroSlides as $index => $heroSlide)
                    <button
                        type="button"
                        @click="go({{ $index }})"
                        :aria-current="slide === {{ $index }} ? 'true' : 'false'"
                        aria-label="{{ __('Slide :number', ['number' => $index + 1]) }}"
                        class="h-1.5 rounded-full transition-all duration-500"
                        :class="slide === {{ $index }} ? 'w-10 bg-white' : 'w-6 bg-white/40 hover:bg-white/70'"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
</section>
