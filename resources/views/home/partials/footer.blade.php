{{-- resources/views/home/partials/footer.blade.php --}}
@php $sectionBase = ($onHomePage ?? true) ? '' : route('home'); @endphp
<footer class="w-full px-5 md:px-16 pb-16 pt-24 border-t border-white/10 bg-hub-dark">
    <div class="max-w-[1520px] mx-auto">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-10 mb-16">
            <div>
                <div class="flex items-center gap-2.5 mb-4">
                    <img src="{{ asset('images/creators-hub/mark-white.png') }}" alt="" aria-hidden="true" class="h-7 w-auto">
                    <span class="font-display font-extrabold text-xl tracking-tight">Creators <span class="text-hub-purple-light">Hub</span></span>
                </div>
                <p class="text-sm text-gray-400 max-w-[240px]">@site('footer.blurb')</p>
            </div>
            <div class="flex flex-col gap-3">
                <span class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">{{ __('Explore') }}</span>
                <a href="{{ $sectionBase }}#about" class="text-sm text-gray-300 hover:text-white transition-colors">{{ __('About') }}</a>
                <a href="{{ route('events.index') }}" class="text-sm text-gray-300 hover:text-white transition-colors">{{ __('Events') }}</a>
                <a href="{{ $sectionBase }}#community" class="text-sm text-gray-300 hover:text-white transition-colors">{{ __('Community') }}</a>
                <a href="{{ $sectionBase }}#partners" class="text-sm text-gray-300 hover:text-white transition-colors">{{ __('Partners') }}</a>
            </div>
            <div class="flex flex-col gap-3">
                <span class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-1">{{ __('Get Involved') }}</span>
                <a href="{{ $sectionBase }}#contact" class="text-sm text-gray-300 hover:text-white transition-colors">{{ __('Contact') }}</a>
                <a href="{{ $sectionBase }}#partners" class="text-sm text-gray-300 hover:text-white transition-colors">{{ __('Become a Partner') }}</a>
            </div>
        </div>
        <div class="flex flex-wrap justify-between items-center gap-4 pt-8 border-t border-white/10 text-xs text-gray-500">
            <span>&copy; {{ now()->year }} Creators Hub. {{ __('All rights reserved.') }}</span>
        </div>
    </div>
</footer>
