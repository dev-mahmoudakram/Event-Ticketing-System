{{-- resources/views/home/partials/partners.blade.php --}}
<section id="partners" class="hub-shell scroll-mt-24 py-6">
    <div class="hub-panel-dark hub-pad" x-data="{
        step() {
            const strip = $refs.strip;
            // Scroll by roughly one tile so the arrows advance the strip predictably at any width.
            return Math.max(240, strip.clientWidth / 3);
        },
        scrollBy(direction) {
            // Chrome counts scroll offsets leftwards in RTL, so Arabic needs the sign flipped
            // for the arrows to keep meaning previous and next rather than left and right.
            const sign = document.documentElement.dir === 'rtl' ? -1 : 1;
            $refs.strip.scrollBy({ left: sign * direction * this.step(), behavior: 'smooth' });
        },
    }">
        <div class="flex flex-wrap items-center justify-between gap-6 mb-12">
            <h2 class="font-display text-[clamp(1.75rem,3.6vw,2.75rem)] font-extrabold leading-[1.1] tracking-tight" data-reveal>
                @site('partners.heading')
            </h2>

            <div class="flex items-center gap-3" data-reveal>
                @if($partners->count() > 3)
                    <button
                        type="button"
                        @click="scrollBy(-1)"
                        class="hub-round-btn hub-round-btn-light text-2xl leading-none"
                        aria-label="{{ __('Previous partners') }}"
                    >&lsaquo;</button>
                    <button
                        type="button"
                        @click="scrollBy(1)"
                        class="hub-round-btn hub-round-btn-light text-2xl leading-none"
                        aria-label="{{ __('Next partners') }}"
                    >&rsaquo;</button>
                @endif

                <a href="#contact" class="hub-pill hub-pill-light text-sm">@site('partners.cta')</a>
            </div>
        </div>

        @if($partners->isNotEmpty())
            {{-- A scrolling strip rather than a fixed grid, so a growing partner list keeps its
                 tile size instead of shrinking to fit. --}}
            <ul
                x-ref="strip"
                class="flex gap-5 overflow-x-auto snap-x snap-mandatory pb-2 mb-12 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                tabindex="0"
                aria-label="{{ __('Partners') }}"
            >
                @foreach($partners as $partner)
                    <li class="snap-start shrink-0 w-[240px] md:w-[280px]">
                        @php $tile = 'flex h-32 items-center justify-center rounded-2xl border border-white/20 px-8 transition-colors hover:border-white/50'; @endphp

                        @if($partner->website_url)
                            <a href="{{ $partner->website_url }}" target="_blank" rel="noopener noreferrer" class="{{ $tile }}">
                        @else
                            <div class="{{ $tile }}">
                        @endif

                            @if($partner->logoUrl())
                                <img src="{{ $partner->logoUrl() }}" alt="{{ $partner->name() }}" class="max-h-12 w-auto object-contain" loading="lazy">
                            @else
                                {{-- No logo uploaded yet: the name keeps the strip even. --}}
                                <span class="font-display text-sm font-bold text-white/80 text-center">{{ $partner->name() }}</span>
                            @endif

                        @if($partner->website_url)
                            </a>
                        @else
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <p class="text-white/70 leading-relaxed max-w-3xl mx-auto text-center" data-reveal data-reveal-delay="1">
            @site('partners.body')
        </p>
    </div>
</section>
