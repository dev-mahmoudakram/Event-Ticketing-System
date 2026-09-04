{{-- resources/views/home/partials/faq.blade.php --}}
@if($faqs->isNotEmpty())
    <section id="faq" class="scroll-mt-24 hub-section">
        <h2 class="font-display text-[clamp(2rem,4.5vw,3.25rem)] font-extrabold leading-[1.05] tracking-tight mb-10 max-w-2xl">
            @site('faq.heading')
        </h2>

        <div class="max-w-3xl border-t border-white/10" x-data="{ open: null }">
            @foreach($faqs as $faq)
                <div class="border-b border-white/10">
                    <h3>
                        <button
                            type="button"
                            @click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}"
                            :aria-expanded="open === {{ $faq->id }} ? 'true' : 'false'"
                            aria-controls="faq-answer-{{ $faq->id }}"
                            class="w-full flex items-center justify-between gap-6 py-6 text-start transition-colors hover:text-hub-purple-light"
                        >
                            <span class="font-display text-lg md:text-xl font-bold">{{ $faq->question() }}</span>
                            {{-- A single stroke that becomes a minus when the answer is open. --}}
                            <span class="relative shrink-0 w-4 h-4" aria-hidden="true">
                                <span class="absolute inset-x-0 top-1/2 h-px bg-current"></span>
                                <span
                                    class="absolute inset-y-0 left-1/2 w-px bg-current transition-transform duration-300"
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
                            <p class="pb-6 text-gray-400 leading-relaxed max-w-2xl">{{ $faq->answer() }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
