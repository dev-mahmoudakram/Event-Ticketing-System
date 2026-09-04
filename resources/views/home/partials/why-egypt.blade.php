{{-- resources/views/home/partials/why-egypt.blade.php --}}
{{-- Entirely admin-supplied (Creators Hub Content). No claims about the region are hardcoded
     here — the section simply does not render until someone writes them. --}}
@php
    $points = $whyEgypt['points'];
    $heading = $whyEgypt['heading'];
@endphp

@if($heading && $points !== [])
    <section id="why-egypt" class="scroll-mt-24 hub-section bg-hub-lavender text-hub-dark">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-14 lg:gap-20 items-start">
            <div>
                <h2 class="font-display text-[clamp(2rem,4.5vw,3.25rem)] font-extrabold leading-[1.05] tracking-tight mb-6">
                    {{ $heading }}
                </h2>
                @if($whyEgypt['body'])
                    <p class="text-lg text-hub-dark/70 leading-relaxed max-w-xl mb-8">{{ $whyEgypt['body'] }}</p>
                @endif
                @if($whyEgypt['image'])
                    <img src="{{ $whyEgypt['image'] }}" alt="" class="w-full rounded-2xl object-cover aspect-[4/3]" loading="lazy">
                @endif
            </div>

            <ul class="flex flex-col">
                @foreach($points as $point)
                    <li class="flex items-start gap-4 py-5 border-b border-hub-dark/15 first:pt-0">
                        <span class="mt-2 w-2 h-2 shrink-0 bg-hub-purple" aria-hidden="true"></span>
                        <span class="text-base md:text-lg text-hub-dark/80 leading-relaxed">{{ $point }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
