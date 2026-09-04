{{-- resources/views/home/partials/about.blade.php --}}
<section id="about" class="scroll-mt-24 hub-section bg-hub-lavender text-hub-dark grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
    <div data-reveal>
        <p class="hub-eyebrow text-hub-purple mb-6">@site('about.eyebrow')</p>
        <h2 class="font-display text-[clamp(2rem,4.5vw,3.25rem)] font-extrabold leading-[1.05] tracking-tight mb-6">
            @site('about.heading')
        </h2>
        <p class="font-display text-2xl md:text-3xl font-bold leading-tight text-hub-purple">
            @site('about.statement')<br>@site('about.statement_second_line')
        </p>
    </div>

    <div data-reveal data-reveal-delay="1" class="flex flex-col gap-8">
        <p class="text-lg leading-relaxed text-hub-dark/80 max-w-xl">
            @site('about.body')
        </p>
        @php $aboutImage = \App\Support\SiteText::image('about', 'image'); @endphp
        @if($aboutImage)
            <img src="{{ $aboutImage }}" alt="" class="w-full rounded-2xl object-cover aspect-[16/10]" loading="lazy">
        @endif

        <ul class="flex flex-wrap items-center gap-x-4 gap-y-3 pt-4 border-t border-hub-purple/20 text-sm font-bold uppercase tracking-wide text-hub-purple">
            @foreach ([
                __('Designers'), __('Architects'), __('Contractors'), __('Developers'),
                __('Suppliers'), __('Brands'), __('Industry Professionals'),
            ] as $index => $role)
                @if($index > 0)<li aria-hidden="true" class="text-hub-purple/30">/</li>@endif
                <li>{{ $role }}</li>
            @endforeach
        </ul>
    </div>
</section>
