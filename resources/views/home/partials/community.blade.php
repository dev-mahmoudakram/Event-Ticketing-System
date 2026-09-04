{{-- resources/views/home/partials/community.blade.php --}}
<section id="community" class="hub-shell scroll-mt-24 py-6">
    {{-- The reference's full-bleed "long haul" band: one dark panel with centred copy. --}}
    <div class="hub-panel-dark hub-pad relative overflow-hidden text-center">
        <img
            src="{{ asset('images/creators-hub/mark-white.png') }}"
            alt=""
            aria-hidden="true"
            class="absolute -left-16 -bottom-20 w-[320px] max-w-none opacity-[0.06] -rotate-12 pointer-events-none select-none"
        >

        <div class="relative max-w-3xl mx-auto">
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/50 mb-5" data-reveal>@site('community.eyebrow')</p>
            <h2 class="font-display text-[clamp(1.9rem,4.4vw,3.4rem)] font-extrabold leading-[1.1] tracking-tight mb-6" data-reveal>
                @site('community.heading')
            </h2>
            <p class="text-lg text-white/75 leading-relaxed mb-4" data-reveal data-reveal-delay="1">@site('community.body')</p>
            <p class="font-display text-lg md:text-xl font-bold text-hub-lavender mb-10" data-reveal data-reveal-delay="1">@site('community.statement')</p>
            <a href="#contact" class="hub-pill hub-pill-light" data-reveal data-reveal-delay="2">@site('community.cta')</a>
        </div>
    </div>
</section>
