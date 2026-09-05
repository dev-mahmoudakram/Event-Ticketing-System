<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('CCS Admin'))</title>
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    {{-- The admin panel is a tool, not a page to share, so it carries the icon and nothing else. --}}
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-ccs-black text-white flex min-h-screen">
    @include('admin.partials.sidebar')
    <main class="flex-1 p-8 overflow-x-hidden">
        @yield('content')
    </main>
</body>
</html>
