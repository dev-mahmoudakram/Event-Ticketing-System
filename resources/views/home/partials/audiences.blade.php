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
@endphp

<section id="audiences" class="scroll-mt-24 hub-section" x-data="{ active: 'builders' }">
    <div class="max-w-2xl mb-10">
        <h2 class="font-display text-[clamp(2rem,4.5vw,3.25rem)] font-extrabold leading-[1.05] tracking-tight mb-5">
            @site('audiences.heading')
        </h2>
        <p class="text-lg text-gray-300 leading-relaxed">
            @site('audiences.body')
        </p>
    </div>

    {{-- The switch is the page's one interactive moment, so it is a real control: proper
         tablist semantics, keyboard support, and a visible state change. --}}
    <div class="flex flex-wrap gap-2 mb-10" role="tablist" aria-label="{{ __('Choose your side') }}">
        @foreach($audiences as $key => $audience)
            <button
                type="button"
                role="tab"
                :aria-selected="active === '{{ $key }}' ? 'true' : 'false'"
                :tabindex="active === '{{ $key }}' ? 0 : -1"
                aria-controls="audience-panel-{{ $key }}"
                id="audience-tab-{{ $key }}"
                @click="active = '{{ $key }}'"
                @keydown.right.prevent="active = '{{ $loop->last ? array_key_first($audiences) : array_keys($audiences)[$loop->index + 1] }}'; $refs['tab' + active]?.focus()"
                @keydown.left.prevent="active = '{{ $loop->first ? array_key_last($audiences) : array_keys($audiences)[$loop->index - 1] }}'; $refs['tab' + active]?.focus()"
                x-ref="tab{{ $key }}"
                class="px-6 py-3.5 rounded-lg border text-sm font-bold transition-colors duration-300"
                :class="active === '{{ $key }}'
                    ? 'bg-hub-purple border-hub-purple text-white'
                    : 'border-white/15 text-gray-400 hover:text-white hover:border-white/30'"
            >
                {{ $audience['tab'] }}
            </button>
        @endforeach
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
            <p class="text-sm text-hub-purple-light font-semibold mb-6">{{ $audience['lede'] }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-px bg-white/10 rounded-2xl overflow-hidden border border-white/10">
                @foreach($audience['cards'] as $card)
                    <div class="bg-hub-dark p-7 flex flex-col gap-3 transition-colors duration-300 hover:bg-hub-purple/20">
                        <h3 class="font-display text-lg font-bold leading-snug">{{ $card['title'] }}</h3>
                        <p class="text-sm text-gray-400 leading-relaxed">{{ $card['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</section>
