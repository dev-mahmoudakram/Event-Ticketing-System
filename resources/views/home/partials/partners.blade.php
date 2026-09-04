{{-- resources/views/home/partials/partners.blade.php --}}
<section id="partners" class="hub-shell scroll-mt-28 py-6">
    <div class="hub-panel-dark hub-pad">
        <div class="flex flex-wrap items-center justify-between gap-6 mb-10">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/50 mb-4" data-reveal>@site('partners.eyebrow')</p>
                <h2 class="font-display text-[clamp(1.75rem,3.6vw,2.75rem)] font-extrabold leading-[1.1] tracking-tight" data-reveal>
                    @site('partners.heading')
                </h2>
            </div>
            <a href="#contact" class="hub-pill hub-pill-light text-sm shrink-0" data-reveal>@site('partners.cta')</a>
        </div>

        <p class="text-white/70 leading-relaxed max-w-2xl" data-reveal data-reveal-delay="1">@site('partners.body')</p>
    </div>
</section>
