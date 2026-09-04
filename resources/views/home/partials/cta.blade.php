{{-- resources/views/home/partials/cta.blade.php --}}
{{-- The reference's slim "need more information?" band: quieter than a full section, sitting
     between the FAQ and the contact form. --}}
<section class="hub-shell py-6">
    <div class="hub-panel px-8 md:px-12 py-10 flex flex-wrap items-center justify-between gap-8">
        <div class="max-w-xl">
            <h2 class="font-display text-xl md:text-2xl font-extrabold text-hub-purple mb-2" data-reveal>@site('cta.heading')</h2>
            <p class="text-hub-dark/60 leading-relaxed" data-reveal data-reveal-delay="1">@site('cta.body')</p>
        </div>

        <div class="flex flex-wrap gap-3" data-reveal data-reveal-delay="2">
            <a href="{{ route('events.index') }}" class="hub-pill hub-pill-solid text-sm">@site('cta.primary_cta')</a>
            <a href="#contact" class="hub-pill hub-pill-outline text-sm">@site('cta.secondary_cta')</a>
        </div>
    </div>
</section>
