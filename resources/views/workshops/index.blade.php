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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($workshops as $workshop)
                    <div data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                        <div class="h-full bg-white/5 border border-white/10 rounded-2xl p-7 flex flex-col gap-4 transition-transform duration-300 hover:-translate-y-1">
                            <h2 class="font-display text-lg font-bold">{{ app()->getLocale() === 'ar' ? $workshop->name_ar : $workshop->name_en }}</h2>
                            {{-- Sanitized on save (SanitizedRichText cast on Workshop) — safe
                                 to render unescaped. --}}
                            <div class="ccs-richtext text-sm text-gray-400 leading-relaxed flex-1">{!! app()->getLocale() === 'ar' ? $workshop->description_ar : $workshop->description_en !!}</div>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ trans_choice(':count seat|:count seats', $workshop->capacity, ['count' => $workshop->capacity]) }}</p>
                            <a href="{{ route('workshops.show', [$event, $workshop]) }}" class="text-sm font-bold text-ccs-teal-light border-b border-transparent hover:border-ccs-teal-light transition-colors w-fit">{{ __('View Workshop') }}</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-400" data-reveal>{{ __('No workshops yet.') }}</p>
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
