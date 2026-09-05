{{-- resources/views/workshops/picker.blade.php --}}
@extends('layouts.app')

@php
    $eventName = app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en;
    $allowance = $ticket->workshopSlotAllowance();
@endphp

@section('title', $eventName.' — '.__('Your workshops'))

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section
        class="ccs-section scroll-mt-24 pt-32 pb-24"
        x-data="{
            chosen: {{ Illuminate\Support\Js::from($chosen) }},
            allowance: {{ $allowance === null ? 'null' : $allowance }},
            toggle(id) {
                this.chosen = this.chosen.includes(id) ? this.chosen.filter(c => c !== id) : [...this.chosen, id];
            },
            get full() { return this.allowance !== null && this.chosen.length >= this.allowance; },
            locked(id) { return this.full && ! this.chosen.includes(id); },
        }"
    >
        <div class="flex flex-wrap items-start justify-between gap-6 mb-10">
            <div>
                <div class="ccs-eyebrow text-ccs-gold">{{ __('Workshops') }}</div>
                <h1 class="font-display text-3xl md:text-4xl font-extrabold mb-2">{{ __('Pick your workshops.') }}</h1>
                <p class="text-gray-400">
                    {{ $ticket->name }} &middot; <bdi>{{ $ticket->ticket_number }}</bdi>
                </p>
            </div>

            <form method="POST" action="{{ route('workshops.forget', $event) }}">
                @csrf
                <button type="submit" class="text-sm font-semibold text-gray-400 hover:text-white transition-colors">{{ __('Not you? Start again') }}</button>
            </form>
        </div>

        @if(session('workshop_booking_saved'))
            <p class="mb-8 rounded-lg border border-ccs-teal/40 bg-ccs-teal/10 px-5 py-4 text-sm font-bold text-ccs-teal-light">
                {{ __('Your workshops are saved.') }}
            </p>
        @endif

        @error('workshops')
            <p class="mb-8 rounded-lg border border-red-400/40 bg-red-400/10 px-5 py-4 text-sm font-bold text-red-200">{{ $message }}</p>
        @enderror

        <p class="text-sm text-gray-300 mb-8">
            @if($allowance === null)
                {{ __('Your ticket includes every workshop you have time for.') }}
            @else
                {{-- The count changes as boxes are ticked, so every wording this ticket can
                     produce is translated up front and picked by the live number. Arabic has
                     more plural forms than English, so the choice cannot be made in JavaScript. --}}
                @php
                    $sentences = collect(range(0, $allowance))->mapWithKeys(fn (int $left) => [$left => trans_choice(
                        '{0} No places left of :allowance.|{1} :count place left of :allowance.|[2,*] :count places left of :allowance.',
                        $left,
                        ['count' => $left, 'allowance' => $allowance],
                    )]);
                @endphp
                <span x-text="{{ Illuminate\Support\Js::from($sentences) }}[allowance - chosen.length]"></span>
            @endif
        </p>

        @if($workshops->isEmpty())
            <p class="text-gray-400">{{ __('No workshops have been announced yet.') }}</p>
        @else
            <form method="POST" action="{{ route('workshops.picker.store', $event) }}">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-10">
                    @foreach($workshops as $workshop)
                        @php
                            $remaining = $workshop->remainingCapacity();
                            $wasChosen = in_array($workshop->id, $chosen, true);
                            $soldOut = $remaining === 0 && ! $wasChosen;
                        @endphp

                        <label
                            class="block h-full rounded-2xl border p-6 transition-colors cursor-pointer"
                            :class="chosen.includes({{ $workshop->id }})
                                ? 'border-ccs-teal bg-ccs-teal/10'
                                : (locked({{ $workshop->id }}) || {{ $soldOut ? 'true' : 'false' }} ? 'border-white/10 bg-white/5 opacity-50 cursor-not-allowed' : 'border-white/10 bg-white/5 hover:border-white/30')"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    name="workshops[]"
                                    value="{{ $workshop->id }}"
                                    class="mt-1.5 w-4 h-4 rounded border-gray-500 bg-gray-900 text-ccs-teal"
                                    @checked($wasChosen)
                                    @disabled($soldOut)
                                    :disabled="locked({{ $workshop->id }}) || {{ $soldOut ? 'true' : 'false' }}"
                                    @change="toggle({{ $workshop->id }})"
                                >
                                <div class="flex-1">
                                    <h2 class="font-display font-bold mb-1">{{ $workshop->name() }}</h2>
                                    @if($workshop->description())
                                        <p class="text-sm text-gray-400 leading-relaxed mb-3">{{ $workshop->description() }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500">
                                        @if($soldOut)
                                            {{ __('Full') }}
                                        @elseif($remaining === null)
                                            {{ __('Open to everyone') }}
                                        @else
                                            {{ trans_choice('{1} :count place left|[2,*] :count places left', $remaining, ['count' => $remaining]) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                <button type="submit" class="px-7 py-4 rounded-lg bg-ccs-teal hover:bg-ccs-teal-light hover:text-ccs-black text-white font-bold transition-colors">
                    {{ __('Save my workshops') }}
                </button>
            </form>
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
