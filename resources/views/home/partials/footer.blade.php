{{-- resources/views/home/partials/footer.blade.php --}}
@php $sectionBase = ($onHomePage ?? true) ? '' : route('home'); @endphp

<footer class="hub-shell pt-2">
    <div class="hub-panel-dark hub-pad">
        <div class="grid grid-cols-1 md:grid-cols-[1.4fr_1fr_1fr] gap-12 mb-14">
            <div>
                <div class="flex items-center gap-2.5 mb-5">
                    <img src="{{ asset('images/creators-hub/mark-white.png') }}" alt="" aria-hidden="true" class="h-8 w-auto">
                    <span class="font-display font-extrabold text-xl tracking-tight">Creators Hub</span>
                </div>
                <p class="text-sm text-white/60 leading-relaxed max-w-xs">@site('footer.blurb')</p>
            </div>

            <div class="flex flex-col gap-3">
                <span class="text-xs font-bold uppercase tracking-[0.18em] text-white/40 mb-1">{{ __('Explore') }}</span>
                <a href="{{ $sectionBase }}#about" class="text-sm text-white/75 hover:text-white transition-colors">{{ __('About') }}</a>
                <a href="{{ $sectionBase }}#audiences" class="text-sm text-white/75 hover:text-white transition-colors">{{ __('Who it is for') }}</a>
                <a href="{{ route('events.index') }}" class="text-sm text-white/75 hover:text-white transition-colors">{{ __('Events') }}</a>
                <a href="{{ $sectionBase }}#community" class="text-sm text-white/75 hover:text-white transition-colors">{{ __('Community') }}</a>
            </div>

            <div class="flex flex-col gap-3">
                <span class="text-xs font-bold uppercase tracking-[0.18em] text-white/40 mb-1">{{ __('Get Involved') }}</span>
                <a href="{{ $sectionBase }}#partners" class="text-sm text-white/75 hover:text-white transition-colors">{{ __('Become a Partner') }}</a>
                <a href="{{ $sectionBase }}#faq" class="text-sm text-white/75 hover:text-white transition-colors">{{ __('FAQs') }}</a>
                <a href="{{ $sectionBase }}#contact" class="text-sm text-white/75 hover:text-white transition-colors">{{ __('Contact') }}</a>
            </div>
        </div>

        <div class="flex flex-wrap justify-between items-center gap-4 pt-8 border-t border-white/15 text-xs text-white/50">
            <span>&copy; {{ now()->year }} Creators Hub. {{ __('All rights reserved.') }}</span>
        </div>
    </div>
</footer>
