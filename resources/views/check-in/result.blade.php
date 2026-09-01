@extends('layouts.app')

@section('content')
    <main class="check-in-shell">
        <div class="check-in-panel check-in-result-panel">
            <div class="check-in-result-icon check-in-result-icon-{{ $result }}" aria-hidden="true">
                <span>{{ $result === 'verified' ? '✓' : '!' }}</span>
            </div>

            @if($result === 'verified')
                <p class="check-in-kicker check-in-kicker-success">{{ __('Access confirmed') }}</p>
                <h1>{{ __('Verified') }}</h1>
                <p class="check-in-result-message">{{ __('Entry allowed. This ticket is now checked in.') }}</p>
            @elseif($result === 'used')
                <p class="check-in-kicker check-in-kicker-error">{{ __('Access denied') }}</p>
                <h1>{{ __('Already checked in') }}</h1>
                <p class="check-in-result-message">{{ __('This ticket has already been used.') }}</p>
            @elseif($result === 'unpaid')
                <p class="check-in-kicker check-in-kicker-error">{{ __('Access denied') }}</p>
                <h1>{{ __('Unverified') }}</h1>
                <p class="check-in-result-message">{{ __('Payment is not completed.') }}</p>
            @else
                <p class="check-in-kicker check-in-kicker-error">{{ __('Access denied') }}</p>
                <h1>{{ __('Unverified ticket') }}</h1>
                <p class="check-in-result-message">{{ __('This ticket was not found in our records.') }}</p>
            @endif

            @if($ticket)
                <div class="check-in-details">
                    <div><span>{{ __('Name') }}</span><strong>{{ $ticket->name }}</strong></div>
                    <div><span>{{ __('Email') }}</span><strong>{{ $ticket->email }}</strong></div>
                    <div><span>{{ __('Reference') }}</span><strong>{{ $ticket->ticket_number }}</strong></div>
                    <div><span>{{ __('Status') }}</span><strong>{{ $ticket->status->value }}</strong></div>
                </div>
            @endif

            <a class="check-in-back" href="{{ route('check-in.index', $event) }}">{{ __('Scan another ticket') }}</a>
        </div>
    </main>
@endsection