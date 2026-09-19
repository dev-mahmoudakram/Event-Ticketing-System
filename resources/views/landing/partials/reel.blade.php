{{-- resources/views/landing/partials/reel.blade.php --}}
@if($event->reels->isNotEmpty() && $event->isSectionVisible('reel'))
    @php $activeIndex = intdiv($event->reels->count(), 2); @endphp

    <section id="reel" class="scroll-mt-24 relative bg-ccs-black py-24 md:py-32 overflow-hidden">
        <div class="max-w-[1440px] mx-auto px-[clamp(20px,6vw,80px)] text-center">
            <p class="inline-block px-4 py-1.5 rounded-md bg-ccs-coral text-ccs-red text-[0.68rem] font-extrabold uppercase tracking-[0.16em] mb-6" data-reveal>
                {{ __('Straight from the floor') }}
            </p>
            <h2 class="ccs-wordmark text-[clamp(2.5rem,8vw,6rem)] mb-5" data-reveal>{{ __('The Reel') }}</h2>
            <p class="text-gray-400 max-w-md mx-auto mb-14" data-reveal data-reveal-delay="1">
                {{ __('Every clip here was shot at the summit — no stock footage, no staging.') }}
            </p>

            <div
                class="ccs-reel-stage"
                data-reel-stage
                role="group"
                aria-roledescription="{{ __('carousel') }}"
                aria-label="{{ __('The Reel') }}"
                tabindex="0"
            >
                @foreach($event->reels as $reel)
                    <div
                        class="ccs-reel-card"
                        data-reel-card
                        data-reel-caption="{{ $reel->caption() }}"
                        data-active="{{ $loop->index === $activeIndex ? 'true' : 'false' }}"
                        role="button"
                        tabindex="{{ $loop->index === $activeIndex ? '0' : '-1' }}"
                        aria-label="{{ $reel->caption() ?: __('Event clip :n', ['n' => $loop->iteration]) }}"
                    >
                        <video
                            data-src="{{ $reel->videoUrl() }}"
                            @if($reel->posterUrl()) poster="{{ $reel->posterUrl() }}" @endif
                            muted loop playsinline preload="none"
                            data-autoplay-video
                        ></video>

                        {{-- One control per clip, but only the active card's is reachable/visible in
                             practice (see .ccs-reel-mute in _ccs-landing.scss) — the inactive cards'
                             copies just ride along with the rest of that card's faded, scaled state. --}}
                        <button
                            type="button"
                            class="ccs-reel-mute"
                            data-reel-mute
                            data-label-unmute="{{ __('Turn sound on') }}"
                            data-label-mute="{{ __('Turn sound off') }}"
                            aria-pressed="false"
                            aria-label="{{ __('Turn sound on') }}"
                        >
                            <svg data-reel-mute-icon="muted" aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="4 9 9 9 13 5 13 19 9 15 4 15 4 9"></polygon>
                                <line x1="17" y1="9" x2="22" y2="14"></line>
                                <line x1="22" y1="9" x2="17" y2="14"></line>
                            </svg>
                            <svg data-reel-mute-icon="unmuted" aria-hidden="true" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="4 9 9 9 13 5 13 19 9 15 4 15 4 9"></polygon>
                                <path d="M17.5 8.5a5 5 0 0 1 0 7"></path>
                                <path d="M20 6a9 9 0 0 1 0 12"></path>
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center justify-center gap-6 mt-10">
                <button type="button" class="ccs-reel-nav" data-reel-prev aria-label="{{ __('Previous clip') }}">
                    <span aria-hidden="true">&lsaquo;</span>
                </button>
                <p class="font-display text-lg md:text-2xl font-extrabold uppercase tracking-wide min-w-[10rem]" data-reel-caption-output aria-live="polite">
                    {{ $event->reels[$activeIndex]->caption() }}
                </p>
                <button type="button" class="ccs-reel-nav" data-reel-next aria-label="{{ __('Next clip') }}">
                    <span aria-hidden="true">&rsaquo;</span>
                </button>
            </div>
        </div>
    </section>
@endif
