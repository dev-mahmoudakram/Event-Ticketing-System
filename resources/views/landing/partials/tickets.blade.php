{{-- resources/views/landing/partials/tickets.blade.php --}}
@if($event->ticketTypes->isNotEmpty() && $event->isSectionVisible('tickets'))
    <section id="tickets" class="ccs-section scroll-mt-24" x-data>
        <div class="max-w-[680px] mb-16">
            <div class="ccs-eyebrow text-ccs-gold" data-reveal>{{ __('Ticket Request') }}</div>
            <h2 class="font-display text-[clamp(32px,3.6vw,45px)] font-extrabold mb-[18px]" data-reveal>{{ __("There's no checkout. There's a review.") }}</h2>
            <p class="text-[17px] text-gray-400 leading-[1.6]" data-reveal>{{ __('CCS is invitation-considerate, not first-come. Submit a request; our team reviews it before any payment happens.') }}</p>
        </div>

        <div class="relative grid grid-cols-6 gap-2 mb-[90px]" data-reveal>
            <div class="absolute top-[23px] left-[8%] right-[8%] h-px bg-white/10 z-0"></div>
            @foreach([
                ['label' => __('Request Ticket'), 'state' => 'active'],
                ['label' => __('Admin Review'), 'state' => 'pending'],
                ['label' => __('Approval'), 'state' => 'pending'],
                ['label' => __('Payment Link'), 'state' => 'pending'],
                ['label' => __('Ticket Generated'), 'state' => 'pending'],
                ['label' => __('QR Code Issued'), 'state' => 'done'],
            ] as $index => $step)
                <div class="relative z-10 flex flex-col items-center text-center gap-3.5">
                    <div class="w-[46px] h-[46px] shrink-0 rounded-full flex items-center justify-center font-extrabold {{ $step['state'] === 'active' ? 'bg-[#241014] border border-white/10 text-white' : ($step['state'] === 'done' ? 'bg-ccs-teal-light text-ccs-black' : 'bg-ccs-maroon border border-white/10 text-white') }}">
                        {{ $index + 1 }}
                    </div>
                    <span class="text-[13px] font-semibold text-gray-400">{{ $step['label'] }}</span>
                </div>
            @endforeach
        </div>

        @php
            $activeTicketTypes = $event->ticketTypes->where('is_active', true);
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start">
            @foreach($activeTicketTypes as $ticketType)
                @php
                    $slotCount = $ticketType->workshop_slot_count;
                    $slotLabel = is_null($slotCount)
                        ? __('Unlimited workshops')
                        : ($slotCount === 0 ? __('No workshops included') : trans_choice(':count workshop included|:count workshops included', $slotCount, ['count' => $slotCount]));
                    $description = app()->getLocale() === 'ar' ? $ticketType->description_ar : $ticketType->description_en;
                    // The admin's manual pick, not an automatic "highest price wins" guess —
                    // a ticket table should not editorialize about which tier is worth more.
                    $isPopular = $ticketType->is_popular;
                @endphp
                <div data-reveal data-reveal-delay="{{ min($loop->iteration, 5) }}" class="{{ $isPopular ? 'md:-mt-4' : '' }}">
                    <div class="h-full rounded-2xl py-9 px-[30px] flex flex-col relative transition-transform duration-300 hover:-translate-y-1 {{ $isPopular ? 'bg-gradient-to-b from-ccs-coral/[0.12] to-white/[0.04] border-2 border-ccs-coral shadow-[0_0_40px_-12px_rgba(255,126,113,0.5)] md:scale-105' : 'bg-white/[0.03] border border-white/10' }}">
                        @if($isPopular)
                            <div class="absolute top-0 inset-x-0 -translate-y-1/2 flex justify-center">
                                <span class="text-[11px] font-extrabold uppercase tracking-[0.12em] bg-ccs-coral text-ccs-red px-4 py-1.5 rounded-full whitespace-nowrap">{{ $ticketType->popularLabel() }}</span>
                            </div>
                        @endif

                        <div class="text-[13px] font-bold uppercase tracking-[0.1em] text-gray-500 mb-[14px]">{{ app()->getLocale() === 'ar' ? $ticketType->name_ar : $ticketType->name_en }}</div>

                        <div class="flex items-baseline gap-1.5 mb-3 flex-wrap">
                            @if($ticketType->isOnSale())
                                <span class="text-lg font-bold text-gray-500 line-through">{{ $ticketType->original_price }}</span>
                            @endif
                            <span class="text-[38px] font-extrabold leading-none">{{ $ticketType->price }}</span>
                            <span class="text-sm font-bold text-gray-500 uppercase tracking-wide">{{ $ticketType->currency }}</span>
                        </div>

                        @if($description)
                            {{-- Sanitized on save (SanitizedRichText cast on TicketType) —
                                 safe to render unescaped. --}}
                            <div class="ccs-richtext text-sm text-gray-400 leading-relaxed mb-6">{!! $description !!}</div>
                        @endif

                        <button type="button" @click="$store.ticketRequest.show('{{ $ticketType->id }}')" class="text-center p-[14px] rounded-lg text-sm font-bold transition-transform duration-200 hover:scale-[1.03] mb-7 {{ $isPopular ? 'bg-ccs-coral text-ccs-red' : 'ccs-btn-red' }}">
                            {{ __('Request This Ticket') }}
                        </button>

                        <div class="text-xs font-bold uppercase tracking-[0.08em] text-ccs-gold mb-4">{{ $slotLabel }}</div>

                        @if($ticketType->features->isNotEmpty())
                            <div class="flex flex-col gap-3.5">
                                @foreach($ticketType->features as $feature)
                                    <div class="flex gap-2.5 items-start text-sm text-gray-300">
                                        <x-bi-check-circle-fill class="shrink-0 mt-0.5 text-[15px] text-ccs-teal-light" />
                                        <span>{{ app()->getLocale() === 'ar' ? $feature->text_ar : $feature->text_en }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
