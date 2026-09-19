{{-- resources/views/landing/partials/partners.blade.php --}}
@if($event->isSectionVisible('partners'))
    <section id="partners" class="ccs-section scroll-mt-24">
        <div class="flex flex-wrap items-end justify-between gap-6 mb-12">
            <div>
                <div class="ccs-eyebrow text-ccs-teal-light" data-reveal>{{ __('Sponsors & Partners') }}</div>
                <h2 class="font-display text-3xl md:text-5xl font-extrabold" data-reveal>{{ __('Backed by the industry.') }}</h2>
            </div>
            <a href="{{ route('sponsor-requests.create', $event) }}" class="shrink-0 px-6 py-3 rounded-lg ccs-btn-red text-sm font-bold transition-transform duration-200 hover:scale-[1.03]" data-reveal>
                {{ __('Become a Sponsor') }}
            </a>
        </div>

        @if($event->sponsors->isNotEmpty())
        @php
            $sponsorsByTier = $event->sponsors->groupBy('sponsor_tier_id');
            $untieredSponsors = $sponsorsByTier->get(null, collect());

            // Rows are sized by rank so the top tier reads as the most prominent, the way a
            // printed sponsor wall would: bigger logo, taller card, the further down the list.
            // A fixed width (not just height) on the card matters here specifically because the
            // track's own width drives the marquee's -50% loop point: if a card's width depended
            // on its logo image (object-contain, w-auto), the track would measure differently
            // before/after each image finishes loading, and the animation's loop point would
            // shift mid-flight — throwing a reversed row's position outside its own track width.
            $tileSizes = [
                ['card' => 'h-32 w-56 px-8 py-6', 'logo' => 'max-h-16', 'gap' => 'gap-8'],
                ['card' => 'h-24 w-44 px-6 py-5', 'logo' => 'max-h-12', 'gap' => 'gap-6'],
                ['card' => 'h-20 w-36 px-5 py-4', 'logo' => 'max-h-10', 'gap' => 'gap-5'],
            ];
            $defaultSize = ['card' => 'h-16 w-28 px-4 py-3', 'logo' => 'max-h-8', 'gap' => 'gap-4'];

            $rows = $event->sponsorTiers
                ->map(fn ($tier) => [
                    'label' => app()->getLocale() === 'ar' ? $tier->name_ar : $tier->name_en,
                    'sponsors' => $sponsorsByTier->get($tier->id, collect()),
                ])
                ->filter(fn ($row) => $row['sponsors']->isNotEmpty())
                ->values();

            if ($untieredSponsors->isNotEmpty()) {
                $rows->push(['label' => __('Partners'), 'sponsors' => $untieredSponsors]);
            }
        @endphp

        <div class="flex flex-col gap-10">
            @foreach($rows as $index => $row)
                @php $size = $tileSizes[$index] ?? $defaultSize; @endphp
                @php
                    // translateX(-50%) only loops seamlessly if the first half of the track is
                    // already at least a full viewport wide — otherwise the second copy visibly
                    // "catches up" through empty space before the loop point. Repeating the
                    // sponsor set enough times guarantees that regardless of how few sponsors
                    // this tier has; the copy count is halved below to build the two matching
                    // halves the animation slides between.
                    $copiesPerHalf = (int) max(1, ceil(8 / max(1, $row['sponsors']->count())));
                @endphp
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-4" data-reveal data-reveal-delay="{{ min($index + 1, 5) }}">{{ $row['label'] }}</div>
                    {{-- Deliberately outside the data-reveal wrapper above: nesting a percentage-based
                         translateX animation inside an ancestor that is itself mid-transform (the
                         reveal's translateY entrance) causes browsers to occasionally miscompute the
                         composited transform, throwing the marquee far outside its own track width
                         on its very first frames. The row fades in on its own via .ccs-sponsor-row's
                         opacity transition below instead, which never touches `transform`. --}}
                    <div class="ccs-sponsor-row">
                        <div class="ccs-sponsor-track {{ $index % 2 === 1 ? 'ccs-sponsor-track--reverse' : '' }} {{ $size['gap'] }}" style="--ccs-marquee-duration: {{ max(20, $row['sponsors']->count() * $copiesPerHalf * 6) }}s;">
                            @for($copy = 0; $copy < $copiesPerHalf * 2; $copy++)
                                @foreach($row['sponsors'] as $sponsor)
                                    @php
                                        $sponsorName = app()->getLocale() === 'ar' ? $sponsor->name_ar : $sponsor->name_en;
                                        $logo = $sponsor->logoUrl();
                                    @endphp

                                    <x-dynamic-component
                                        :component="$sponsor->website_url ? 'sponsor-link' : 'sponsor-tile'"
                                        :url="$sponsor->website_url"
                                        :class="'shrink-0 '.$size['card']"
                                        :aria-hidden="$copy > 0 ? 'true' : null"
                                        :tabindex="$copy > 0 ? '-1' : null"
                                    >
                                        @if($logo)
                                            {{-- Not lazy-loaded: this row is a continuously-scrolling marquee, so every
                                                 copy of every logo is visible (or about to be) from the first frame.
                                                 Loading them lazily meant most had no measured width yet when the
                                                 track's own width was first read, so the -50% loop point kept moving
                                                 as images resolved late, throwing the animation off. --}}
                                            <img
                                                src="{{ $logo }}"
                                                alt="{{ $sponsorName }}"
                                                class="{{ $size['logo'] }} w-auto object-contain transition duration-300 group-hover:scale-105"
                                            >
                                        @else
                                            {{-- No logo uploaded yet: the name stands in so the row stays populated. --}}
                                            <span class="font-display text-sm font-bold text-gray-300 text-center px-2 whitespace-nowrap">{{ $sponsorName }}</span>
                                        @endif
                                    </x-dynamic-component>
                                @endforeach
                            @endfor
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </section>
@endif
