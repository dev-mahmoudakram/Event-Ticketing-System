{{-- resources/views/home/partials/audiences.blade.php --}}
@php
    use App\Support\SiteText;

    // Two sides of the same room, all of it editable under Creators Hub Content.
    $card = fn (string $side, string $slot) => [
        'title' => SiteText::get('audiences', $side.'_'.$slot.'_title'),
        'body' => SiteText::get('audiences', $side.'_'.$slot.'_body'),
        'image' => SiteText::image('audiences', $side.'_'.$slot.'_image'),
    ];

    $audiences = [];
    foreach (['builders', 'brands'] as $side) {
        $audiences[$side] = [
            'tab' => SiteText::get('audiences', $side.'_tab'),
            'lede' => SiteText::get('audiences', $side.'_lede'),
            'cta' => SiteText::get('audiences', $side.'_cta'),
            'cards' => array_map(fn (string $slot) => $card($side, $slot), ['one', 'two', 'three', 'four']),
        ];
    }
    $sides = array_keys($audiences);
@endphp

{{-- Sits directly on the page ground rather than inside a white panel, so the stacked cards
     read as a deck the way they do in the reference. --}}
<section id="audiences" class="hub-shell scroll-mt-24 py-16 md:py-24" x-data="{ active: 'builders' }">
    <div class="max-w-2xl mx-auto text-center mb-10">
        <h2 class="font-display text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.1] tracking-tight text-hub-purple mb-5" data-reveal>
            @site('audiences.heading')
        </h2>
        <p class="text-lg text-hub-dark/70 leading-relaxed" data-reveal data-reveal-delay="1">@site('audiences.body')</p>
    </div>

    {{-- The page's one interactive moment, built as a real tablist: arrow keys move between
         sides and the selected state is announced, not just coloured. --}}
    {{-- The switch stays put while the deck scrolls past it, so the reader can always see
         which side they are reading and swap without scrolling back up. --}}
    <div class="sticky top-[5.5rem] z-30 flex justify-center mb-12">
        <div class="hub-segment shadow-lg shadow-hub-purple/10" role="tablist" aria-label="{{ __('Choose your side') }}">
            @foreach($audiences as $key => $audience)
                <button
                    type="button"
                    role="tab"
                    id="audience-tab-{{ $key }}"
                    aria-controls="audience-panel-{{ $key }}"
                    :aria-selected="active === '{{ $key }}' ? 'true' : 'false'"
                    :tabindex="active === '{{ $key }}' ? 0 : -1"
                    x-ref="tab{{ $key }}"
                    @click="active = '{{ $key }}'"
                    @keydown.right.prevent="active = '{{ $loop->last ? $sides[0] : $sides[$loop->index + 1] }}'; $refs['tab' + active].focus()"
                    @keydown.left.prevent="active = '{{ $loop->first ? $sides[count($sides) - 1] : $sides[$loop->index - 1] }}'; $refs['tab' + active].focus()"
                    class="hub-segment-option"
                >
                    {{ $audience['tab'] }}
                </button>
            @endforeach
        </div>
    </div>

    @foreach($audiences as $key => $audience)
        <div
            id="audience-panel-{{ $key }}"
            role="tabpanel"
            aria-labelledby="audience-tab-{{ $key }}"
            x-show="active === '{{ $key }}'"
            x-cloak
            x-transition:enter="transition ease-out duration-400"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
        >
            <p class="text-center text-sm font-semibold text-hub-purple-light mb-10">{{ $audience['lede'] }}</p>

            {{-- Cards stick under the navbar as the section scrolls, so each one comes to rest
                 on the pile above it. Each sits a little lower than the last, which is what
                 leaves the edge of the previous card visible. --}}
            <div class="flex flex-col gap-8">
                @foreach($audience['cards'] as $index => $card)
                    <div class="lg:sticky" style="top: calc(10rem + {{ $index * 1.75 }}rem)">
                        <div class="hub-stack-card rounded-[2rem] overflow-hidden">
                            <div class="grid grid-cols-1 lg:grid-cols-2 items-center gap-8 lg:gap-12 p-8 md:p-12">
                                <div>
                                    <h3 class="font-display text-[clamp(1.6rem,3vw,2.6rem)] font-extrabold leading-tight tracking-tight text-hub-purple mb-5">
                                        {{ $card['title'] }}
                                    </h3>
                                    <p class="text-lg text-hub-dark/70 leading-relaxed mb-8 max-w-md">{{ $card['body'] }}</p>
                                    <a href="#contact" class="hub-pill hub-pill-outline text-sm">{{ $audience['cta'] }}</a>
                                </div>

                                <div class="hub-media-well rounded-3xl aspect-[4/3] lg:aspect-[5/4]">
                                    @if($card['image'])
                                        <img src="{{ $card['image'] }}" alt="" loading="lazy">
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
