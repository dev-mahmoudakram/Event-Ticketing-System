{{-- resources/views/home/partials/nav.blade.php --}}
@php $sectionBase = ($onHomePage ?? true) ? '' : route('home'); @endphp

{{-- A floating white bar that sits over the hero rather than spanning the viewport,
     matching the reference's detached nav card. --}}
<header
    x-data="{ open: false, scrolled: window.scrollY > 40 }"
    @scroll.window="scrolled = window.scrollY > 40"
    class="fixed top-0 inset-x-0 z-50 hub-shell pt-0"
>
    <div
        class="mx-auto flex items-center justify-between gap-6 bg-white px-5 md:px-8 py-4 transition-shadow duration-300"
        :class="{ 'shadow-lg shadow-hub-purple/10': scrolled && ! open, 'rounded-b-3xl': ! open }"
    >
        <a href="{{ $sectionBase }}#hero" class="flex items-center gap-2.5 shrink-0">
            <img src="{{ asset('images/creators-hub/mark.png') }}" alt="" aria-hidden="true" class="h-8 w-auto">
            <span class="font-display font-extrabold text-lg tracking-tight text-hub-purple">Creators Hub</span>
        </a>

        <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-hub-dark/70">
            <a href="{{ $sectionBase }}#about" class="hover:text-hub-purple transition-colors">{{ __('About') }}</a>
            <a href="{{ $sectionBase }}#audiences" class="hover:text-hub-purple transition-colors">{{ __('Who it is for') }}</a>
            <a href="{{ route('events.index') }}" class="hover:text-hub-purple transition-colors">{{ __('Events') }}</a>
            <a href="{{ $sectionBase }}#partners" class="hover:text-hub-purple transition-colors">{{ __('Partners') }}</a>
            <a href="{{ $sectionBase }}#faq" class="hover:text-hub-purple transition-colors">{{ __('FAQs') }}</a>
        </nav>

        <div class="flex items-center gap-3 shrink-0">
            <div class="flex items-center text-xs font-bold rounded-full border border-hub-purple/20 overflow-hidden">
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'en' ? 'bg-hub-purple text-white' : 'text-hub-dark/60 hover:text-hub-purple' }}">EN</a>
                <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'ar' ? 'bg-hub-purple text-white' : 'text-hub-dark/60 hover:text-hub-purple' }}">AR</a>
            </div>
            {{-- Wrapped, because .hub-pill sets its own display and would outrank `hidden` on the
                 links themselves, leaving both buttons in the bar on mobile as well as the menu. --}}
            <div class="hidden lg:flex items-center gap-3">
                <a href="{{ $sectionBase }}#contact" class="hub-pill hub-pill-outline text-sm !py-2.5 !px-5">{{ __('Contact') }}</a>
                <a href="{{ route('events.index') }}" class="hub-pill hub-pill-solid text-sm !py-2.5 !px-5">{{ __('Explore Events') }}</a>
            </div>
            <button type="button" aria-label="{{ __('Menu') }}" class="lg:hidden w-11 h-11 rounded-full border border-hub-purple/20 text-hub-purple transition-colors hover:bg-hub-purple/5" @click="open = !open">&#9776;</button>
        </div>
    </div>

    <div x-show="open" x-cloak x-transition class="mx-auto bg-white rounded-b-3xl border-t border-hub-purple/10 flex flex-col px-6 pb-6 lg:hidden">
        <a href="{{ $sectionBase }}#about" class="py-3.5 border-b border-hub-purple/10 font-semibold text-hub-dark" @click="open = false">{{ __('About') }}</a>
        <a href="{{ $sectionBase }}#audiences" class="py-3.5 border-b border-hub-purple/10 font-semibold text-hub-dark" @click="open = false">{{ __('Who it is for') }}</a>
        <a href="{{ route('events.index') }}" class="py-3.5 border-b border-hub-purple/10 font-semibold text-hub-dark" @click="open = false">{{ __('Events') }}</a>
        <a href="{{ $sectionBase }}#partners" class="py-3.5 border-b border-hub-purple/10 font-semibold text-hub-dark" @click="open = false">{{ __('Partners') }}</a>
        <a href="{{ $sectionBase }}#faq" class="py-3.5 font-semibold text-hub-dark" @click="open = false">{{ __('FAQs') }}</a>
        <a href="{{ $sectionBase }}#contact" class="mt-5 hub-pill hub-pill-outline text-sm" @click="open = false">{{ __('Contact') }}</a>
        <a href="{{ route('events.index') }}" class="mt-3 hub-pill hub-pill-solid text-sm">{{ __('Explore Events') }}</a>
    </div>
</header>
