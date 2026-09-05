{{-- resources/views/emails/ticket-requests/approved.blade.php --}}
@extends('emails.layout')

@section('preview', __('Your ticket request was approved. One step left.'))

@section('content')
    @php $eventName = app()->getLocale() === 'ar' ? $ticket->event->name_ar : $ticket->event->name_en; @endphp

    <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:1.35;color:#171f22;">{{ __('Your request was approved.') }}</h1>

    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Hi') }} {{ $ticket->name }},
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Your place at :event is held. Confirm the payment and your ticket, with its QR code, is emailed to you straight away.', ['event' => $eventName]) }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 28px;">
        <tr>
            <td style="border-radius:9999px;background:#3c3489;">
                <a href="{{ $paymentUrl }}" style="display:inline-block;padding:15px 34px;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:9999px;">
                    {{ __('Confirm payment and get my ticket') }}
                </a>
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#faf9ff;border:1px solid #e6e4f2;border-radius:12px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 4px;font-size:11px;letter-spacing:0.08em;color:#8b8b8b;">{{ __('Reference') }}</p>
                <p style="margin:0;font-size:16px;font-weight:700;color:#3c3489;letter-spacing:0.04em;"><bdi>{{ $ticket->ticket_number }}</bdi></p>
            </td>
        </tr>
    </table>
@endsection
