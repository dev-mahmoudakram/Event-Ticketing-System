{{-- resources/views/home/partials/stats.blade.php --}}
{{-- Figures come from the admin (Creators Hub Content); the band stays out of the page
     entirely until at least one figure and its label are filled in. --}}
@if(!empty($stats))
    <section class="border-y border-white/10 bg-hub-dark">
        @php
            // Written out in full because Tailwind only generates classes it can find as
            // literal strings in the source; an interpolated grid-cols-{n} is never built.
            $columnClass = match (min(count($stats), 3)) {
                1 => 'sm:grid-cols-1',
                2 => 'sm:grid-cols-2',
                default => 'sm:grid-cols-3',
            };
        @endphp
        <div class="max-w-[1520px] mx-auto px-5 md:px-16 py-14 grid grid-cols-1 {{ $columnClass }} gap-10 sm:gap-6">
            @foreach($stats as $stat)
                <div class="text-center" data-reveal data-reveal-delay="{{ $loop->iteration }}">
                    <p class="font-display text-[clamp(2.5rem,5vw,4rem)] font-extrabold leading-none text-hub-purple-light">{{ $stat['figure'] }}</p>
                    <p class="mt-3 text-sm text-gray-400">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
