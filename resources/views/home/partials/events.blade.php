{{-- resources/views/home/partials/events.blade.php --}}
<section id="events" class="scroll-mt-24 hub-section">
    <div class="flex flex-wrap items-end justify-between gap-6 mb-12">
        <h2 class="font-display text-[clamp(2rem,4.5vw,3.25rem)] font-extrabold leading-[1.05] tracking-tight max-w-2xl">
            {{ __('Events that bring the industry together.') }}
        </h2>
        <a href="{{ route('events.index') }}" class="shrink-0 text-sm font-bold text-hub-purple-light hover:text-white transition-colors">{{ __('View All Events') }}</a>
    </div>

    @if(!$featuredEvent)
        <div class="relative overflow-hidden rounded-2xl border border-white/10 hub-blueprint-grid px-8 py-20 md:px-16 md:py-28 text-center">
            <div class="absolute inset-0 bg-gradient-to-b from-hub-dark/60 via-hub-dark/85 to-hub-dark" aria-hidden="true"></div>
            <div class="relative flex flex-col items-center gap-5">
                <p class="font-display text-2xl md:text-4xl font-bold max-w-2xl">{{ __('Our first gathering is in the works.') }}</p>
                <p class="text-gray-400 max-w-lg">{{ __("Get in touch to hear about it first — dates, format, and who's speaking, before anyone else.") }}</p>
                <a href="#contact" class="mt-3 px-7 py-3.5 rounded-lg border border-white/25 text-sm font-bold transition-colors hover:bg-white/5">{{ __('Get in Touch') }}</a>
            </div>
        </div>
    @else
        @php
            $featuredReel = $featuredEvent->reels->first();
            // Only figures the system can actually count; anything empty simply drops out.
            $metrics = collect([
                ['value' => $featuredEvent->speakers_count, 'label' => __('Speakers')],
                ['value' => $featuredEvent->workshops_count, 'label' => __('Workshops')],
                ['value' => $featuredEvent->start_date->diffInDays($featuredEvent->end_date) + 1, 'label' => __('Days')],
            ])->filter(fn ($metric) => $metric['value'] > 0);
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-px bg-white/10 rounded-2xl overflow-hidden border border-white/10">
            {{-- Left: a clip from the event when one exists, otherwise a plain brand panel. --}}
            <div class="relative bg-hub-purple min-h-[280px] lg:min-h-[420px]">
                @if($featuredReel)
                    <video
                        src="{{ $featuredReel->videoUrl() }}"
                        @if($featuredReel->posterUrl()) poster="{{ $featuredReel->posterUrl() }}" @endif
                        muted loop playsinline autoplay preload="metadata"
                        class="absolute inset-0 w-full h-full object-cover"
                        aria-label="{{ $featuredReel->caption() ?? __('Event clip') }}"
                    ></video>
                    <div class="absolute inset-0 bg-gradient-to-t from-hub-dark/70 to-transparent" aria-hidden="true"></div>
                @else
                    <div class="absolute inset-0 hub-blueprint-grid opacity-40" aria-hidden="true"></div>
                @endif
            </div>

            {{-- Right: the event itself. --}}
            <div class="bg-hub-dark p-8 md:p-12 flex flex-col justify-center">
                <p class="text-sm text-hub-purple-light font-semibold mb-4">
                    {{ $featuredEvent->start_date->format('j M') }} &ndash; {{ $featuredEvent->end_date->format('j M Y') }}
                </p>

                <h3 class="font-display text-2xl md:text-4xl font-extrabold leading-tight mb-4">
                    {{ app()->getLocale() === 'ar' ? $featuredEvent->name_ar : $featuredEvent->name_en }}
                </h3>

                @if($featuredEvent->venue_name_en)
                    <p class="text-gray-400 mb-6">{{ app()->getLocale() === 'ar' ? $featuredEvent->venue_name_ar : $featuredEvent->venue_name_en }}</p>
                @endif

                @if($metrics->isNotEmpty())
                    <dl class="flex flex-wrap gap-x-10 gap-y-4 mb-8">
                        @foreach($metrics as $metric)
                            <div>
                                <dt class="sr-only">{{ $metric['label'] }}</dt>
                                <dd class="font-display text-2xl font-extrabold text-hub-purple-light">{{ $metric['value'] }}</dd>
                                <p class="text-xs text-gray-500 mt-1">{{ $metric['label'] }}</p>
                            </div>
                        @endforeach
                    </dl>
                @endif

                <a href="{{ route('landing.show', $featuredEvent) }}" class="self-start px-7 py-3.5 rounded-lg hub-btn-primary text-sm font-bold transition-transform duration-200 hover:scale-[1.03]">
                    {{ __('View Event') }}
                </a>
            </div>
        </div>

        @if($otherEvents->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                @foreach($otherEvents as $event)
                    <a href="{{ route('landing.show', $event) }}" class="group block rounded-2xl border border-white/10 p-7 hover:border-hub-purple-light/40 transition-colors">
                        <p class="text-sm text-hub-purple-light mb-3">{{ $event->start_date->format('j M Y') }}</p>
                        <h3 class="font-display text-xl font-bold transition-colors group-hover:text-hub-purple-light">
                            {{ app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en }}
                        </h3>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
</section>
