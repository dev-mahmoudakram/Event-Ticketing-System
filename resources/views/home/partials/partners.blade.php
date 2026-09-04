{{-- resources/views/home/partials/partners.blade.php --}}
<section id="partners" class="scroll-mt-24 hub-section bg-hub-lavender text-hub-dark text-center">
    <p class="hub-eyebrow text-hub-purple mb-4" data-reveal>@site('partners.eyebrow')</p>
    <h2 class="font-display text-[clamp(2rem,4.5vw,3.25rem)] font-extrabold leading-[1.05] tracking-tight mb-6" data-reveal>
        @site('partners.heading')
    </h2>
    <p class="text-lg text-hub-dark/70 max-w-xl mx-auto mb-10" data-reveal data-reveal-delay="1">
        @site('partners.body')
    </p>
    <a href="#contact" class="inline-flex px-7 py-3.5 rounded-lg border border-hub-purple/30 text-sm font-bold text-hub-purple transition-colors hover:bg-hub-purple/5" data-reveal data-reveal-delay="2">@site('partners.cta')</a>
</section>
