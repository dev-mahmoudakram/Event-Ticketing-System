{{-- resources/views/home/partials/events.blade.php --}}
<section id="events" class="hub-shell scroll-mt-24 py-6">
    <div class="hub-panel hub-pad">
        <div class="flex flex-wrap items-end justify-between gap-6 mb-10">
            <h2 class="font-display text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.1] tracking-tight text-hub-purple max-w-2xl" data-reveal>
                @site('events.heading')
            </h2>
            <a href="{{ route('events.index') }}" class="hub-pill hub-pill-outline text-sm shrink-0">@site('events.view_all')</a>
        </div>

        @if(!$featuredEvent)
            <div class="rounded-3xl border border-hub-purple/15 px-8 py-16 md:py-24 text-center">
                <p class="font-display text-2xl md:text-3xl font-bold text-hub-purple max-w-2xl mx-auto mb-4">@site('events.empty_heading')</p>
                <p class="text-hub-dark/60 max-w-lg mx-auto mb-8">@site('events.empty_body')</p>
                <a href="#contact" class="hub-pill hub-pill-solid">{{ __('Get in Touch') }}</a>
            </div>
        @else
            @php
                $featuredReel = $featuredEvent->reels->first();
                // Only figures the system can actually count; anything empty drops out.
                $metrics = collect([
                    ['value' => $featuredEvent->speakers_count, 'label' => __('Speakers')],
                    ['value' => $featuredEvent->workshops_count, 'label' => __('Workshops')],
                    ['value' => $featuredEvent->start_date->diffInDays($featuredEvent->end_date) + 1, 'label' => __('Days')],
                ])->filter(fn ($metric) => $metric['value'] > 0);
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-14 items-center">
                <div>
                    <h3 class="font-display text-2xl md:text-4xl font-extrabold leading-tight text-hub-purple mb-3">
                        {{ app()->getLocale() === 'ar' ? $featuredEvent->name_ar : $featuredEvent->name_en }}
                    </h3>
                    <p class="text-hub-dark/60 mb-8">
                        {{ $featuredEvent->start_date->format('j M') }} &ndash; {{ $featuredEvent->end_date->format('j M Y') }}
                        @if($featuredEvent->venue_name_en)
                            &middot; {{ app()->getLocale() === 'ar' ? $featuredEvent->venue_name_ar : $featuredEvent->venue_name_en }}
                        @endif
                    </p>

                    @if($metrics->isNotEmpty())
                        <dl class="flex flex-wrap items-start gap-x-10 gap-y-5 mb-10">
                            @foreach($metrics as $metric)
                                @if(!$loop->first)
                                    <span class="hidden sm:block w-px h-12 bg-hub-purple/15" aria-hidden="true"></span>
                                @endif
                                <div>
                                    <dt class="sr-only">{{ $metric['label'] }}</dt>
                                    <dd class="font-display text-3xl md:text-4xl font-extrabold text-hub-purple leading-none">{{ $metric['value'] }}</dd>
                                    <p class="text-sm text-hub-dark/50 mt-2">{{ $metric['label'] }}</p>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    <a href="{{ route('landing.show', $featuredEvent) }}" class="hub-pill hub-pill-solid">{{ __('View Event') }}</a>
                </div>

                {{-- The event's own cover image is the admin's deliberate choice, so it wins over
                     whichever reel happens to be first. --}}
                <div class="hub-media-well rounded-3xl aspect-[4/3]">
                    @if($featuredEvent->coverImageUrl())
                        <img src="{{ $featuredEvent->coverImageUrl() }}" alt="{{ app()->getLocale() === 'ar' ? $featuredEvent->name_ar : $featuredEvent->name_en }}" loading="lazy">
                    @elseif($featuredReel)
                        <video
                            src="{{ $featuredReel->videoUrl() }}"
                            @if($featuredReel->posterUrl()) poster="{{ $featuredReel->posterUrl() }}" @endif
                            muted loop playsinline autoplay preload="metadata"
                            aria-label="{{ $featuredReel->caption() ?? __('Event clip') }}"
                        ></video>
                    @endif
                </div>
            </div>

            @if($otherEvents->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-10 pt-10 border-t border-hub-purple/10">
                    @foreach($otherEvents as $event)
                        <a href="{{ route('landing.show', $event) }}" class="group rounded-3xl border border-hub-purple/15 overflow-hidden transition-colors hover:border-hub-purple/40">
                            @if($event->coverImageUrl())
                                <div class="hub-media-well aspect-[16/9]">
                                    <img src="{{ $event->coverImageUrl() }}" alt="" loading="lazy">
                                </div>
                            @endif
                            <div class="p-7">
                                <p class="text-sm text-hub-dark/50 mb-2">{{ $event->start_date->format('j M Y') }}</p>
                                <h3 class="font-display text-xl font-bold text-hub-purple">
                                    {{ app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en }}
                                </h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</section>
