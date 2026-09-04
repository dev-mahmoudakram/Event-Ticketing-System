{{-- resources/views/home/partials/hero.blade.php --}}
<section id="hero" class="relative min-h-[92vh] flex items-center justify-center overflow-hidden px-5 md:px-16 pt-36 pb-24 text-center">
@php $heroImage = \App\Support\SiteText::image('hero', 'image'); @endphp

    {{-- Ground: an uploaded image when the admin has supplied one, otherwise the purple wash
         this brand uses in place of photography it does not have. --}}
    <div class="absolute inset-0 bg-hub-dark" aria-hidden="true"></div>
    @if($heroImage)
        <img src="{{ $heroImage }}" alt="" aria-hidden="true" class="absolute inset-0 w-full h-full object-cover opacity-40">
        <div class="absolute inset-0 bg-gradient-to-b from-hub-dark/70 via-hub-dark/60 to-hub-dark" aria-hidden="true"></div>
    @else
        <div class="absolute inset-0 opacity-90" style="background: radial-gradient(80% 60% at 50% 0%, rgba(60,52,137,0.85), transparent 70%), radial-gradient(50% 50% at 15% 90%, rgba(127,119,221,0.22), transparent 70%);" aria-hidden="true"></div>
    @endif

    {{-- The mark, enlarged and dimmed into the ground so it reads as texture, not a logo. --}}
    <img
        src="{{ asset('images/creators-hub/mark-secondary.png') }}"
        alt=""
        aria-hidden="true"
        class="absolute -right-24 -bottom-24 w-[520px] max-w-none opacity-[0.07] rotate-12 pointer-events-none select-none"
    >

    <div class="relative max-w-3xl mx-auto" data-hero-copy>
        <h1 class="font-display text-[clamp(2.25rem,5.6vw,4.5rem)] font-extrabold leading-[1.06] tracking-tight mb-7">
            @site('hero.headline')
        </h1>

        <p class="text-lg md:text-xl text-hub-lavender/75 leading-relaxed max-w-2xl mx-auto mb-10">
            @site('hero.body')
        </p>

        <div class="flex flex-wrap justify-center gap-4">
            <a href="{{ route('events.index') }}" class="px-8 py-4 rounded-lg hub-btn-primary text-base font-bold transition-transform duration-200 hover:scale-[1.03]">
                @site('hero.primary_cta')
            </a>
            <a href="#about" class="px-8 py-4 rounded-lg border border-white/25 text-base font-bold transition-colors hover:bg-white/5">
                @site('hero.secondary_cta')
            </a>
        </div>
    </div>
</section>
