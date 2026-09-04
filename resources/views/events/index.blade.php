{{-- resources/views/events/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Creators Hub — '.__('Events'))

@section('bodyClass', 'hub-page hub-page-light')

@section('content')
    @include('home.partials.nav', ['onHomePage' => false])

    <section class="hub-shell scroll-mt-24 pt-40 pb-24 min-h-[70vh]">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-hub-dark/50 hover:text-hub-purple transition-colors mb-10">
            <span aria-hidden="true">&larr;</span> {{ __('Back to Creators Hub') }}
        </a>

        <p class="text-xs font-bold uppercase tracking-[0.18em] text-hub-purple/60 mb-4">{{ __('Events') }}</p>
        <h1 class="font-display text-3xl md:text-5xl font-extrabold leading-[1.05] tracking-tight mb-14 max-w-2xl text-hub-purple">
            {{ __('Events that bring the industry together.') }}
        </h1>

        @if($events->isEmpty())
            <div class="relative overflow-hidden rounded-2xl border border-hub-purple/15 px-8 py-20 md:px-16 md:py-28 text-center">
                
                <div class="relative flex flex-col items-center gap-5">
                    <span class="text-xs font-bold uppercase tracking-[0.18em] text-hub-purple/60">{{ __('Coming Soon') }}</span>
                    <p class="font-display text-2xl md:text-4xl font-bold max-w-2xl text-hub-purple">{{ __('Our first gathering is in the works.') }}</p>
                    <p class="text-hub-dark/60 max-w-lg">{{ __("Get in touch to hear about it first — dates, format, and who's speaking, before anyone else.") }}</p>
                    <a href="{{ route('home') }}#contact" class="mt-3 hub-pill hub-pill-solid text-sm">{{ __('Get in Touch') }}</a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($events as $event)
                    <a href="{{ route('landing.show', $event) }}" class="group block rounded-3xl border border-hub-purple/15 overflow-hidden hover:border-hub-purple/40 transition-colors">
                        <div class="hub-blueprint-grid relative aspect-[16/9] flex items-end p-6 overflow-hidden" style="background-color: var(--color-hub-purple);">
                            @if($event->coverImageUrl())
                                <img src="{{ $event->coverImageUrl() }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover">
                                {{-- Keeps the date readable whatever the photograph is. --}}
                                <span class="absolute inset-0 bg-hub-dark/40" aria-hidden="true"></span>
                            @endif
                            <span class="relative text-xs font-bold uppercase tracking-[0.18em] text-white/70">{{ $event->start_date->format('M j') }}&ndash;{{ $event->end_date->format('j, Y') }}</span>
                        </div>
                        <div class="p-6">
                            <h2 class="font-display text-xl font-bold mb-2 transition-colors group-hover:text-hub-purple">
                                {{ app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en }}
                            </h2>
                            @if($event->venue_name_en)
                                <p class="text-sm text-hub-dark/50">{{ app()->getLocale() === 'ar' ? $event->venue_name_ar : $event->venue_name_en }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @include('home.partials.footer', ['onHomePage' => false])
@endsection
