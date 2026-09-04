{{-- resources/views/home/partials/stats.blade.php --}}
{{-- Figures come from the admin (Creators Hub Content); a figure without its label is
     dropped, and the band disappears entirely when none are filled in. --}}
@if(!empty($stats))
    @php
        // Written out in full because Tailwind only generates classes it can find as literal
        // strings in the source; an interpolated grid-cols-{n} is never built.
        $columnClass = match (min(count($stats), 3)) {
            1 => 'sm:grid-cols-1',
            2 => 'sm:grid-cols-2',
            default => 'sm:grid-cols-3',
        };
    @endphp

    <section class="hub-shell py-6">
        <div class="grid grid-cols-1 {{ $columnClass }} gap-5">
            @foreach($stats as $stat)
                <div class="hub-panel px-8 py-10 text-center" data-reveal data-reveal-delay="{{ $loop->iteration }}">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-hub-dark/50 mb-3">{{ $stat['label'] }}</p>
                    <p class="font-display text-[clamp(2.25rem,4vw,3.25rem)] font-extrabold leading-none text-hub-purple">{{ $stat['figure'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
