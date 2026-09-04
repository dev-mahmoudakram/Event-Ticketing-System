{{-- resources/views/home/partials/cta.blade.php --}}
<section class="relative overflow-hidden hub-blueprint-grid" style="background: linear-gradient(160deg, var(--color-hub-purple), var(--color-hub-dark));">
    <div class="hub-section text-center flex flex-col items-center">
        <h2 class="font-display text-[clamp(2.25rem,5vw,4rem)] font-extrabold leading-[1.02] tracking-tight max-w-3xl mb-6" data-reveal>
            @site('cta.heading')
        </h2>
        <p class="text-lg text-hub-lavender/85 max-w-xl mb-10" data-reveal data-reveal-delay="1">
            @site('cta.body')
        </p>
        <div class="flex flex-wrap justify-center gap-4" data-reveal data-reveal-delay="2">
            <a href="{{ route('events.index') }}" class="px-8 py-4 rounded-lg bg-white text-hub-dark text-base font-bold transition-transform duration-200 hover:scale-[1.03]">@site('cta.primary_cta')</a>
            <a href="#contact" class="px-8 py-4 rounded-lg border border-white/30 text-base font-bold transition-colors hover:bg-white/10">@site('cta.secondary_cta')</a>
        </div>
    </div>
</section>
