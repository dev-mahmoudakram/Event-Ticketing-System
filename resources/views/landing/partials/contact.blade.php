{{-- resources/views/landing/partials/contact.blade.php --}}
@if($event->isSectionVisible('contact'))
    <section id="contact" class="ccs-section scroll-mt-24 grid grid-cols-1 lg:grid-cols-2 gap-16">
        <div data-reveal>
            <div class="ccs-eyebrow text-ccs-coral">{{ __('Contact') }}</div>
            <h2 class="font-display text-3xl md:text-5xl font-extrabold mb-8">{{ __('Questions before you request?') }}</h2>
        </div>
        @include('landing.partials.contact-form', ['event' => $event])
    </section>
@endif
