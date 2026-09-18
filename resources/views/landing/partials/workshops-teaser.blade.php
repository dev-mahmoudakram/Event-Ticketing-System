{{-- resources/views/landing/partials/workshops-teaser.blade.php --}}
@if($event->workshops->isNotEmpty() && $event->isSectionVisible('workshops'))
    <section id="workshops" class="ccs-section scroll-mt-24">
        <div class="ccs-eyebrow text-ccs-gold" data-reveal>{{ __('Workshops') }}</div>
        <h2 class="font-display text-3xl md:text-5xl font-extrabold mb-12 max-w-xl" data-reveal>{{ __('Choose your own workshops.') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
            @foreach($event->workshops->take(3) as $workshop)
                <div data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                    <div class="h-full bg-white/5 border border-white/10 rounded-2xl p-7 flex flex-col gap-4 transition-transform duration-300 hover:-translate-y-1">
                        <h3 class="font-display text-lg font-bold">{{ app()->getLocale() === 'ar' ? $workshop->name_ar : $workshop->name_en }}</h3>
                        {{-- Sanitized on save (SanitizedRichText cast on Workshop) — safe to
                             render unescaped. --}}
                        <div class="ccs-richtext text-sm text-gray-400 leading-relaxed flex-1">{!! app()->getLocale() === 'ar' ? $workshop->description_ar : $workshop->description_en !!}</div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">{{ trans_choice(':count seat|:count seats', $workshop->capacity, ['count' => $workshop->capacity]) }}</p>
                        <a href="{{ route('workshops.show', [$event, $workshop]) }}" class="text-sm font-bold text-ccs-teal-light border-b border-transparent hover:border-ccs-teal-light transition-colors w-fit">{{ __('View Workshop') }}</a>
                    </div>
                </div>
            @endforeach
        </div>
        <a href="{{ route('workshops.index', $event) }}" class="inline-block px-7 py-3.5 rounded-lg border border-white/35 text-sm font-bold hover:bg-white hover:text-ccs-black transition-colors">{{ __('See All Workshops') }}</a>
    </section>
@endif
