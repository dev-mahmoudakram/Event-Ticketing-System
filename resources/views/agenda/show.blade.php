{{-- resources/views/agenda/show.blade.php --}}
@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Agenda'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24" x-data="{ day: 0 }">
        <a href="{{ route('landing.show', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
            <span aria-hidden="true">&larr;</span> {{ __('Back to :event', ['event' => app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en]) }}
        </a>

        <div class="ccs-eyebrow text-ccs-gold" data-reveal>{{ __('Agenda') }}</div>
        <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-10" data-reveal>
            @if($days->isNotEmpty())
                {{ trans_choice(':count day, deliberately paced.|:count days, deliberately paced.', $days->count(), ['count' => $days->count()]) }}
            @else
                {{ __('Agenda') }}
            @endif
        </h1>

        @if($days->isNotEmpty())
            @if($days->count() > 1)
                <div class="flex gap-3 mb-10 flex-wrap">
                    @foreach($days as $index => $sessions)
                        <button type="button" @click="day = {{ $index }}" :class="day === {{ $index }} ? 'bg-ccs-red border-ccs-red' : 'border-white/10'" class="px-6 py-3.5 rounded-lg border text-sm font-bold text-gray-300 transition-colors duration-300">
                            {{ __('Day :n', ['n' => $index + 1]) }} &middot; {{ $sessions->first()->day_date->format('M j') }}
                        </button>
                    @endforeach
                </div>
            @endif

            @foreach($days as $index => $sessions)
                <div x-show="day === {{ $index }}" x-cloak x-transition class="relative">
                    <div class="absolute left-1.25 top-2 bottom-2 w-px bg-white/10"></div>
                    <div class="flex flex-col gap-10">
                        @foreach($sessions as $item)
                            <div class="relative pl-10">
                                <span class="absolute left-0 top-1.5 w-2.75 h-2.75 rounded-full bg-ccs-gold"></span>
                                <div class="flex flex-wrap items-center gap-3 mb-2">
                                    <span class="text-sm font-bold text-gray-400 tabular-nums">{{ $item->start_time->format('H:i') }}</span>
                                    <span class="text-xs font-bold uppercase tracking-wide text-ccs-coral border border-ccs-coral/40 rounded-md px-3 py-1.5 whitespace-nowrap">{{ $item->sessionType->name() }}</span>
                                </div>
                                <div class="font-display font-bold text-lg mb-1">{{ app()->getLocale() === 'ar' ? $item->title_ar : $item->title_en }}</div>
                                @if($item->speakers->isNotEmpty())
                                    <div class="text-sm text-gray-500">{{ $item->speakers->map(fn ($speaker) => app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en)->implode('، ') }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-gray-400" data-reveal>{{ __('No agenda items yet.') }}</p>
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
