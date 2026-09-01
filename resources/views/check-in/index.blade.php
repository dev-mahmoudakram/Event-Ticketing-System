@extends('layouts.app')

@section('content')
    <main class="check-in-shell">
        <div class="check-in-panel">
            <div class="check-in-mark" aria-hidden="true"><span></span></div>
            <p class="check-in-kicker">{{ __('Event access') }}</p>
            <h1>{{ __('Ticket verification') }}</h1>
            <p class="check-in-event">{{ $event->name_en }}</p>

            @if(session('success'))
                <div class="check-in-alert check-in-alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="check-in-alert check-in-alert-error" role="alert">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('check-in.store', $event) }}" class="check-in-form">
                @csrf
                <label for="qr_code">{{ __('Scanned ticket link or ID') }}</label>
                <input id="qr_code" name="qr_code" type="text" value="{{ old('qr_code') }}" required autofocus autocomplete="off" placeholder="{{ __('Scan QR code') }}">
                @error('qr_code') <p class="check-in-field-error">{{ $message }}</p> @enderror
                <button type="submit">{{ __('Verify ticket') }}</button>
            </form>
        </div>
    </main>
@endsection