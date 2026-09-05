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

    {{-- Only tiers that include workshops get a key, and only they get this. --}}
    @if($ticket->workshop_booking_key)
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-top:26px;background:#faf9ff;border:1px solid #e6e4f2;border-radius:12px;">
            <tr>
                <td style="padding:20px 22px;">
                    <p style="margin:0 0 8px;font-size:15px;font-weight:700;color:#171f22;">{{ __('Your ticket includes workshops.') }}</p>
                    <p style="margin:0 0 16px;font-size:14px;line-height:1.8;color:#4a4a4a;">
                        {{ __('Choose them with your reference number and the booking key on your ticket. Places are limited, so earlier is better.') }}
                    </p>
                    <a href="{{ $workshopUrl }}" style="display:inline-block;padding:12px 26px;font-size:14px;font-weight:700;color:#ffffff;background:#3c3489;text-decoration:none;border-radius:9999px;">
                        {{ __('Book my workshops') }}
                    </a>
                </td>
            </tr>
        </table>
    @endif
@endsection
