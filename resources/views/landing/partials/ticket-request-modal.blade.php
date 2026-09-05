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
                    <h2 class="font-display text-xl font-bold">{{ __('Request Your Ticket') }}</h2>
                    <button type="button" @click="$store.ticketRequest.open = false" class="text-gray-400 hover:text-white text-2xl leading-none transition-colors" aria-label="{{ __('Close') }}">&times;</button>
                </div>

                <p id="ticket-request-feedback" class="text-sm font-bold mb-4 {{ session('ticket_request_success') ? 'text-ccs-teal-light' : 'hidden' }}">
                    @if(session('ticket_request_success'))
                        {{ __('Request received! Your reference number is :number.', ['number' => session('ticket_request_success')]) }}
                    @endif
                </p>

                <form
                    id="ticket-request-form"
                    method="POST"
                    action="{{ route('ticket-requests.store', $event) }}"
                    enctype="multipart/form-data"
                    class="flex flex-col gap-4"
                    data-generic-error="{{ __('Something went wrong. Please try again.') }}"
                    data-success-title="{{ __('Ticket requested') }}"
                    data-success-reference-label="{{ __('Your reference number') }}"
                    data-success-note="{{ __('Keep your reference number. We review every request and email you the next step.') }}"
                    data-success-confirm="{{ __('Done') }}"
                    novalidate
                >
                    @csrf

                    <div>
                        <label for="ticket_type_id" class="block text-sm text-gray-300 mb-1">{{ __('Ticket Type') }}</label>
                        <x-nice-select
                            id="ticket_type_id"
                            name="ticket_type_id"
                            model="$store.ticketRequest.ticketTypeId"
                            :options="$event->ticketTypes->where('is_active', true)->map(fn ($ticketType) => [
                                'value' => (string) $ticketType->id,
                                'label' => (app()->getLocale() === 'ar' ? $ticketType->name_ar : $ticketType->name_en).' — '.$ticketType->price.' '.$ticketType->currency,
                            ])->values()->all()"
                        />
                        <p id="error-ticket_type_id" class="text-red-400 text-sm mt-1 {{ $errors->has('ticket_type_id') ? '' : 'hidden' }}">{{ $errors->first('ticket_type_id') }}</p>
                    </div>

                    <div>
                        <label for="name" class="block text-sm text-gray-300 mb-1">{{ __('Name') }}</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        <p id="error-name" class="text-red-400 text-sm mt-1 {{ $errors->has('name') ? '' : 'hidden' }}">{{ $errors->first('name') }}</p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm text-gray-300 mb-1">{{ __('Email') }}</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        <p id="error-email" class="text-red-400 text-sm mt-1 {{ $errors->has('email') ? '' : 'hidden' }}">{{ $errors->first('email') }}</p>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm text-gray-300 mb-1">{{ __('Phone') }}</label>
                        <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                        <p id="error-phone" class="text-red-400 text-sm mt-1 {{ $errors->has('phone') ? '' : 'hidden' }}">{{ $errors->first('phone') }}</p>
                    </div>

                    {{-- Only offered when this event actually runs codes. --}}
                    @if($event->discountCoupons()->where('is_active', true)->exists())
                        <div>
                            <label for="coupon_code" class="block text-sm text-gray-300 mb-1">{{ __('Discount code') }} <span class="text-gray-500">({{ __('optional') }})</span></label>
                            <input id="coupon_code" type="text" name="coupon_code" value="{{ old('coupon_code') }}" autocapitalize="characters" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2 uppercase">
                            <p id="error-coupon_code" class="text-red-400 text-sm mt-1 {{ $errors->has('coupon_code') ? '' : 'hidden' }}">{{ $errors->first('coupon_code') }}</p>
                        </div>
                    @endif

                    @foreach($event->ticketRequestFields as $field)
                        @php $inputKey = 'field_'.$field->id; @endphp
                        <div>
                            <label class="block text-sm text-gray-300 mb-1">
                                {{ app()->getLocale() === 'ar' ? $field->label_ar : $field->label_en }}
                                @if($field->is_required)<span class="text-red-400">*</span>@endif
                            </label>

                            @if($field->type === \App\Enums\TicketRequestFieldType::Instagram)
                                <input type="text" name="{{ $inputKey }}" value="{{ old($inputKey) }}" placeholder="@username" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                                <p id="error-{{ $inputKey }}" class="text-red-400 text-sm mt-1 {{ $errors->has($inputKey) ? '' : 'hidden' }}">{{ $errors->first($inputKey) }}</p>
                            @elseif($field->type === \App\Enums\TicketRequestFieldType::Portfolio)
                                <div x-data="{ mode: '{{ old($inputKey.'_mode', 'url') }}' }" class="flex flex-col gap-3">
                                    <div class="flex gap-4 text-sm">
                                        <label class="flex items-center gap-2"><input type="radio" name="{{ $inputKey }}_mode" value="url" x-model="mode"> {{ __('URL') }}</label>
                                        <label class="flex items-center gap-2"><input type="radio" name="{{ $inputKey }}_mode" value="pdf" x-model="mode"> {{ __('PDF') }}</label>
                                    </div>
                                    <input x-show="mode === 'url'" type="url" name="{{ $inputKey }}_url" value="{{ old($inputKey.'_url') }}" class="w-full border border-gray-600 bg-gray-900 text-white rounded px-3 py-2">
                                    <div x-show="mode === 'pdf'" x-cloak>
                                        <x-file-dropzone :name="$inputKey.'_file'" accept=".pdf" />
                                    </div>
                                </div>
                                <p id="error-{{ $inputKey }}_mode" class="text-red-400 text-sm mt-1 {{ $errors->has($inputKey.'_mode') ? '' : 'hidden' }}">{{ $errors->first($inputKey.'_mode') }}</p>
                                <p id="error-{{ $inputKey }}_url" class="text-red-400 text-sm mt-1 {{ $errors->has($inputKey.'_url') ? '' : 'hidden' }}">{{ $errors->first($inputKey.'_url') }}</p>
                                <p id="error-{{ $inputKey }}_file" class="text-red-400 text-sm mt-1 {{ $errors->has($inputKey.'_file') ? '' : 'hidden' }}">{{ $errors->first($inputKey.'_file') }}</p>
                            @elseif($field->type === \App\Enums\TicketRequestFieldType::Cv)
                                <x-file-dropzone :name="$inputKey" accept=".pdf,.doc,.docx" />
                                <p id="error-{{ $inputKey }}" class="text-red-400 text-sm mt-1 {{ $errors->has($inputKey) ? '' : 'hidden' }}">{{ $errors->first($inputKey) }}</p>
                            @endif
                        </div>
                    @endforeach

                    <button type="submit" class="px-6 py-3 rounded bg-ccs-red hover:bg-ccs-maroon text-white font-bold transition-opacity disabled:opacity-60">{{ __('Submit Request') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
