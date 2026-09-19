{{-- resources/views/speakers/index.blade.php --}}
@extends('layouts.app')

@section('title', (app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en).' — '.__('Speakers'))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
        <a href="{{ route('landing.show', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
            <span aria-hidden="true">&larr;</span> {{ __('Back to :event', ['event' => app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en]) }}
        </a>

        <div class="ccs-eyebrow text-ccs-coral" data-reveal>{{ __('Speakers') }}</div>
        <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-12 max-w-xl" data-reveal>{{ __('Voices shaping the industry.') }}</h1>

        @if($speakers->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($speakers as $speaker)
                    @php $speakerName = app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en; @endphp
                    <div class="flex flex-col gap-4" data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                        <div class="aspect-3/4 rounded-2xl border border-white/10 overflow-hidden">
                            @if($speaker->photoUrl())
                                <img src="{{ $speaker->photoUrl() }}" alt="{{ $speakerName }}" class="w-full h-full object-cover" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-ccs-maroon/60" aria-hidden="true">
                                    <span class="font-display text-4xl font-extrabold text-ccs-coral/70">{{ \Illuminate\Support\Str::of($speakerName)->explode(' ')->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->implode('') }}</span>
                                </div>
                            @endif
                        </div>
                        <div>
                            <h2 class="font-display font-bold text-xl mb-1">{{ $speakerName }}</h2>
                            <p class="text-ccs-coral font-bold text-sm uppercase tracking-wide mb-3">{{ app()->getLocale() === 'ar' ? $speaker->title_ar : $speaker->title_en }}</p>
                            {{-- Sanitized on save (SanitizedRichText cast on Speaker) — safe to
                                 render unescaped. --}}
                            <div class="ccs-richtext text-sm text-gray-400 leading-relaxed">{!! app()->getLocale() === 'ar' ? $speaker->bio_ar : $speaker->bio_en !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-400" data-reveal>{{ __('No speakers yet.') }}</p>
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
