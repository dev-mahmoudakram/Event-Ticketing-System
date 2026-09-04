{{-- resources/views/home/partials/why-egypt.blade.php --}}
{{-- Entirely admin-supplied. No claims about the region are hardcoded here — the section
     simply does not render until someone writes them. --}}
@php
    $points = $whyEgypt['points'];
    $heading = $whyEgypt['heading'];
@endphp

@if($heading && $points !== [])
    <section id="why-egypt" class="hub-shell scroll-mt-28 py-6">
        <div class="hub-panel hub-pad">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20">
                <div>
                    <h2 class="font-display text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.1] tracking-tight text-hub-purple mb-6">
                        {{ $heading }}
                    </h2>
                    @if($whyEgypt['body'])
                        <p class="text-lg text-hub-dark/70 leading-relaxed mb-8">{{ $whyEgypt['body'] }}</p>
                    @endif
                    @if($whyEgypt['image'])
                        <div class="hub-media-well rounded-3xl aspect-[4/3]">
                            <img src="{{ $whyEgypt['image'] }}" alt="" loading="lazy">
                        </div>
                    @endif
                </div>

                <ul class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5 self-center">
                    @foreach($points as $point)
                        <li class="flex items-start gap-3">
                            <span class="mt-1 text-hub-purple shrink-0" aria-hidden="true">&check;</span>
                            <span class="text-base md:text-lg text-hub-dark/75 leading-relaxed">{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>
@endif
