{{-- resources/views/landing/partials/hero.blade.php --}}
@php
    $locale = app()->getLocale();
    $headline = $event->contentFor(\App\Enums\LandingPageSection::Hero, 'headline');
    $headlineText = $locale === 'ar'
        ? ($headline?->value_ar ?: $event->name_ar)
        : ($headline?->value_en ?: $event->name_en);
    $venueName = $locale === 'ar' ? $event->venue_name_ar : $event->venue_name_en;
    $tagline = $locale === 'ar' ? $event->tagline_ar : $event->tagline_en;

    // The wordmark stacks the headline over two lines with the year set in the accent
    // colour. Event names usually already end in the year ("Content Creators Summit 2026"),
    // so lift that off the end rather than printing it twice.
    $headlineText = trim($headlineText);
    $displayYear = $event->start_date->format('Y');

    if (preg_match('/^(.*?)[\s\x{2013}\x{2014}-]*(\d{4})$/u', $headlineText, $matches) && trim($matches[1]) !== '') {
        $headlineText = trim($matches[1]);
        $displayYear = $matches[2];
    }

    $words = preg_split('/\s+/u', $headlineText) ?: [$headlineText];
    $lastWord = array_pop($words);
    $leadLine = implode(' ', $words);

    // The programme pill lists what this event actually has, rather than a fixed menu.
    $tracks = collect([
        $event->speakers->isNotEmpty() ? __('Talks') : null,
        $event->workshops->isNotEmpty() ? __('Workshops') : null,
        $event->isSectionVisible('awards') ? __('Awards') : null,
        $event->reels->isNotEmpty() ? __('Reels') : null,
        __('& more'),
    ])->filter()->values();

    // Two clips ride each side of the wordmark; the hero simply omits them when empty.
    $heroReels = $event->reels->take(4);
    $leftReels = $heroReels->slice(0, 2)->values();
    $rightReels = $heroReels->slice(2, 2)->values();
@endphp

<section
    id="hero"
    data-hero
    class="ccs-hero-bloom scroll-mt-24 relative min-h-screen flex items-center overflow-hidden px-[clamp(20px,5vw,64px)] pt-[132px] pb-[88px]"
>
    <div class="relative w-full max-w-[1400px] mx-auto grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,2.1fr)_minmax(0,1fr)] gap-8 xl:gap-12 items-center">

        {{-- Left clips --}}
        <div class="hidden lg:flex flex-col gap-8 pt-16">
            @foreach($leftReels as $reel)
                <div class="ccs-phone {{ $loop->first ? 'w-[190px]' : 'w-[160px] ms-10' }}" data-hero-phone data-hero-phone-side="start">
                    <video
                        data-src="{{ $reel->videoUrl() }}"
                        @if($reel->posterUrl()) poster="{{ $reel->posterUrl() }}" @endif
                        muted loop playsinline preload="none"
                        data-autoplay-video
                        aria-label="{{ $reel->caption() ?? __('Event clip') }}"
                    ></video>
                </div>
            @endforeach
        </div>

        {{-- Centre lockup --}}
        <div class="text-center flex flex-col items-center">
            <p class="ccs-eyebrow text-ccs-teal-light mb-4" data-hero-bit>
                @if($event->isSingleDay())
                    {{ $event->start_date->translatedFormat('M j, Y') }}
                @else
                    {{ $event->start_date->translatedFormat('M j') }}&ndash;{{ $event->end_date->translatedFormat('j, Y') }}
                @endif
                @if($venueName) &middot; {{ $venueName }} @endif
            </p>

            <h1 class="ccs-wordmark text-[clamp(2.1rem,5.4vw,4.75rem)] mb-6">
                @if($leadLine !== '')
                    <span class="ccs-wordmark-line" data-hero-line><span class="block">{{ $leadLine }}</span></span>
                @endif
                <span class="ccs-wordmark-line" data-hero-line>
                    <span class="block">
                        {{ $lastWord }}@if($lastWord !== $displayYear)<span class="text-ccs-coral ms-3">{{ $displayYear }}</span>@endif
                    </span>
                </span>
            </h1>

            @if($tagline)
                <p class="ccs-ribbon text-xs md:text-sm mb-7" data-hero-bit>{{ $tagline }}</p>
            @endif

            <div class="ccs-track-pill px-6 py-3 mb-8 max-w-full" data-hero-bit>
                <p class="text-[0.7rem] md:text-xs font-bold uppercase tracking-[0.14em] text-white/90">
                    {{ $tracks->join(' · ') }}
                </p>
            </div>

            <div class="flex items-stretch rounded-xl overflow-hidden border border-white/15 mb-9 text-start" data-hero-bit>
                <div class="bg-ccs-teal/70 px-4 py-3 flex items-center">
                    <span class="text-[0.65rem] font-bold uppercase tracking-widest">{{ $venueName ?: __('Venue TBA') }}</span>
                </div>
                <div class="bg-ccs-red px-5 py-3">
                    <span class="font-display text-xl md:text-2xl font-extrabold leading-none">
                        @if($event->isSingleDay())
                            {{ mb_strtoupper($event->start_date->translatedFormat('M j'), 'UTF-8') }}
                        @else
                            {{ mb_strtoupper($event->start_date->translatedFormat('M j'), 'UTF-8') }}&ndash;{{ $event->end_date->translatedFormat('j') }}
                        @endif
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-4" data-hero-bit>
                <a href="#tickets" class="px-8 py-4 rounded-lg ccs-btn-red text-base font-bold transition-transform duration-200 hover:scale-[1.03]">
                    {{ __('Request Your Ticket') }} <span aria-hidden="true">&rarr;</span>
                </a>
                <a href="{{ route('agenda.show', $event) }}" class="px-8 py-4 rounded-lg border border-white/35 text-base font-bold transition-colors hover:bg-white/5">
                    {{ __('Explore Event') }}
                </a>
            </div>

            {{-- Kept from the previous hero: a live countdown, set quietly so it supports the
                 lockup instead of competing with it. --}}
            <div
                class="flex items-center gap-5 md:gap-7 mt-9 text-center"
                data-hero-bit
                x-data="{
                    now: Date.now(),
                    target: new Date('{{ $event->start_date->toDateString() }}').getTime(),
                    get diff() { return Math.max(0, this.target - this.now); },
                    get d() { return String(Math.floor(this.diff / 86400000)).padStart(2, '0'); },
                    get h() { return String(Math.floor(this.diff / 3600000) % 24).padStart(2, '0'); },
                    get m() { return String(Math.floor(this.diff / 60000) % 60).padStart(2, '0'); },
                    get s() { return String(Math.floor(this.diff / 1000) % 60).padStart(2, '0'); },
                }"
                x-init="setInterval(() => now = Date.now(), 1000)"
            >
                @foreach([['d', __('Days')], ['h', __('Hours')], ['m', __('Min')], ['s', __('Sec')]] as [$unit, $label])
                    <div class="flex flex-col items-center">
                        <span class="font-display text-xl md:text-2xl font-extrabold tabular-nums text-ccs-gold" x-text="{{ $unit }}"></span>
                        <span class="text-[0.6rem] uppercase tracking-[0.18em] text-gray-400 mt-1">{{ $label }}</span>
                    </div>
                    @if(!$loop->last)
                        <span class="text-white/20 font-display text-xl" aria-hidden="true">:</span>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Right clips --}}
        <div class="hidden lg:flex flex-col gap-8 items-end pb-16">
            @foreach($rightReels as $reel)
                <div class="ccs-phone {{ $loop->first ? 'w-[160px] me-10' : 'w-[190px]' }}" data-hero-phone data-hero-phone-side="end">
                    <video
                        data-src="{{ $reel->videoUrl() }}"
                        @if($reel->posterUrl()) poster="{{ $reel->posterUrl() }}" @endif
                        muted loop playsinline preload="none"
                        data-autoplay-video
                        aria-label="{{ $reel->caption() ?? __('Event clip') }}"
                    ></video>
                </div>
            @endforeach
        </div>
    </div>
</section>
