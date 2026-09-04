{{-- resources/views/home/partials/nav.blade.php --}}
@php $sectionBase = ($onHomePage ?? true) ? '' : route('home'); @endphp
<header
    x-data="{ open: false, scrolled: window.scrollY > 40 }"
    @scroll.window="scrolled = window.scrollY > 40"
    class="fixed top-0 inset-x-0 z-50 flex items-center justify-between gap-4 px-5 md:px-16 py-5 transition-colors duration-300 border-b"
    :class="scrolled ? 'bg-hub-dark/90 backdrop-blur border-white/10' : 'bg-transparent border-transparent'"
>
    <a href="{{ $sectionBase }}#hero" class="flex items-center gap-2.5 shrink-0">
        <img src="{{ asset('images/creators-hub/mark-white.png') }}" alt="" aria-hidden="true" class="h-7 w-auto">
        <span class="font-display font-extrabold text-xl tracking-tight">Creators <span class="text-hub-purple-light">Hub</span></span>
    </a>

    <nav class="hidden lg:flex items-center gap-8 text-sm font-semibold text-gray-300">
        <a href="{{ $sectionBase }}#hero" class="hover:text-white transition-colors">{{ __('Home') }}</a>
        <a href="{{ $sectionBase }}#about" class="hover:text-white transition-colors">{{ __('About') }}</a>
        <a href="{{ route('events.index') }}" class="hover:text-white transition-colors {{ request()->routeIs('events.index') ? 'text-white' : '' }}">{{ __('Events') }}</a>
        <a href="{{ $sectionBase }}#audiences" class="hover:text-white transition-colors">{{ __('Who it is for') }}</a>
        <a href="{{ $sectionBase }}#partners" class="hover:text-white transition-colors">{{ __('Partners') }}</a>
        <a href="{{ $sectionBase }}#contact" class="hover:text-white transition-colors">{{ __('Contact') }}</a>
    </nav>

    <div class="flex items-center gap-3 shrink-0">
        <div class="hidden sm:flex items-center text-xs font-bold border border-white/20 rounded-md overflow-hidden">
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="px-2.5 py-1.5 transition-colors {{ app()->getLocale() === 'en' ? 'bg-white text-hub-dark' : 'text-gray-400 hover:text-white' }}">EN</a>
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" class="px-2.5 py-1.5 transition-colors {{ app()->getLocale() === 'ar' ? 'bg-white text-hub-dark' : 'text-gray-400 hover:text-white' }}">AR</a>
        </div>
        <a href="{{ route('events.index') }}" class="hidden sm:inline-flex px-5 py-2.5 rounded-md hub-btn-primary text-sm font-bold whitespace-nowrap transition-transform duration-200 hover:scale-[1.03]">{{ __('Explore Events') }}</a>
        <button type="button" aria-label="{{ __('Menu') }}" class="lg:hidden w-11 h-11 rounded-md border border-white/20 transition-colors hover:bg-white/10" @click="open = !open">&#9776;</button>
    </div>

    <div x-show="open" x-cloak x-transition class="absolute top-full inset-x-0 bg-hub-dark border-b border-white/10 flex flex-col px-5 pb-6 lg:hidden">
        <a href="{{ $sectionBase }}#hero" class="py-3.5 border-b border-white/10 font-semibold transition-colors hover:text-hub-purple-light" @click="open = false">{{ __('Home') }}</a>
        <a href="{{ $sectionBase }}#about" class="py-3.5 border-b border-white/10 font-semibold transition-colors hover:text-hub-purple-light" @click="open = false">{{ __('About') }}</a>
        <a href="{{ route('events.index') }}" class="py-3.5 border-b border-white/10 font-semibold transition-colors hover:text-hub-purple-light" @click="open = false">{{ __('Events') }}</a>
        <a href="{{ $sectionBase }}#audiences" class="py-3.5 border-b border-white/10 font-semibold transition-colors hover:text-hub-purple-light" @click="open = false">{{ __('Who it is for') }}</a>
        <a href="{{ $sectionBase }}#partners" class="py-3.5 border-b border-white/10 font-semibold transition-colors hover:text-hub-purple-light" @click="open = false">{{ __('Partners') }}</a>
        <a href="{{ $sectionBase }}#contact" class="py-3.5 font-semibold transition-colors hover:text-hub-purple-light" @click="open = false">{{ __('Contact') }}</a>
        <a href="{{ route('events.index') }}" class="mt-4 px-5 py-3 rounded-md hub-btn-primary text-sm font-bold text-center">{{ __('Explore Events') }}</a>
        <div class="sm:hidden flex items-center gap-1 text-xs font-bold border border-white/20 rounded-md overflow-hidden mt-4 w-fit">
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="px-3 py-2 transition-colors {{ app()->getLocale() === 'en' ? 'bg-white text-hub-dark' : 'text-gray-400 hover:text-white' }}">EN</a>
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" class="px-3 py-2 transition-colors {{ app()->getLocale() === 'ar' ? 'bg-white text-hub-dark' : 'text-gray-400 hover:text-white' }}">AR</a>
        </div>
    </div>
</header>
