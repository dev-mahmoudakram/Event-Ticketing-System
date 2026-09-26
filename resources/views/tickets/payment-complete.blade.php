@extends('layouts.app')

@section('content')
    <main class="min-h-screen flex items-center justify-center bg-ccs-black px-6 text-white">
        <div class="max-w-lg text-center">
            @if($ticket->status === \App\Enums\TicketStatus::TicketIssued)
                <h1 class="font-display text-3xl font-bold">{{ __('Payment completed') }}</h1>
                <p class="mt-4 text-gray-300">{{ __('Your ticket has been issued and sent to your email address.') }}</p>
                <p class="mt-3 text-ccs-teal-light">{{ __('Reference') }}: {{ $ticket->ticket_number }}</p>
                <a href="{{ route('tickets.show', [$ticket, $ticket->ticket_id]) }}" class="mt-6 inline-block rounded-lg bg-ccs-red px-6 py-3 font-bold text-white hover:bg-ccs-maroon">{{ __('View your ticket') }}</a>
            @else
                <h1 class="font-display text-3xl font-bold">{{ __('This payment link has already been used') }}</h1>
                <p class="mt-4 text-gray-300">{{ __('Your ticket was already processed. Please check your email.') }}</p>
            @endif
        </div>
    </main>
@endsection