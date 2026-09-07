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
