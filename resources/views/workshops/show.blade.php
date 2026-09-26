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

        @if($workshop->isScheduled())
            <div class="flex flex-wrap gap-3 mb-8" data-reveal>
                <span class="text-sm font-bold text-ccs-gold border border-ccs-gold/40 rounded-lg px-4 py-2">
                    {{ $workshop->day_date->format('M j') }} &middot; {{ $workshop->start_time->format('H:i') }}–{{ $workshop->end_time->format('H:i') }}
                </span>
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
            @if($workshop->speakers->isNotEmpty())
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">{{ __('Speakers') }}</div>
                    <div class="font-bold">{{ $workshop->speakers->map(fn ($speaker) => app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en)->implode('، ') }}</div>
                </div>
            @endif
        </div>
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
