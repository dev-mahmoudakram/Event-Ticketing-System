{{-- resources/views/home/partials/about.blade.php --}}
@php $aboutImage = \App\Support\SiteText::image('about', 'image'); @endphp

<section id="about" class="hub-shell scroll-mt-28 py-6">
    <div class="hub-panel hub-pad">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-start">
            <div data-reveal>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-hub-purple/60 mb-5">@site('about.eyebrow')</p>
                <h2 class="font-display text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.1] tracking-tight text-hub-purple mb-6">
                    @site('about.heading')
                </h2>
                <p class="font-display text-xl md:text-2xl font-bold leading-snug text-hub-dark">
                    @site('about.statement')<br>@site('about.statement_second_line')
                </p>
            </div>

            <div data-reveal data-reveal-delay="1" class="flex flex-col gap-8">
                <p class="text-lg leading-relaxed text-hub-dark/70">@site('about.body')</p>

                @if($aboutImage)
                    <div class="hub-media-well rounded-3xl aspect-[16/10]">
                        <img src="{{ $aboutImage }}" alt="" loading="lazy">
                    </div>
                @endif

                <ul class="flex flex-wrap items-center gap-x-4 gap-y-3 pt-6 border-t border-hub-purple/15 text-sm font-bold text-hub-purple">
                    @foreach ([
                        __('Designers'), __('Architects'), __('Contractors'), __('Developers'),
                        __('Suppliers'), __('Brands'), __('Industry Professionals'),
                    ] as $index => $role)
                        @if($index > 0)<li aria-hidden="true" class="text-hub-purple/30">/</li>@endif
                        <li>{{ $role }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>
