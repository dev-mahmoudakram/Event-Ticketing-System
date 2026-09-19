{{-- resources/views/emails/speaker-requests/submitted.blade.php --}}
@extends('emails.layout')

@section('preview', __('We received your speaker request.'))

@section('content')
    @php
        $eventName = app()->getLocale() === 'ar' ? $speakerRequest->event->name_ar : $speakerRequest->event->name_en;
        $speakerName = app()->getLocale() === 'ar' ? $speakerRequest->name_ar : $speakerRequest->name_en;
    @endphp

    <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:1.35;color:#171f22;">{{ __('We received your request.') }}</h1>

    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Hi') }} {{ $speakerName }},
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Thank you for applying to speak at :event. Our team will review your request and get back to you by email.', ['event' => $eventName]) }}
    </p>
@endsection
