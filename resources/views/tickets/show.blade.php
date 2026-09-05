{{-- resources/views/tickets/show.blade.php --}}
@extends('layouts.app')

@php $eventName = app()->getLocale() === 'ar' ? $ticket->event->name_ar : $ticket->event->name_en; @endphp

@section('title', __('Your ticket').' — '.$eventName)

@section('meta')
    {{-- A ticket is nobody else's business, and its link should not be indexed. --}}
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('bodyClass', 'bg-hub-lavender text-hub-dark')

@section('content')
    <main class="min-h-screen px-4 py-10 md:py-16">
        <div class="max-w-[640px] mx-auto">
            <div class="ticket-sheet">
                @include('tickets.partials.ticket', ['ticket' => $ticket, 'qrSrc' => $qrSrc])
            </div>

            <div class="flex flex-wrap justify-center gap-3 mt-8 print:hidden">
                <button type="button" onclick="window.print()" class="hub-pill hub-pill-solid text-sm">{{ __('Print this ticket') }}</button>
                <a href="{{ route('landing.show', $ticket->event) }}" class="hub-pill hub-pill-outline text-sm">{{ __('Back to the event') }}</a>
            </div>

            @if($ticket->checked_in_at)
                <p class="text-center text-sm text-hub-dark/60 mt-6">
                    {{ __('Checked in on :date', ['date' => $ticket->checked_in_at->translatedFormat('j M Y, H:i')]) }}
                </p>
            @endif
        </div>
    </main>
@endsection
