{{-- resources/views/landing/partials/stats.blade.php --}}
@php
    $attendees = $event->contentFor(\App\Enums\LandingPageSection::Stats, 'attendees_count');
    $countries = $event->contentFor(\App\Enums\LandingPageSection::Stats, 'countries_count');
    $attendeesText = app()->getLocale() === 'ar' ? $attendees?->value_ar : $attendees?->value_en;
    $countriesText = app()->getLocale() === 'ar' ? $countries?->value_ar : $countries?->value_en;
    $speakerCount = $event->speakers->count();
    $workshopCount = $event->workshops->count();
@endphp
@if($event->isSectionVisible('stats') && ($attendeesText || $countriesText || $speakerCount || $workshopCount))
    <section id="stats" class="ccs-section scroll-mt-24">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-white/10 border border-white/10 rounded-2xl overflow-hidden">
            <div class="bg-ccs-black px-8 py-11 text-center" data-reveal>
                <div class="text-4xl md:text-5xl font-extrabold text-ccs-coral" data-stat-value="attendees">{{ $attendeesText ?? '—' }}</div>
                <div class="text-sm text-gray-400 mt-2.5">{{ __('Attendees') }}</div>
            </div>
            <div class="bg-ccs-black px-8 py-11 text-center" data-reveal data-reveal-delay="1">
                <div class="text-4xl md:text-5xl font-extrabold text-ccs-gold" data-stat-value="speakers">{{ $speakerCount }}</div>
                <div class="text-sm text-gray-400 mt-2.5">{{ __('Speakers') }}</div>
            </div>
            <div class="bg-ccs-black px-8 py-11 text-center" data-reveal data-reveal-delay="2">
                <div class="text-4xl md:text-5xl font-extrabold text-ccs-gold" data-stat-value="countries">{{ $countriesText ?? '—' }}</div>
                <div class="text-sm text-gray-400 mt-2.5">{{ __('Countries') }}</div>
            </div>
            <div class="bg-ccs-black px-8 py-11 text-center" data-reveal data-reveal-delay="3">
                <div class="text-4xl md:text-5xl font-extrabold" data-stat-value="workshops">{{ $workshopCount }}</div>
                <div class="text-sm text-gray-400 mt-2.5">{{ __('Workshops') }}</div>
            </div>
        </div>
    </section>
@endif
