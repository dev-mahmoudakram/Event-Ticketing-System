{{-- resources/views/landing/partials/contact-form.blade.php --}}
{{-- Shared by the landing page's Contact section and the event's Contact page; the page passes
     $returnTo so the visitor lands back on it after sending. --}}
<form method="POST" action="{{ route('contact.store', $event) }}" class="flex flex-col gap-4" data-reveal data-reveal-delay="1">
    @csrf
    @isset($returnTo)<input type="hidden" name="return_to" value="{{ $returnTo }}">@endisset
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
