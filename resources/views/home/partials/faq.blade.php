{{-- resources/views/home/partials/faq.blade.php --}}
@if($faqs->isNotEmpty())
    <section id="faq" class="hub-shell scroll-mt-24 py-6">
        <div class="hub-panel hub-pad">
            <h2 class="font-display text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.1] tracking-tight text-hub-purple mb-10" data-reveal>
                @site('faq.heading')
            </h2>

            <div class="flex flex-col gap-4 max-w-4xl" x-data="{ open: null }">
                @foreach($faqs as $faq)
                    <div class="rounded-3xl border border-hub-purple/15 overflow-hidden transition-colors" :class="open === {{ $faq->id }} ? 'bg-hub-lavender/40' : ''">
                        <h3>
                            <button
                                type="button"
                                @click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}"
                                :aria-expanded="open === {{ $faq->id }} ? 'true' : 'false'"
                                aria-controls="faq-answer-{{ $faq->id }}"
                                class="w-full flex items-center justify-between gap-6 px-7 py-6 text-start"
                            >
                                <span class="font-display text-base md:text-lg font-bold text-hub-purple">{{ $faq->question() }}</span>

                                {{-- A circled stroke that loses its vertical bar when open. --}}
                                <span class="relative shrink-0 grid place-items-center w-8 h-8 rounded-full border border-hub-purple/30 text-hub-purple" aria-hidden="true">
                                    <span class="absolute w-3.5 h-px bg-current"></span>
                                    <span
                                        class="absolute w-px h-3.5 bg-current transition-transform duration-300"
                                        :class="open === {{ $faq->id }} ? 'scale-y-0' : 'scale-y-100'"
                                    ></span>
                                </span>
                            </button>
                        </h3>

                        {{-- Height animates via grid-template-rows, so the answer expands smoothly
                             without pulling in Alpine's collapse plugin. --}}
                        <div
                            id="faq-answer-{{ $faq->id }}"
                            class="grid transition-[grid-template-rows] duration-300 ease-out motion-reduce:transition-none"
                            :class="open === {{ $faq->id }} ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'"
                        >
                            <div class="overflow-hidden">
                                <p class="px-7 pb-6 text-hub-dark/70 leading-relaxed max-w-3xl border-t border-hub-purple/10 pt-5">{{ $faq->answer() }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
