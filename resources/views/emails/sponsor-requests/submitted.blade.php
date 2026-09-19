{{-- resources/views/emails/sponsor-requests/submitted.blade.php --}}
@extends('emails.layout')

@section('preview', __('We received your sponsorship request.'))

@section('content')
    @php
        $eventName = app()->getLocale() === 'ar' ? $sponsorRequest->event->name_ar : $sponsorRequest->event->name_en;
        $sponsorName = app()->getLocale() === 'ar' ? $sponsorRequest->name_ar : $sponsorRequest->name_en;
    @endphp

    <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:1.35;color:#171f22;">{{ __('We received your request.') }}</h1>

    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Hi') }} {{ $sponsorRequest->contact_name }},
    </p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#4a4a4a;">
        {{ __('Thank you for :sponsor\'s interest in sponsoring :event. Our team will review your request and get back to you by email.', ['sponsor' => $sponsorName, 'event' => $eventName]) }}
    </p>
@endsection
