{{-- resources/views/landing/partials/contact.blade.php --}}
@if($event->isSectionVisible('contact'))
    <section id="contact" class="ccs-section scroll-mt-24 grid grid-cols-1 lg:grid-cols-2 gap-16">
        <div data-reveal>
            <div class="ccs-eyebrow text-ccs-coral">{{ __('Contact') }}</div>
            <h2 class="font-display text-3xl md:text-5xl font-extrabold mb-8">{{ __('Questions before you request?') }}</h2>
        </div>
        <form method="POST" action="{{ route('contact.store', $event) }}" class="flex flex-col gap-4" data-reveal data-reveal-delay="1">
            @csrf
            @if(session('contact_success'))
                <p class="text-sm font-bold text-ccs-gold">{{ __("Thanks — we'll be in touch soon.") }}</p>
            @endif
            <div>
                <label for="contact-name" class="sr-only">{{ __('Name') }}</label>
                <input id="contact-name" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Name') }}" class="w-full px-4 py-4 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 transition-colors focus:border-ccs-gold focus:outline-none">
                @error('name') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="contact-email" class="sr-only">{{ __('Email') }}</label>
                <input id="contact-email" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email') }}" class="w-full px-4 py-4 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 transition-colors focus:border-ccs-gold focus:outline-none">
                @error('email') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="contact-message" class="sr-only">{{ __('Message') }}</label>
                <textarea id="contact-message" name="message" rows="4" placeholder="{{ __('Message') }}" class="w-full px-4 py-4 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 transition-colors focus:border-ccs-gold focus:outline-none">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="px-6 py-4 rounded-lg ccs-btn-red font-bold transition-transform duration-200 hover:scale-[1.02]">{{ __('Send Message') }}</button>
        </form>
    </section>
@endif
