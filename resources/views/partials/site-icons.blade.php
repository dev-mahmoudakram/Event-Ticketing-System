{{-- resources/views/partials/site-icons.blade.php --}}
@php
    use App\Models\Event;

    // Every event-scoped page binds the event to the route, so an event's own icons are picked
    // up here without each view having to pass them along.
    $routeEvent = request()->route('event');
    $routeEvent = $routeEvent instanceof Event ? $routeEvent : null;

    $favicon = $routeEvent?->faviconUrl();
    $appleTouchIcon = $routeEvent?->appleTouchIconUrl();
    $appTitle = $routeEvent
        ? (app()->getLocale() === 'ar' ? $routeEvent->name_ar : $routeEvent->name_en)
        : 'CreatorsHub';
@endphp

@if($favicon)
    <link rel="icon" href="{{ $favicon }}">
    <link rel="shortcut icon" href="{{ $favicon }}">
@else
    {{-- Icon set generated for Creators Hub; the platform's own pages carry it. --}}
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
@endif

<link rel="apple-touch-icon" sizes="180x180" href="{{ $appleTouchIcon ?? '/apple-touch-icon.png' }}">
<meta name="apple-mobile-web-app-title" content="{{ $appTitle }}">

@unless($routeEvent)
    {{-- The manifest names the platform, so an event page does not claim it. --}}
    <link rel="manifest" href="/site.webmanifest">
@endunless
<meta name="theme-color" content="#ffffff">
