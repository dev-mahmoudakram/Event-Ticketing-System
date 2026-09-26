{{-- resources/views/workshops/index.blade.php --}}
@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Workshops'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
        <a href="{{ route('landing.show', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
            <span aria-hidden="true">&larr;</span> {{ __('Back to :event', ['event' => app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en]) }}
        </a>

        <div class="ccs-eyebrow text-ccs-gold" data-reveal>{{ __('Workshops') }}</div>
        <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-12 max-w-xl" data-reveal>{{ __('Choose your own workshops.') }}</h1>

        @if($workshops->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($workshops as $workshop)
                    <div data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                        <x-schedule-card :entry="\App\Support\ScheduleEntry::forWorkshop($workshop)" :event="$event" />
                    </div>
                @endforeach
            </div>
            <x-schedule-popup />
        @else
            <p class="text-gray-400" data-reveal>{{ __('No workshops yet.') }}</p>
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
