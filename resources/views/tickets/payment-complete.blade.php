@extends('layouts.app')

@section('content')
    @php
        $paymentStatus = \App\Enums\TicketPaymentStatus::class;
        $issued = $ticket->status === \App\Enums\TicketStatus::TicketIssued && $ticket->ticket_id !== null;
    @endphp

    <main class="min-h-screen flex items-center justify-center bg-ccs-black px-6 text-white">
        <div class="max-w-lg text-center">
            @if($issued && $payment->status === $paymentStatus::Paid)
                <h1 class="font-display text-3xl font-bold">{{ __('Payment completed') }}</h1>
                <p class="mt-4 text-gray-300">{{ __('Your ticket has been issued and sent to your email address.') }}</p>
                <p class="mt-3 text-ccs-gold">{{ __('Reference') }}: <bdi>{{ $ticket->ticket_number }}</bdi></p>
                <a href="{{ route('tickets.show', [$ticket, $ticket->ticket_id]) }}" class="mt-6 inline-block rounded-lg bg-ccs-red px-6 py-3 font-bold text-white hover:bg-ccs-maroon">{{ __('View your ticket') }}</a>
            @elseif($payment->status === $paymentStatus::Paid)
                <h1 class="font-display text-3xl font-bold">{{ __('Payment received') }}</h1>
                <p class="mt-4 text-gray-300">{{ __('We received your payment, but this ticket was already processed. Our team will contact you.') }}</p>
                <p class="mt-3 text-ccs-gold">{{ __('Reference') }}: <bdi>{{ $ticket->ticket_number }}</bdi></p>
            @elseif($payment->status === $paymentStatus::Pending && $reportedFailure)
                <h1 class="font-display text-3xl font-bold">{{ __('Your payment did not go through') }}</h1>
                <p class="mt-4 text-gray-300">{{ __('No money was taken. You can try again with the link in your email until :date.', ['date' => $payment->expires_at?->copy()->timezone(config('app.timezone'))->translatedFormat('j F Y, g:i A')]) }}</p>
                <a href="{{ $payment->session_url }}" class="mt-6 inline-block rounded-lg bg-ccs-red px-6 py-3 font-bold text-white hover:bg-ccs-maroon">{{ __('Try again') }}</a>
            @elseif($payment->status === $paymentStatus::Pending)
                <h1 class="font-display text-3xl font-bold">{{ __('We are confirming your payment') }}</h1>
                <p class="mt-4 text-gray-300">{{ __('This usually takes a minute. Your ticket will be emailed to you as soon as the payment is confirmed.') }}</p>
            @else
                <h1 class="font-display text-3xl font-bold">{{ __('This payment link is no longer valid') }}</h1>
                <p class="mt-4 text-gray-300">{{ __('No money was taken. Please contact us and we will send you a new payment link.') }}</p>
            @endif
        </div>
    </main>
@endsection
