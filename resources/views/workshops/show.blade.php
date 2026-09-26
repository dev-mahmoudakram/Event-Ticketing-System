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

        <div class="flex flex-wrap items-center gap-3 mb-8" data-reveal>
            @if($workshop->isScheduled())
                <span class="inline-flex items-center gap-2 text-sm font-bold text-ccs-gold border border-ccs-gold/40 rounded-lg px-4 py-2">
                    <x-bi-clock class="w-4 h-4" aria-hidden="true" />
                    {{ $workshop->day_date->translatedFormat('j M') }} &middot; <span dir="ltr">{{ $workshop->start_time->format('H:i') }}–{{ $workshop->end_time->format('H:i') }}</span>
                </span>
            @else
                <span class="text-sm text-gray-400">{{ __('Time to be announced') }}</span>
            @endif
            @if($workshop->location)
                <span class="inline-flex items-center gap-2 text-sm text-gray-300">
                    <x-bi-geo-alt class="w-4 h-4 text-ccs-coral" aria-hidden="true" />
                    {{ $workshop->location->name() }}
                </span>
            @endif
        </div>

        {{-- Sanitized on save (SanitizedRichText cast on Workshop) — safe to render
             unescaped. --}}
        <div class="ccs-richtext text-lg text-gray-300 leading-relaxed mb-8" data-reveal>{!! app()->getLocale() === 'ar' ? $workshop->description_ar : $workshop->description_en !!}</div>

        <div class="flex flex-wrap gap-8 text-sm" data-reveal>
            <div>
                <div class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">{{ __('Capacity') }}</div>
                <div class="font-bold">{{ trans_choice(':count seat|:count seats', $workshop->capacity, ['count' => $workshop->capacity]) }}</div>
            </div>
        </div>

        @if($workshop->speakers->isNotEmpty())
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-8" data-reveal>
                @foreach($workshop->speakers as $speaker)
                    @php $jobTitle = app()->getLocale() === 'ar' ? $speaker->title_ar : $speaker->title_en; @endphp
                    <li class="flex items-center gap-3 min-w-0">
                        <x-speaker-avatar :speaker="$speaker" size="w-12 h-12" />
                        <div class="min-w-0">
                            <div class="font-semibold truncate">{{ app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en }}</div>
                            @if($jobTitle)<div class="text-xs text-gray-400 truncate">{{ $jobTitle }}</div>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @unless($workshop->isFull())
            <a href="{{ route('workshops.book', $event) }}" class="ccs-schedule-book mt-10" data-reveal>{{ __('Book your seat') }}</a>
        @endunless
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
