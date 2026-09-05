<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Creators Hub Admin'))</title>
    <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="shortcut icon" href="/favicon.ico">
    {{-- The admin panel is a tool, not a page to share, so it carries the icon and nothing else. --}}
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css', 'resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="adm-body min-h-screen" x-data="{ menu: false }">
    {{-- The sidebar slides in on small screens and is simply always there on large ones. --}}
    <div
        x-show="menu"
        x-cloak
        @click="menu = false"
        class="fixed inset-0 z-30 bg-hub-dark/40 lg:hidden"
        x-transition.opacity
    ></div>

    <div class="flex min-h-screen">
        {{-- Fixed at every width: the menu stays put while the page beside it scrolls, and the
             tree scrolls inside its own column when it outgrows the screen. --}}
        <aside
            class="adm-sidebar fixed inset-y-0 start-0 z-40 w-72 flex flex-col transition-transform duration-300 lg:translate-x-0"
            :class="menu ? 'translate-x-0' : '{{ app()->getLocale() === 'ar' ? 'translate-x-full' : '-translate-x-full' }} lg:translate-x-0'"
        >
            @include('admin.partials.sidebar')
        </aside>

        <div class="flex-1 min-w-0 flex flex-col lg:ps-72">
            <header class="adm-topbar sticky top-0 z-20 flex items-center justify-between gap-4 px-5 md:px-8 py-3.5">
                <button
                    type="button"
                    @click="menu = true"
                    class="lg:hidden grid place-items-center w-10 h-10 rounded-xl border border-hub-purple/20 text-hub-purple"
                    aria-label="{{ __('Menu') }}"
                >&#9776;</button>

                <div class="flex items-center gap-3 ms-auto">
                    <div class="flex items-center text-xs font-bold rounded-full border border-hub-purple/20 overflow-hidden">
                        <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'en' ? 'bg-hub-purple text-white' : 'text-hub-dark/60 hover:text-hub-purple' }}">EN</a>
                        <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'ar' ? 'bg-hub-purple text-white' : 'text-hub-dark/60 hover:text-hub-purple' }}">AR</a>
                    </div>

                    <a href="{{ route('home') }}" target="_blank" rel="noopener" class="adm-btn adm-btn-secondary">{{ __('View site') }}</a>
                </div>
            </header>

            <main class="flex-1 px-5 md:px-8 py-8 overflow-x-hidden">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
