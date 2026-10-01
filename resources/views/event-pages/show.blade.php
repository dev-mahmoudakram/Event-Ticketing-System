{{-- resources/views/event-pages/show.blade.php --}}
@extends('layouts.app')

@section('title', $page->title().' — '.(app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en))

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section class="ccs-section scroll-mt-24 pt-32 pb-24">
        <div class="max-w-3xl">
            <a href="{{ route('landing.show', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
                <span aria-hidden="true" class="rtl:rotate-180">&larr;</span> {{ __('Back to :event', ['event' => app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en]) }}
            </a>

            <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-3">{{ $page->title() }}</h1>
            <p class="text-sm text-gray-400 mb-10">{{ __('Last updated: :date', ['date' => $page->updated_at->translatedFormat('j F Y')]) }}</p>

            {{-- Sanitized on save (SanitizedRichText cast) — safe to render unescaped. --}}
            <div class="ccs-richtext text-gray-300 leading-relaxed">{!! $page->body() !!}</div>

            @if($page->key === \App\Enums\RequiredPage::Contact->value)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 mt-12">
                    <div class="flex flex-col gap-3 text-gray-300">
                        @if($event->contact_email)
                            <a href="mailto:{{ $event->contact_email }}" class="hover:text-white break-all">{{ $event->contact_email }}</a>
                        @endif
                        @if($event->contact_phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $event->contact_phone) }}" class="hover:text-white" dir="ltr">{{ $event->contact_phone }}</a>
                        @endif
                        @php
                            $venue = app()->getLocale() === 'ar' ? $event->venue_name_ar : $event->venue_name_en;
                            $address = app()->getLocale() === 'ar' ? $event->venue_address_ar : $event->venue_address_en;
                        @endphp
                        @if($venue || $address)
                            <p class="text-gray-400 leading-relaxed">{{ collect([$venue, $address])->filter()->implode(app()->getLocale() === 'ar' ? '، ' : ', ') }}</p>
                        @endif
                    </div>
                    @include('landing.partials.contact-form', ['event' => $event, 'returnTo' => 'contact-page'])
                </div>
            @endif
        </div>
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
