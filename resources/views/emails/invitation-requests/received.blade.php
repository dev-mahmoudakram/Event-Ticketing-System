@extends('emails.layout')

@section('preview', __('We received your request.'))

@section('content')
    @php $eventName = app()->getLocale() === 'ar' ? $invitationRequest->event->name_ar : $invitationRequest->event->name_en; @endphp
    <h1 style="margin:0 0 16px;font-size:24px;font-weight:800;line-height:1.35;color:#171f22;">{{ __('We received your request.') }}</h1>
    <p style="margin:0 0 12px;font-size:15px;line-height:1.8;color:#4a4a4a;">{{ __('Hi') }} {{ $invitationRequest->name }},</p>
    <p style="margin:0 0 24px;font-size:15px;line-height:1.8;color:#4a4a4a;">{{ __('Thank you for your interest in :event. Our team will review your request and email you as soon as a decision is made.', ['event' => $eventName]) }}</p>
@endsection
