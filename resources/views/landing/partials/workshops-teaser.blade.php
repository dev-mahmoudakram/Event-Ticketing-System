{{-- resources/views/landing/partials/workshops-teaser.blade.php --}}
@if($event->workshops->isNotEmpty() && $event->isSectionVisible('workshops'))
    <section id="workshops" class="ccs-section scroll-mt-24">
        <div class="ccs-eyebrow text-ccs-gold" data-reveal>{{ __('Workshops') }}</div>
        <h2 class="font-display text-3xl md:text-5xl font-extrabold mb-12 max-w-xl" data-reveal>{{ __('Choose your own workshops.') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            @foreach($event->workshops->take(3) as $workshop)
                <div data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                    <x-schedule-card :entry="\App\Support\ScheduleEntry::forWorkshop($workshop)" :event="$event" />
                </div>
            @endforeach
        </div>
        <x-schedule-popup />
        <a href="{{ route('workshops.index', $event) }}" class="inline-block px-7 py-3.5 rounded-lg border border-white/35 text-sm font-bold hover:bg-white hover:text-ccs-black transition-colors">{{ __('See All Workshops') }}</a>
    </section>
@endif
