{{-- resources/views/emails/ticket-requests/rejected.blade.php --}}
@extends('emails.layout')

@section('preview', __('About your ticket request.'))

@section('content')
    @php $eventName = app()->getLocale() === 'ar' ? $ticket->event->name_ar : $ticket->event->name_en; @endphp

    <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:1.35;color:#171f22;">{{ __('About your ticket request') }}</h1>

    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Hi') }} {{ $ticket->name }},
    </p>
    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Thank you for your interest in :event. We are not able to approve your request this time.', ['event' => $eventName]) }}
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('We keep every request on file, so you will hear from us about what is coming next.') }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:#faf9ff;border:1px solid #e6e4f2;border-radius:12px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 4px;font-size:11px;letter-spacing:0.08em;color:#8b8b8b;">{{ __('Reference') }}</p>
                <p style="margin:0;font-size:16px;font-weight:700;color:#3c3489;letter-spacing:0.04em;"><bdi>{{ $ticket->ticket_number }}</bdi></p>
            </td>
        </tr>
    </table>
@endsection
