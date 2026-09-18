{{-- resources/views/workshops/show.blade.php --}}
@extends('layouts.app')

@section('title', app()->getLocale() === 'ar' ? $workshop->name_ar : $workshop->name_en)

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24 max-w-3xl">
        <a href="{{ route('workshops.index', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
            <span aria-hidden="true">&larr;</span> {{ __('All Workshops') }}
        </a>

        <div class="ccs-eyebrow text-ccs-gold" data-reveal>{{ __('Workshops') }}</div>
        <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-6" data-reveal>{{ app()->getLocale() === 'ar' ? $workshop->name_ar : $workshop->name_en }}</h1>

        @if($workshop->agendaItems->isNotEmpty())
            <div class="flex flex-wrap gap-3 mb-8" data-reveal>
                @foreach($workshop->agendaItems as $session)
                    <span class="text-sm font-bold text-ccs-teal-light border border-ccs-teal-light/40 rounded-lg px-4 py-2">
                        {{ $session->day_date->format('M j') }} &middot; {{ $session->start_time->format('H:i') }}–{{ $session->end_time->format('H:i') }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Sanitized on save (SanitizedRichText cast on Workshop) — safe to render
             unescaped. --}}
        <div class="ccs-richtext text-lg text-gray-300 leading-relaxed mb-8" data-reveal>{!! app()->getLocale() === 'ar' ? $workshop->description_ar : $workshop->description_en !!}</div>

        <div class="flex flex-wrap gap-8 text-sm" data-reveal>
            <div>
                <div class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">{{ __('Capacity') }}</div>
                <div class="font-bold">{{ trans_choice(':count seat|:count seats', $workshop->capacity, ['count' => $workshop->capacity]) }}</div>
            </div>
            @if($workshop->speaker)
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">{{ __('Speaker') }}</div>
                    <div class="font-bold">{{ app()->getLocale() === 'ar' ? $workshop->speaker->name_ar : $workshop->speaker->name_en }}</div>
                </div>
            @endif
        </div>
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
