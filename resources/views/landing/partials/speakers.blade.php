{{-- resources/views/landing/partials/speakers.blade.php --}}
@if($event->isSectionVisible('speakers'))
    <section id="speakers" class="ccs-section scroll-mt-24">
        <div class="flex flex-wrap items-end justify-between gap-6 mb-12">
            <div>
                <div class="ccs-eyebrow text-ccs-coral" data-reveal>{{ __('Featured Speakers') }}</div>
                <h2 class="font-display text-3xl md:text-5xl font-extrabold" data-reveal>{{ __('Voices shaping the industry.') }}</h2>
            </div>
            <a href="{{ route('speaker-requests.create', $event) }}" class="shrink-0 px-6 py-3 rounded-lg ccs-btn-red text-sm font-bold transition-transform duration-200 hover:scale-[1.03]" data-reveal>
                {{ __('Become a Speaker') }}
            </a>
        </div>
        @if($event->speakers->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($event->speakers as $speaker)
                <div class="group relative aspect-3/4 rounded-2xl border border-white/10 overflow-hidden select-none" data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                    @php $speakerName = app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en; @endphp
                    @if($speaker->photoUrl())
                        <img src="{{ $speaker->photoUrl() }}" alt="{{ $speakerName }}" class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-110" loading="lazy">
                    @else
                        {{-- No photo uploaded yet: initials keep the grid intact instead of a broken image. --}}
                        <div class="w-full h-full flex items-center justify-center bg-ccs-maroon/60" aria-hidden="true">
                            <span class="font-display text-4xl font-extrabold text-ccs-coral/70">{{ \Illuminate\Support\Str::of($speakerName)->explode(' ')->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->implode('') }}</span>
                        </div>
                    @endif
                    <div class="absolute inset-0 bg-ccs-black/95 p-8 flex flex-col justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 ease-out">
                        <h3 class="font-display font-bold text-xl mb-2">{{ app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en }}</h3>
                        <p class="text-ccs-coral font-bold text-sm uppercase tracking-wide mb-5">{{ app()->getLocale() === 'ar' ? $speaker->title_ar : $speaker->title_en }}</p>
                        <div class="w-10 h-px bg-white/20 mb-5"></div>
                        {{-- Sanitized on save (SanitizedRichText cast on Speaker) — safe to
                             render unescaped. --}}
                        <div class="ccs-richtext text-sm text-gray-400 leading-relaxed">{!! app()->getLocale() === 'ar' ? $speaker->bio_ar : $speaker->bio_en !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </section>
@endif
