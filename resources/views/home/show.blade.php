@extends('layouts.app')

@php
    use App\Support\SiteText;

    $shareTitle = 'Creators Hub — '.SiteText::get('sharing', 'title');
    $shareImage = SiteText::image('sharing', 'image') ?? asset('images/creators-hub/Logo.png');
@endphp

@section('title', $shareTitle)

@section('meta')
    <x-social-meta
        :title="$shareTitle"
        :description="SiteText::get('sharing', 'description')"
        :image="$shareImage"
    />
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
