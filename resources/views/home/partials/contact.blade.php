{{-- resources/views/home/partials/contact.blade.php --}}
<section id="contact" class="hub-shell scroll-mt-28 py-6">
    <div class="hub-panel hub-pad">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20">
            <div data-reveal>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-hub-purple/60 mb-5">@site('contact.eyebrow')</p>
                <h2 class="font-display text-[clamp(1.9rem,4vw,3rem)] font-extrabold leading-[1.1] tracking-tight text-hub-purple mb-6">@site('contact.heading')</h2>
                <p class="text-lg text-hub-dark/70 leading-relaxed max-w-md">@site('contact.body')</p>
            </div>

            <form method="POST" action="{{ route('contact.store.general') }}" class="flex flex-col gap-4" data-reveal data-reveal-delay="1">
                @csrf

                @if(session('contact_success'))
                    <p class="text-sm font-bold text-hub-purple">{{ __("Thanks — we'll be in touch soon.") }}</p>
                @endif

                <div>
                    <label for="hub-contact-name" class="sr-only">{{ __('Name') }}</label>
                    <input id="hub-contact-name" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Name') }}" class="w-full px-5 py-4 rounded-2xl bg-hub-lavender/60 border border-hub-purple/15 text-hub-dark placeholder-hub-dark/40 transition-colors focus:border-hub-purple focus:outline-none">
                    @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="hub-contact-email" class="sr-only">{{ __('Email') }}</label>
                    <input id="hub-contact-email" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email') }}" class="w-full px-5 py-4 rounded-2xl bg-hub-lavender/60 border border-hub-purple/15 text-hub-dark placeholder-hub-dark/40 transition-colors focus:border-hub-purple focus:outline-none">
                    @error('email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="hub-contact-message" class="sr-only">{{ __('Message') }}</label>
                    <textarea id="hub-contact-message" name="message" rows="4" placeholder="{{ __('Message') }}" class="w-full px-5 py-4 rounded-2xl bg-hub-lavender/60 border border-hub-purple/15 text-hub-dark placeholder-hub-dark/40 transition-colors focus:border-hub-purple focus:outline-none">{{ old('message') }}</textarea>
                    @error('message') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="hub-pill hub-pill-solid self-start">@site('contact.submit')</button>
            </form>
        </div>
    </div>
</section>
