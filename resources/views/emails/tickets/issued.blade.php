{{-- resources/views/emails/tickets/issued.blade.php --}}
@extends('emails.layout')

@section('preview', __('Your ticket is attached below. Show the QR code at the entrance.'))

@section('content')
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:1.35;color:#171f22;">{{ __('Your ticket is ready.') }}</h1>

    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Hi') }} {{ $ticket->name }},
    </p>
    <p style="margin:0 0 26px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Your payment came through. Here is your ticket — show the QR code at the entrance and the registration team will check you in.') }}
    </p>

    {{-- The ticket itself, embedded so it is readable without opening an attachment. --}}
    @include('tickets.partials.ticket', ['ticket' => $ticket, 'qrSrc' => $message->embedData($qrImage, 'ticket-qr.png', 'image/png')])

    <p style="margin:24px 0 0;font-size:14px;line-height:1.8;color:#4a4a4a;">
        <a href="{{ $ticketUrl }}" style="color:#3c3489;font-weight:700;text-decoration:none;">{{ __('Open or print your ticket') }}</a>
        <span style="color:#8b8b8b;"> · {{ __('The QR code is also attached to this email.') }}</span>
    </p>
@endsection
