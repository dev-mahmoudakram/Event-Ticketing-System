{{-- resources/views/home/partials/audiences.blade.php --}}
@php
    use App\Support\SiteText;

    // Two sides of the same room, all of it editable under Creators Hub Content.
    $card = fn (string $side, string $slot) => [
        'title' => SiteText::get('audiences', $side.'_'.$slot.'_title'),
        'body' => SiteText::get('audiences', $side.'_'.$slot.'_body'),
    ];

    $audiences = [];
    foreach (['builders', 'brands'] as $side) {
        $audiences[$side] = [
            'tab' => SiteText::get('audiences', $side.'_tab'),
            'lede' => SiteText::get('audiences', $side.'_lede'),
            'cards' => array_map(fn (string $slot) => $card($side, $slot), ['one', 'two', 'three', 'four']),
        ];
    }
    $sides = array_keys($audiences);
@endphp

<section id="audiences" class="hub-shell scroll-mt-28 py-6" x-data="{ active: 'builders' }">
    <div class="hub-panel hub-pad">
        <div class="max-w-2xl mx-auto text-center mb-10">
            <h2 class="font-display text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.1] tracking-tight text-hub-purple mb-5" data-reveal>
                @site('audiences.heading')
            </h2>
            <p class="text-lg text-hub-dark/70 leading-relaxed" data-reveal data-reveal-delay="1">@site('audiences.body')</p>
        </div>

        {{-- The page's one interactive moment, built as a real tablist: arrow keys move
             between sides and the selected state is announced, not just coloured. --}}
        <div class="flex justify-center mb-12">
            <div class="hub-segment" role="tablist" aria-label="{{ __('Choose your side') }}">
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
                <p class="text-center text-sm font-semibold text-hub-purple-light mb-8">{{ $audience['lede'] }}</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    @foreach($audience['cards'] as $card)
                        <div class="rounded-3xl border border-hub-purple/15 p-8 transition-colors duration-300 hover:border-hub-purple/40 hover:bg-hub-lavender/50">
                            <h3 class="font-display text-xl font-bold text-hub-purple mb-3">{{ $card['title'] }}</h3>
                            <p class="text-hub-dark/70 leading-relaxed">{{ $card['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
