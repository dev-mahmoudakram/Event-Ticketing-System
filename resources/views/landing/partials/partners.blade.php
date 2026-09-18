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
        @endphp

        <div data-sponsor-grid>
            @foreach($event->sponsorTiers as $sponsorTier)
                @continue($sponsorsByTier->get($sponsorTier->id, collect())->isEmpty())
                <div class="mb-10">
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-4">
                        {{ app()->getLocale() === 'ar' ? $sponsorTier->name_ar : $sponsorTier->name_en }}
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach($sponsorsByTier->get($sponsorTier->id) as $sponsor)
                            @php
                                $sponsorName = app()->getLocale() === 'ar' ? $sponsor->name_ar : $sponsor->name_en;
                                $logo = $sponsor->logoUrl();
                            @endphp

                            <x-dynamic-component
                                :component="$sponsor->website_url ? 'sponsor-link' : 'sponsor-tile'"
                                :url="$sponsor->website_url"
                            >
                                @if($logo)
                                    <img
                                        src="{{ $logo }}"
                                        alt="{{ $sponsorName }}"
                                        class="max-h-12 w-auto object-contain transition duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                @else
                                    {{-- No logo uploaded yet: the name stands in so the grid stays even. --}}
                                    <span class="font-display text-sm font-bold text-gray-300 text-center px-2">{{ $sponsorName }}</span>
                                @endif
                            </x-dynamic-component>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if($untieredSponsors->isNotEmpty())
                <div class="mb-10">
                    <div class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-4">{{ __('Partners') }}</div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        @foreach($untieredSponsors as $sponsor)
                            @php
                                $sponsorName = app()->getLocale() === 'ar' ? $sponsor->name_ar : $sponsor->name_en;
                                $logo = $sponsor->logoUrl();
                            @endphp

                            <x-dynamic-component
                                :component="$sponsor->website_url ? 'sponsor-link' : 'sponsor-tile'"
                                :url="$sponsor->website_url"
                            >
                                @if($logo)
                                    <img
                                        src="{{ $logo }}"
                                        alt="{{ $sponsorName }}"
                                        class="max-h-12 w-auto object-contain transition duration-300 group-hover:scale-105"
                                        loading="lazy"
                                    >
                                @else
                                    <span class="font-display text-sm font-bold text-gray-300 text-center px-2">{{ $sponsorName }}</span>
                                @endif
                            </x-dynamic-component>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @endif
    </section>
@endif
