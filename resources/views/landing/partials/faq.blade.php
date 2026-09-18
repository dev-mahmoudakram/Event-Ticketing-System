{{-- resources/views/landing/partials/faq.blade.php --}}
@if($event->faqs->isNotEmpty() && $event->isSectionVisible('faq'))
    <section id="faq" class="ccs-section scroll-mt-24 max-w-3xl">
        <div class="ccs-eyebrow text-ccs-gold" data-reveal>{{ __('FAQs') }}</div>
        <h2 class="font-display text-3xl md:text-5xl font-extrabold mb-10" data-reveal>{{ __('Good questions.') }}</h2>
        <div class="flex flex-col">
            @foreach($event->faqs as $faq)
                <div x-data="{ open: false }" class="border-b border-white/10" data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                    <button type="button" class="w-full flex justify-between items-center gap-5 py-6 text-left font-display font-bold text-lg" @click="open = !open">
                        <span>{{ app()->getLocale() === 'ar' ? $faq->question_ar : $faq->question_en }}</span>
                        <span class="text-2xl text-gray-500 transition-transform duration-300 shrink-0" :class="open ? 'rotate-45' : ''">+</span>
                    </button>
                    <div
                        x-ref="panel"
                        class="overflow-hidden transition-[max-height,opacity] duration-300 ease-in-out"
                        :class="open ? 'opacity-100' : 'opacity-0'"
                        :style="open ? 'max-height: ' + $refs.panel.scrollHeight + 'px' : 'max-height: 0px'"
                    >
                        {{-- Sanitized on save (SanitizedRichText cast on Faq) — safe to render
                             unescaped. --}}
                        <div class="ccs-richtext pb-6 text-gray-400 leading-relaxed max-w-xl">{!! app()->getLocale() === 'ar' ? $faq->answer_ar : $faq->answer_en !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
