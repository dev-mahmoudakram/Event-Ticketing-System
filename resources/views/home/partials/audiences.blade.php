{{-- resources/views/home/partials/audiences.blade.php --}}
@php
    // Tabs and their cards are rows now, so the section describes as many audiences as the
    // admin has created rather than the two it was once written around.
    $tabs = $audienceTabs->filter->isComplete()->values();
@endphp

@if($tabs->isNotEmpty())
    {{-- Sits directly on the page ground rather than inside a white panel, so the stacked
         cards read as a deck the way they do in the reference. --}}
    <section id="audiences" class="hub-shell scroll-mt-24 py-16 md:py-24" x-data="{ active: {{ $tabs->first()->id }} }">
        <div class="max-w-2xl mx-auto text-center mb-10">
            <h2 class="font-display text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.1] tracking-tight text-hub-purple mb-5" data-reveal>
                @site('audiences.heading')
            </h2>
            <p class="text-lg text-hub-dark/70 leading-relaxed" data-reveal data-reveal-delay="1">@site('audiences.body')</p>
        </div>

        {{-- The page's one interactive moment, built as a real tablist: arrow keys move
             between sides and the selected state is announced, not just coloured.
             The switch stays put while the deck scrolls past it, so the reader can always see
             which side they are reading and swap without scrolling back up. --}}
        @if($tabs->count() > 1)
            @php $ids = $tabs->pluck('id')->all(); @endphp
            <div class="sticky top-[5.5rem] z-30 flex justify-center mb-12">
                {{-- Scrolls rather than wraps: with enough tabs the switch would otherwise
                     break onto a second line and stop reading as one control. --}}
                <div class="hub-segment hub-segment-scroll shadow-lg shadow-hub-purple/10" role="tablist" aria-label="{{ __('Choose your side') }}">
                    @foreach($tabs as $tab)
                        <button
                            type="button"
                            role="tab"
                            id="audience-tab-{{ $tab->id }}"
                            aria-controls="audience-panel-{{ $tab->id }}"
                            :aria-selected="active === {{ $tab->id }} ? 'true' : 'false'"
                            :tabindex="active === {{ $tab->id }} ? 0 : -1"
                            x-ref="tab{{ $tab->id }}"
                            @click="active = {{ $tab->id }}"
                            @keydown.right.prevent="active = {{ $loop->last ? $ids[0] : $ids[$loop->index + 1] }}; $refs['tab' + active].focus()"
                            @keydown.left.prevent="active = {{ $loop->first ? $ids[count($ids) - 1] : $ids[$loop->index - 1] }}; $refs['tab' + active].focus()"
                            class="hub-segment-option"
                        >
                            {{ $tab->label() }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @foreach($tabs as $tab)
            <div
                id="audience-panel-{{ $tab->id }}"
                role="tabpanel"
                aria-labelledby="audience-tab-{{ $tab->id }}"
                @if($tabs->count() > 1)
                    x-show="active === {{ $tab->id }}"
                    x-cloak
                    x-transition:enter="transition ease-out duration-400"
                    x-transition:enter-start="opacity-0 translate-y-3"
                    x-transition:enter-end="opacity-100 translate-y-0"
                @endif
            >
                @if(trim($tab->lede()) !== '')
                    <p class="text-center text-sm font-semibold text-hub-purple-light mb-10">{{ $tab->lede() }}</p>
                @endif

                {{-- Cards stick under the navbar as the section scrolls, so each one comes to
                     rest on the pile above it. Each sits a little lower than the last, which
                     is what leaves the edge of the previous card visible. --}}
                <div class="flex flex-col gap-8">
                    @foreach($tab->cards as $index => $card)
                        <div class="lg:sticky" style="top: calc(10rem + {{ $index * 1.75 }}rem)">
                            <div class="hub-stack-card rounded-[2rem] overflow-hidden">
                                <div class="grid grid-cols-1 lg:grid-cols-2 items-center gap-8 lg:gap-12 p-8 md:p-12">
                                    <div>
                                        <h3 class="font-display text-[clamp(1.6rem,3vw,2.6rem)] font-extrabold leading-tight tracking-tight text-hub-purple mb-5">
                                            {{ $card->title() }}
                                        </h3>
                                        <p class="text-lg text-hub-dark/70 leading-relaxed mb-8 max-w-md">{{ $card->body() }}</p>
                                        @if(trim($tab->cta()) !== '')
                                            <a href="#contact" class="hub-pill hub-pill-outline text-sm">{{ $tab->cta() }}</a>
                                        @endif
                                    </div>

                                    <div class="hub-media-well rounded-3xl aspect-[4/3] lg:aspect-[5/4]">
                                        @if($card->imageUrl())
                                            <img src="{{ $card->imageUrl() }}" alt="" loading="lazy">
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
@endif
