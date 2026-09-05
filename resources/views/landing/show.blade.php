@extends('layouts.app')

@php
    $eventName = app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en;
    $eventTagline = app()->getLocale() === 'ar' ? $event->tagline_ar : $event->tagline_en;
@endphp

@section('title', $eventName)

@section('meta')
    <x-social-meta
        :title="$eventName"
        :description="$eventTagline"
        :image="$event->coverImageUrl()"
    />
@endsection

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => true])
    @include('landing.partials.hero', ['event' => $event])
    @include('landing.partials.reel', ['event' => $event])
    @include('landing.partials.about', ['event' => $event])
    @include('landing.partials.stats', ['event' => $event])
    @include('landing.partials.speakers', ['event' => $event])
    @include('landing.partials.workshops-teaser', ['event' => $event])
    @include('landing.partials.tickets', ['event' => $event])
    @include('landing.partials.awards-teaser', ['event' => $event])
    @include('landing.partials.gallery', ['event' => $event])
    @include('landing.partials.testimonials', ['event' => $event])
    @include('landing.partials.partners', ['event' => $event])
    @include('landing.partials.faq', ['event' => $event])
    @include('landing.partials.location', ['event' => $event])
    @include('landing.partials.contact', ['event' => $event])
    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => true])
    @include('landing.partials.ticket-request-modal', ['event' => $event])
@endsection
