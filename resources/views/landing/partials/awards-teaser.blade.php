{{-- resources/views/landing/partials/awards-teaser.blade.php --}}
@if($event->isSectionVisible('awards'))
    <section id="awards" class="ccs-section scroll-mt-24">
        <div class="ccs-eyebrow text-ccs-coral" data-reveal>{{ __('CCS Awards') }}</div>
        <h2 class="font-display text-3xl md:text-5xl font-extrabold mb-6" data-reveal>{{ __("Honoring the year's defining work.") }}</h2>
        @php $blurb = $event->contentFor(\App\Enums\LandingPageSection::AwardsTeaser, 'blurb'); @endphp
        @if($blurb)
            {{-- Sanitized on save against the strict 'cms' Purifier profile — see
                 App\Support\RichText — so it is safe to render unescaped here. --}}
            <div class="ccs-richtext text-lg text-gray-300 max-w-2xl leading-relaxed mb-8" data-reveal>{!! app()->getLocale() === 'ar' ? $blurb->value_ar : $blurb->value_en !!}</div>
        @endif
        <a href="{{ route('awards.show', $event) }}" class="inline-block px-7 py-3.5 rounded-lg border border-white/35 text-sm font-bold hover:bg-white hover:text-ccs-black transition-colors">{{ __('Learn More') }}</a>
    </section>
@endif
