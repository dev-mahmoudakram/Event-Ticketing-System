{{-- resources/views/home/partials/contact.blade.php --}}
<section id="contact" class="scroll-mt-24 hub-section grid grid-cols-1 lg:grid-cols-2 gap-16">
    <div data-reveal>
        <p class="hub-eyebrow text-hub-purple-light mb-4">@site('contact.eyebrow')</p>
        <h2 class="font-display text-3xl md:text-5xl font-extrabold leading-[1.05] tracking-tight mb-6">@site('contact.heading')</h2>
        <p class="text-lg text-gray-300 max-w-md leading-relaxed">@site('contact.body')</p>
    </div>
    <form method="POST" action="{{ route('contact.store.general') }}" class="flex flex-col gap-4" data-reveal data-reveal-delay="1">
        @csrf
        @if(session('contact_success'))
            <p class="text-sm font-bold text-hub-purple-light">{{ __("Thanks — we'll be in touch soon.") }}</p>
        @endif
        <div>
            <label for="hub-contact-name" class="sr-only">{{ __('Name') }}</label>
            <input id="hub-contact-name" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('Name') }}" class="w-full px-4 py-4 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 transition-colors focus:border-hub-purple-light focus:outline-none">
            @error('name') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="hub-contact-email" class="sr-only">{{ __('Email') }}</label>
            <input id="hub-contact-email" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('Email') }}" class="w-full px-4 py-4 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 transition-colors focus:border-hub-purple-light focus:outline-none">
            @error('email') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="hub-contact-message" class="sr-only">{{ __('Message') }}</label>
            <textarea id="hub-contact-message" name="message" rows="4" placeholder="{{ __('Message') }}" class="w-full px-4 py-4 bg-white/5 border border-white/10 rounded-lg text-white placeholder-gray-500 transition-colors focus:border-hub-purple-light focus:outline-none">{{ old('message') }}</textarea>
            @error('message') <p class="text-red-400 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="px-6 py-4 rounded-lg hub-btn-primary font-bold transition-transform duration-200 hover:scale-[1.02]">@site('contact.submit')</button>
    </form>
</section>
