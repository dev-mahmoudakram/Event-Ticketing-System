@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Event Check-in').' — '.$event->name_en" />

    @if(session('success'))
        <div class="mb-4 rounded border border-ccs-teal-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="max-w-xl rounded border border-hub-purple/10 bg-white p-6">
        <h2 class="font-display text-xl font-bold">{{ __('Scan attendee QR code') }}</h2>
        <p class="mt-2 text-sm text-hub-dark/60">{{ __('Paste the scanned QR content below to verify this ticket.') }}</p>

        <form method="POST" action="{{ route('admin.events.check-in.store', $event) }}" class="mt-6">
            @csrf
            <label for="qr_code" class="mb-2 block text-sm text-hub-dark/75">{{ __('QR code') }}</label>
            <textarea id="qr_code" name="qr_code" rows="6" required autofocus class="w-full rounded border border-hub-purple/20 bg-white px-3 py-2 text-hub-dark">{{ old('qr_code') }}</textarea>
            @error('qr_code')
                <p class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
            <x-admin.button type="submit" class="mt-4">{{ __('Verify and check in') }}</x-admin.button>
        </form>
    </div>
@endsection