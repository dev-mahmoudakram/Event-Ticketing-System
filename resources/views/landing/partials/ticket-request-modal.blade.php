{{-- resources/views/landing/partials/ticket-request-modal.blade.php --}}
@if($event->ticketTypes->where('is_active', true)->isNotEmpty())
<div
    x-data
    x-show="$store.ticketRequest.open"
    x-cloak
    x-init="if (@js($errors->any())) { $store.ticketRequest.open = true; $store.ticketRequest.ticketTypeId = '{{ old('ticket_type_id') }}'; }"
    @keydown.escape.window="$store.ticketRequest.open = false"
    class="fixed inset-0 z-100"
>
    <div
        class="fixed inset-0 bg-black/70"
        @click="$store.ticketRequest.open = false"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <div class="fixed inset-0 overflow-y-auto p-4" @click.self="$store.ticketRequest.open = false">
        <div class="flex min-h-full items-center justify-center" @click.self="$store.ticketRequest.open = false">
            <div
                class="relative bg-ccs-black border border-white/10 rounded-2xl max-w-xl w-full p-6 md:p-8 my-8"
                x-transition:enter="transition-opacity ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            >
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-display text-2xl font-extrabold">{{ __('Request Your Ticket') }}</h2>
                    <button type="button" @click="$store.ticketRequest.open = false" class="text-gray-400 hover:text-white text-2xl leading-none transition-colors" aria-label="{{ __('Close') }}">&times;</button>
                </div>

                <p id="ticket-request-feedback" class="text-sm font-bold mb-4 {{ session('ticket_request_success') ? 'text-ccs-gold' : 'hidden' }}">
                    @if(session('ticket_request_success'))
                        {{ __('Request received! Your reference number is :number.', ['number' => session('ticket_request_success')]) }}
                    @endif
                </p>

                <form
                    id="ticket-request-form"
                    method="POST"
                    action="{{ route('ticket-requests.store', $event) }}"
                    enctype="multipart/form-data"
                    class="ccs-form"
                    data-generic-error="{{ __('Something went wrong. Please try again.') }}"
                    data-success-title="{{ __('Ticket requested') }}"
                    data-success-reference-label="{{ __('Your reference number') }}"
                    data-success-note="{{ __('Keep your reference number. We review every request and email you the next step.') }}"
                    data-success-confirm="{{ __('Done') }}"
                    novalidate
                >
                    @csrf

                    <div>
                        <label for="ticket_type_id" class="ccs-form-label">{{ __('Ticket Type') }} <span class="ccs-form-required" aria-hidden="true">*</span></label>
                        <x-nice-select
                            id="ticket_type_id"
                            name="ticket_type_id"
                            model="$store.ticketRequest.ticketTypeId"
                            :options="$event->ticketTypes->where('is_active', true)->map(fn ($ticketType) => [
                                'value' => (string) $ticketType->id,
                                'label' => (app()->getLocale() === 'ar' ? $ticketType->name_ar : $ticketType->name_en).' — '.$ticketType->price.' '.$ticketType->currency,
                            ])->values()->all()"
                        />
                        <p id="error-ticket_type_id" class="ccs-form-error {{ $errors->has('ticket_type_id') ? '' : 'hidden' }}">{{ $errors->first('ticket_type_id') }}</p>
                    </div>

                    <div>
                        <label for="name" class="ccs-form-label">{{ __('Name') }} <span class="ccs-form-required" aria-hidden="true">*</span></label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('e.g. Ahmed Hassan') }}" autocomplete="name" aria-required="true" class="ccs-form-input">
                        <p id="error-name" class="ccs-form-error {{ $errors->has('name') ? '' : 'hidden' }}">{{ $errors->first('name') }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="ccs-form-label">{{ __('Email') }} <span class="ccs-form-required" aria-hidden="true">*</span></label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('name@example.com') }}" autocomplete="email" dir="ltr" aria-required="true" class="ccs-form-input">
                            <p id="error-email" class="ccs-form-error {{ $errors->has('email') ? '' : 'hidden' }}">{{ $errors->first('email') }}</p>
                        </div>

                        <div>
                            <label for="phone" class="ccs-form-label">{{ __('Phone') }} <span class="ccs-form-required" aria-hidden="true">*</span></label>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" aria-required="true" class="ccs-form-input">
                            <p id="error-phone" class="ccs-form-error {{ $errors->has('phone') ? '' : 'hidden' }}">{{ $errors->first('phone') }}</p>
                        </div>
                    </div>

                    @if($event->influencerCategories->isNotEmpty())
                        <div x-data="{ influencerCategoryId: '{{ old('influencer_category_id') }}' }">
                            <label for="influencer_category_id" class="ccs-form-label">
                                {{ __('Influencer Category') }}
                                @if($event->require_influencer_category)
                                    <span class="ccs-form-required" aria-hidden="true">*</span>
                                @else
                                    <span class="ccs-form-optional">({{ __('optional') }})</span>
                                @endif
                            </label>
                            <x-nice-select
                                id="influencer_category_id"
                                name="influencer_category_id"
                                model="influencerCategoryId"
                                :placeholder="__('Choose a category')"
                                :options="$event->influencerCategories->map(fn ($category) => [
                                    'value' => (string) $category->id,
                                    'label' => app()->getLocale() === 'ar' ? $category->name_ar : $category->name_en,
                                ])->push(['value' => 'other', 'label' => __('Other')])->values()->all()"
                            />
                            <p id="error-influencer_category_id" class="ccs-form-error {{ $errors->has('influencer_category_id') ? '' : 'hidden' }}">{{ $errors->first('influencer_category_id') }}</p>

                            <div x-show="influencerCategoryId === 'other'" x-cloak class="mt-3">
                                <input type="text" name="influencer_category_other" value="{{ old('influencer_category_other') }}" placeholder="{{ __('Tell us your category') }}" aria-label="{{ __('Tell us your category') }}" class="ccs-form-input">
                                <p id="error-influencer_category_other" class="ccs-form-error {{ $errors->has('influencer_category_other') ? '' : 'hidden' }}">{{ $errors->first('influencer_category_other') }}</p>
                            </div>
                        </div>
                    @endif

                    @foreach($event->ticketRequestFields as $field)
                        @php
                            $inputKey = 'field_'.$field->id;
                            $fieldLabel = app()->getLocale() === 'ar' ? $field->label_ar : $field->label_en;
                        @endphp

                        @if($field->type === \App\Enums\TicketRequestFieldType::Instagram)
                            <x-social-field platform="instagram" :name="$inputKey" :followers-name="$inputKey.'_followers'" :label="$fieldLabel" :required="$field->is_required" />
                        @elseif($field->type === \App\Enums\TicketRequestFieldType::SocialLink)
                            <x-social-field :platform="$field->platform" :name="$inputKey" :followers-name="$inputKey.'_followers'" :label="$fieldLabel" :required="$field->is_required" />
                        @else
                            <div>
                                <label class="ccs-form-label">
                                    {{ $fieldLabel }}
                                    @if($field->is_required)<span class="ccs-form-required" aria-hidden="true">*</span>@endif
                                </label>

                                @if($field->type === \App\Enums\TicketRequestFieldType::Portfolio)
                                    <div x-data="{ mode: '{{ old($inputKey.'_mode', 'url') }}' }" class="flex flex-col gap-3">
                                        <div class="flex gap-4 text-sm text-white/80">
                                            <label class="flex items-center gap-2"><input type="radio" name="{{ $inputKey }}_mode" value="url" x-model="mode" class="accent-ccs-coral"> {{ __('URL') }}</label>
                                            <label class="flex items-center gap-2"><input type="radio" name="{{ $inputKey }}_mode" value="pdf" x-model="mode" class="accent-ccs-coral"> {{ __('PDF') }}</label>
                                        </div>
                                        <input x-show="mode === 'url'" type="url" name="{{ $inputKey }}_url" value="{{ old($inputKey.'_url') }}" placeholder="https://" dir="ltr" aria-label="{{ $fieldLabel }}" class="ccs-form-input">
                                        <div x-show="mode === 'pdf'" x-cloak>
                                            <x-file-dropzone :name="$inputKey.'_file'" accept=".pdf" />
                                        </div>
                                    </div>
                                    <p id="error-{{ $inputKey }}_mode" class="ccs-form-error {{ $errors->has($inputKey.'_mode') ? '' : 'hidden' }}">{{ $errors->first($inputKey.'_mode') }}</p>
                                    <p id="error-{{ $inputKey }}_url" class="ccs-form-error {{ $errors->has($inputKey.'_url') ? '' : 'hidden' }}">{{ $errors->first($inputKey.'_url') }}</p>
                                    <p id="error-{{ $inputKey }}_file" class="ccs-form-error {{ $errors->has($inputKey.'_file') ? '' : 'hidden' }}">{{ $errors->first($inputKey.'_file') }}</p>
                                @elseif($field->type === \App\Enums\TicketRequestFieldType::Cv)
                                    <x-file-dropzone :name="$inputKey" accept=".pdf,.doc,.docx" />
                                    <p id="error-{{ $inputKey }}" class="ccs-form-error {{ $errors->has($inputKey) ? '' : 'hidden' }}">{{ $errors->first($inputKey) }}</p>
                                @endif
                            </div>
                        @endif
                    @endforeach

                    {{-- Only offered when this event actually runs codes. --}}
                    @if($event->discountCoupons()->where('is_active', true)->exists())
                        <div>
                            <label for="coupon_code" class="ccs-form-label">{{ __('Discount code') }} <span class="ccs-form-optional">({{ __('optional') }})</span></label>
                            <input id="coupon_code" type="text" name="coupon_code" value="{{ old('coupon_code') }}" autocapitalize="characters" placeholder="{{ __('Enter your code') }}" dir="ltr" class="ccs-form-input uppercase">
                            <p id="error-coupon_code" class="ccs-form-error {{ $errors->has('coupon_code') ? '' : 'hidden' }}">{{ $errors->first('coupon_code') }}</p>
                        </div>
                    @endif

                    <div>
                        <label class="flex items-start gap-3 text-sm text-white/80 leading-relaxed">
                            <input type="checkbox" name="accept_terms" value="1" class="mt-1 w-4 h-4 shrink-0 accent-ccs-coral" @checked(old('accept_terms')) aria-required="true">
                            {{-- Safe unescaped: the sentence comes from our translation files and the links are built with e(). --}}
                            <span>{!! __('I have read and agree to the :terms and the :refund.', [
                                'terms' => '<a href="'.e(route('event-pages.show', [$event, 'terms'])).'" target="_blank" rel="noopener" class="text-ccs-coral underline">'.e(__('Terms & Conditions')).'</a>',
                                'refund' => '<a href="'.e(route('event-pages.show', [$event, 'refund-policy'])).'" target="_blank" rel="noopener" class="text-ccs-coral underline">'.e(__('Refund & Cancellation Policy')).'</a>',
                            ]) !!}</span>
                        </label>
                        <p id="error-accept_terms" class="ccs-form-error {{ $errors->has('accept_terms') ? '' : 'hidden' }}">{{ $errors->first('accept_terms') }}</p>
                    </div>

                    <button type="submit" class="ccs-form-submit">{{ __('Submit Request') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
