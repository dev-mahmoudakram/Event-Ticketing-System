{{-- resources/views/agenda/show.blade.php --}}
@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Agenda'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
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
            <div x-data="{ day: 0, type: 'all' }">
                @if($days->count() > 1)
                    <div class="flex gap-3 mb-6 flex-wrap" role="tablist">
                        @foreach($days as $index => $entries)
                            <button type="button" role="tab" data-day-tab="{{ $index }}" @click="day = {{ $index }}; type = 'all'"
                                    :aria-selected="day === {{ $index }}"
                                    :class="day === {{ $index }} ? 'bg-ccs-red border-ccs-red text-white' : 'border-white/10 text-gray-300'"
                                    class="px-6 py-3.5 rounded-lg border text-sm font-bold transition-colors duration-300">
                                {{ __('Day :n', ['n' => $index + 1]) }} &middot; {{ $entries->first()->day()->translatedFormat('j M') }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @foreach($days as $index => $entries)
                    <div x-show="day === {{ $index }}" x-cloak>
                        <div class="flex gap-2 mb-8 flex-wrap">
                            <button type="button" data-type-filter="all" @click="type = 'all'" :class="type === 'all' ? 'bg-ccs-coral text-ccs-red border-ccs-coral' : 'border-white/15 text-gray-300'" class="px-4 py-2 rounded-full border text-sm font-bold">{{ __('All') }}</button>
                            @foreach($entries->unique(fn ($entry) => $entry->typeKey()) as $entry)
                                <button type="button" data-type-filter="{{ $entry->typeKey() }}" @click="type = '{{ $entry->typeKey() }}'" :class="type === '{{ $entry->typeKey() }}' ? 'bg-ccs-coral text-ccs-red border-ccs-coral' : 'border-white/15 text-gray-300'" class="px-4 py-2 rounded-full border text-sm font-bold">{{ $entry->typeLabel() }}</button>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($entries as $entry)
                                <div x-show="type === 'all' || type === '{{ $entry->typeKey() }}'" class="{{ $entry->isBreak() ? 'md:col-span-2 lg:col-span-3' : '' }}">
                                    <x-schedule-card :entry="$entry" :event="$event" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <x-schedule-popup />
        @else
            <p class="text-gray-400" data-reveal>{{ __('No agenda items yet.') }}</p>
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
