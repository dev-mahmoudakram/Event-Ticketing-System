@extends('layouts.app')

@section('title', 'Creators Hub — '.__('Interior Design & Construction Events'))

@section('meta')
    <meta name="description" content="{{ __('Creators Hub connects the interior design and construction industry through events, community, and collaboration.') }}">
    <meta property="og:title" content="Creators Hub — {{ __('Interior Design & Construction Events') }}">
    <meta property="og:description" content="{{ __('Creators Hub connects the interior design and construction industry through events, community, and collaboration.') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="{{ url('/') }}">
@endsection

@section('bodyClass', 'hub-page hub-page-light')

@section('content')
    @include('home.partials.nav', ['onHomePage' => true])
    @include('home.partials.hero')
    @include('home.partials.stats')
    @include('home.partials.about')
    @include('home.partials.audiences')
    @include('home.partials.events')
    @include('home.partials.community')
    @include('home.partials.why-egypt')
    @include('home.partials.partners')
    @include('home.partials.faq')
    @include('home.partials.cta')
    @include('home.partials.contact')
    @include('home.partials.footer', ['onHomePage' => true])
@endsection
