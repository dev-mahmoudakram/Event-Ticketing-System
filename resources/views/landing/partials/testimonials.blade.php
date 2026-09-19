{{-- resources/views/landing/partials/testimonials.blade.php --}}
@if($event->testimonials->isNotEmpty() && $event->isSectionVisible('testimonials'))
    <section id="testimonials" class="ccs-section scroll-mt-24">
        <div class="ccs-eyebrow text-ccs-gold" data-reveal>{{ __('Testimonials') }}</div>
        <h2 class="font-display text-3xl md:text-5xl font-extrabold mb-12" data-reveal>{{ __('What past attendees say.') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-7">
            @foreach($event->testimonials as $testimonial)
                @php
                    $testimonialName = app()->getLocale() === 'ar' ? $testimonial->name_ar : $testimonial->name_en;
                    $photo = $testimonial->photoUrl();
                @endphp
                <div data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}">
                    <div class="h-full bg-white/5 border border-white/10 rounded-2xl p-8 flex flex-col gap-6 transition-transform duration-300 hover:-translate-y-1">
                        {{-- Sanitized on save (App\Casts\SanitizedRichText on Testimonial::quote_ar/quote_en)
                             against the strict 'cms' Purifier profile — safe to render unescaped here
                             because nothing reaches this column without having passed through it first. --}}
                        <div class="ccs-richtext text-lg leading-relaxed font-medium">&ldquo;{!! app()->getLocale() === 'ar' ? $testimonial->quote_ar : $testimonial->quote_en !!}&rdquo;</div>
                        <div class="flex items-center gap-3">
                            @if($photo)
                                <img src="{{ $photo }}" alt="{{ $testimonialName }}" class="w-12 h-12 rounded-full object-cover shrink-0" loading="lazy">
                            @else
                                <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center font-display font-bold text-sm shrink-0" aria-hidden="true">
                                    {{ mb_substr($testimonialName, 0, 1) }}
                                </div>
                            @endif
                            <div>
                                <div class="font-bold text-sm">{{ $testimonialName }}</div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ app()->getLocale() === 'ar' ? $testimonial->title_ar : $testimonial->title_en }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
