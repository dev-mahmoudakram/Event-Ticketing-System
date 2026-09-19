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
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($speakers as $speaker)
                    @php $speakerName = app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en; @endphp
                    <div
                        class="group relative aspect-3/4 rounded-2xl border border-white/10 overflow-hidden select-none"
                        data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}"
                        x-data="{ open: false }"
                        @click="open = !open"
                    >
                        @if($speaker->photoUrl())
                            <img src="{{ $speaker->photoUrl() }}" alt="{{ $speakerName }}" class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-110" loading="lazy">
                        @else
                            {{-- No photo uploaded yet: initials keep the grid intact instead of a broken image. --}}
                            <div class="w-full h-full flex items-center justify-center bg-ccs-maroon/60" aria-hidden="true">
                                <span class="font-display text-4xl font-extrabold text-ccs-coral/70">{{ \Illuminate\Support\Str::of($speakerName)->explode(' ')->take(2)->map(fn ($part) => \Illuminate\Support\Str::substr($part, 0, 1))->implode('') }}</span>
                            </div>
                        @endif
                        {{-- Hover reveals this on pointer devices; a tap toggles it on touch, since
                             :hover never fires (or never releases) on a touchscreen. --}}
                        <div
                            class="absolute inset-0 bg-ccs-black/95 p-8 flex flex-col justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-500 ease-out"
                            :class="open && 'opacity-100'"
                        >
                            <h3 class="font-display font-bold text-xl mb-2">{{ $speakerName }}</h3>
                            <p class="text-ccs-coral font-bold text-sm uppercase tracking-wide mb-5">{{ app()->getLocale() === 'ar' ? $speaker->title_ar : $speaker->title_en }}</p>
                            <div class="w-10 h-px bg-white/20 mb-5"></div>
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
