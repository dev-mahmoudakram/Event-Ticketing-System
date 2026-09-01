@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Event Check-in').' — '.$event->name_en" />

    @if(session('success'))
        <div class="mb-4 rounded border border-ccs-teal-light/40 bg-ccs-teal-light/10 px-4 py-3 text-sm text-ccs-teal-light" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="max-w-xl rounded border border-gray-800 bg-gray-900 p-6">
        <h2 class="font-display text-xl font-bold">{{ __('Scan attendee QR code') }}</h2>
        <p class="mt-2 text-sm text-gray-400">{{ __('Paste the scanned QR content below to verify this ticket.') }}</p>

        <form method="POST" action="{{ route('admin.events.check-in.store', $event) }}" class="mt-6">
            @csrf
            <label for="qr_code" class="mb-2 block text-sm text-gray-300">{{ __('QR code') }}</label>
            <textarea id="qr_code" name="qr_code" rows="6" required autofocus class="w-full rounded border border-gray-600 bg-gray-950 px-3 py-2 text-white">{{ old('qr_code') }}</textarea>
            @error('qr_code')
                <p class="mt-1 text-sm text-red-400">{{ $message }}</p>
            @enderror
            <x-admin.button type="submit" class="mt-4">{{ __('Verify and check in') }}</x-admin.button>
        </form>
    </div>
@endsection