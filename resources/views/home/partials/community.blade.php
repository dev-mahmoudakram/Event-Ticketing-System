{{-- resources/views/home/partials/community.blade.php --}}
<section id="community" class="scroll-mt-24 hub-section grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
    <div data-reveal>
        <p class="hub-eyebrow text-hub-purple-light mb-6">@site('community.eyebrow')</p>
        <h2 class="font-display text-[clamp(2rem,4.5vw,3.25rem)] font-extrabold leading-[1.05] tracking-tight mb-6">
            @site('community.heading')
        </h2>
        <p class="text-lg text-gray-300 leading-relaxed max-w-xl mb-4">
            @site('community.body')
        </p>
        <p class="font-display text-xl font-bold text-hub-purple-light mb-10">
            @site('community.statement')
        </p>
        <a href="#contact" class="inline-flex px-7 py-3.5 rounded-lg hub-btn-primary text-sm font-bold transition-transform duration-200 hover:scale-[1.03]">@site('community.cta')</a>
    </div>

    <div class="relative aspect-square" aria-hidden="true" data-reveal data-reveal-delay="1">
        <svg viewBox="0 0 400 400" class="w-full h-full">
            <g stroke="var(--color-hub-purple-light)" stroke-width="1" opacity="0.4">
                <line x1="200" y1="200" x2="80" y2="110" />
                <line x1="200" y1="200" x2="320" y2="90" />
                <line x1="200" y1="200" x2="330" y2="230" />
                <line x1="200" y1="200" x2="250" y2="330" />
                <line x1="200" y1="200" x2="90" y2="300" />
                <line x1="200" y1="200" x2="60" y2="210" />
            </g>
            <circle cx="200" cy="200" r="30" fill="var(--color-hub-purple)" />
            <text x="200" y="204" text-anchor="middle" fill="white" font-family="Manrope, sans-serif" font-weight="800" font-size="11">HUB</text>

            @foreach ([
                ['x' => 80, 'y' => 110, 'label' => __('Designers')],
                ['x' => 320, 'y' => 90, 'label' => __('Architects')],
                ['x' => 330, 'y' => 230, 'label' => __('Contractors')],
                ['x' => 250, 'y' => 330, 'label' => __('Brands')],
                ['x' => 90, 'y' => 300, 'label' => __('Suppliers')],
                ['x' => 60, 'y' => 210, 'label' => __('Developers')],
            ] as $node)
                <circle cx="{{ $node['x'] }}" cy="{{ $node['y'] }}" r="5" fill="var(--color-hub-lavender)" />
                <text x="{{ $node['x'] }}" y="{{ $node['y'] - 14 }}" text-anchor="middle" fill="#d1d5db" font-family="Inter, sans-serif" font-size="11" letter-spacing="0.5">{{ $node['label'] }}</text>
            @endforeach
        </svg>
    </div>
</section>
