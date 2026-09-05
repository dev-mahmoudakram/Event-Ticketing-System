<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'CCS')</title>
    {{-- Icon set generated for Creators Hub; every page of the platform carries it. --}}
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <meta name="apple-mobile-web-app-title" content="CreatorsHub">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#ffffff">
    @yield('meta')
    @foreach ([
        'node_modules/@fontsource/manrope/files/manrope-latin-800-normal.woff2',
        'node_modules/@fontsource/manrope/files/manrope-latin-700-normal.woff2',
        'node_modules/@fontsource/inter/files/inter-latin-400-normal.woff2',
        'node_modules/@fontsource/inter/files/inter-latin-500-normal.woff2',
        'node_modules/@fontsource/cairo/files/cairo-arabic-400-normal.woff2',
        'node_modules/@fontsource/cairo/files/cairo-arabic-700-normal.woff2',
    ] as $fontSource)
        <link rel="preload" as="font" type="font/woff2" href="{{ \Illuminate\Support\Facades\Vite::asset($fontSource) }}" crossorigin>
    @endforeach
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('bodyClass', 'bg-ccs-red text-white')">
    @yield('content')
</body>
</html>
