@extends('layouts.admin')

@php $eventName = app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en; @endphp

@section('title', __('Registration desk').' — '.$eventName)

@section('content')
    <x-admin.page-header :title="__('Registration desk').' — '.$eventName" />

    <div
        class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_22rem] gap-6"
        x-data="ticketScanner({
            endpoint: '{{ route('check-in.scan', $event) }}',
            arrived: {{ $arrived }},
            labels: {
                start: @js(__('Start camera')),
                stop: @js(__('Stop camera')),
                blocked: @js(__('The camera could not be opened. Check the browser permission, or type the code below.')),
                offline: @js(__('The scan could not be sent. Check the connection and try again.')),
            },
        })"
    >
        <section class="adm-card p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <h2 class="font-display font-bold text-lg">{{ __('Scan a ticket') }}</h2>
                <button type="button" class="adm-btn adm-btn-primary" @click="toggle()" x-text="running ? labels.stop : labels.start"></button>
            </div>

            {{-- html5-qrcode paints the camera into this element; it stays empty until asked. --}}
            <div class="scanner-stage" :class="running ? 'is-live' : ''">
                <div id="scanner-view" class="scanner-view"></div>
                <p class="scanner-idle" x-show="! running" x-cloak>{{ __('The camera is off. Start it to scan tickets.') }}</p>
            </div>

            <p class="text-sm text-red-600 font-semibold mt-3" x-show="cameraError" x-cloak x-text="cameraError"></p>

            {{-- The verdict, large enough to read at arm's length across a busy door. --}}
            <div class="scan-verdict mt-5" :class="verdict ? 'scan-verdict-' + verdict : ''" x-show="verdict" x-cloak role="status" aria-live="assertive">
                <p class="scan-verdict-headline" x-text="message"></p>
                <template x-if="ticket">
                    <div class="scan-verdict-detail">
                        <p class="scan-verdict-name" x-text="ticket.name"></p>
                        <p class="scan-verdict-meta">
                            <bdi x-text="ticket.reference"></bdi>
                            <template x-if="ticket.type"><span> &middot; <span x-text="ticket.type"></span></span></template>
                            <template x-if="verdict === 'used' && ticket.checked_in_at">
                                <span> &middot; {{ __('arrived at') }} <span x-text="ticket.checked_in_at"></span></span>
                            </template>
                        </p>
                    </div>
                </template>
            </div>
        </section>

        <div class="flex flex-col gap-6">
            <section class="adm-card p-6">
                <p class="text-sm text-hub-dark/55">{{ __('Arrived') }}</p>
                <p class="font-display text-4xl font-extrabold text-hub-purple mt-1">
                    <span x-text="arrived"></span><span class="text-hub-dark/30 text-2xl"> / {{ number_format($expected) }}</span>
                </p>
                <p class="text-sm text-hub-dark/55 mt-1">{{ __('of :count issued tickets', ['count' => number_format($expected)]) }}</p>
            </section>

            {{-- Works with the camera off: a hardware scanner types straight into this box. --}}
            <section class="adm-card p-6">
                <h2 class="font-display font-bold text-lg mb-1">{{ __('Type a code') }}</h2>
                <p class="text-sm text-hub-dark/55 mb-4">{{ __('For a damaged code, or a handheld scanner.') }}</p>

                @if(session('success'))
                    <div class="scan-verdict scan-verdict-verified mb-4" role="status">
                        <p class="scan-verdict-headline">{{ session('success') }}</p>
                        @if(session('checked_in_name'))
                            <div class="scan-verdict-detail"><p class="scan-verdict-name">{{ session('checked_in_name') }}</p></div>
                        @endif
                    </div>
                @endif
                @if(session('error'))
                    <div class="scan-verdict scan-verdict-used mb-4" role="alert">
                        <p class="scan-verdict-headline">{{ session('error') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('check-in.store', $event) }}" class="flex flex-col gap-3">
                    @csrf
                    <label for="qr_code" class="text-sm font-semibold">{{ __('Ticket code') }}</label>
                    <input id="qr_code" name="qr_code" type="text" value="{{ old('qr_code') }}" required autocomplete="off" dir="ltr"
                        class="adm-input" placeholder="{{ __('Paste or scan the code') }}">
                    @error('qr_code') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="adm-btn adm-btn-secondary">{{ __('Verify ticket') }}</button>
                </form>
            </section>
        </div>
    </div>
@endsection
