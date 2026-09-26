{{-- One session or workshop on the public schedule, plus the <template> its pop-up is filled
     from. Clicking the card opens the pop-up (resources/js/schedule-popup.js); the booking link
     goes straight to booking, since the pop-up ignores clicks on links and buttons. --}}
@props(['entry', 'event'])

@php
    $workshop = $entry->workshop();
    $seatsLeft = $workshop?->remainingCapacity();
    $isFull = $workshop?->isFull() ?? false;
    $speakers = $entry->speakers();
    $hasDetails = ! $entry->isBreak() || filled($entry->description());
@endphp

@if($entry->isBreak())
    <div id="{{ $entry->anchor() }}" data-break-entry="{{ $entry->anchor() }}"
         class="ccs-schedule-break {{ $hasDetails ? 'cursor-pointer' : '' }}"
         @if($hasDetails) data-schedule-open="{{ $entry->anchor() }}" role="button" tabindex="0" @endif>
        <span dir="ltr" class="tabular-nums">{{ $entry->start() }} – {{ $entry->end() }}</span>
        <span aria-hidden="true">·</span>
        <span>{{ $entry->title() }}</span>
    </div>
@else
    <article id="{{ $entry->anchor() }}" data-schedule-open="{{ $entry->anchor() }}" role="button" tabindex="0"
             aria-label="{{ $entry->title() }}" class="ccs-schedule-card">
        <div class="flex items-center gap-2 text-sm text-gray-300">
            <x-bi-clock class="w-4 h-4 text-ccs-gold" aria-hidden="true" />
            @if($entry->isScheduled())
                <span dir="ltr" class="tabular-nums font-semibold">{{ $entry->start() }} – {{ $entry->end() }}</span>
            @else
                <span class="text-gray-400">{{ __('Time to be announced') }}</span>
            @endif
        </div>

        <span class="ccs-schedule-pill">{{ $entry->typeLabel() }}</span>

        <h3 class="font-display text-lg md:text-xl font-bold leading-snug line-clamp-3">{{ $entry->title() }}</h3>

        @if($entry->locationName())
            <div class="flex items-center gap-2 text-sm text-gray-400">
                <x-bi-geo-alt class="w-4 h-4 text-ccs-coral" aria-hidden="true" />
                <span>{{ $entry->locationName() }}</span>
            </div>
        @endif

        @if($speakers->isNotEmpty())
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
                @foreach($speakers->take(4) as $speaker)
                    <li class="flex items-center gap-2.5 min-w-0">
                        <x-speaker-avatar :speaker="$speaker" />
                        <span class="text-sm font-semibold truncate">{{ app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en }}</span>
                    </li>
                @endforeach
            </ul>
            @if($speakers->count() > 4)
                <p class="text-xs font-semibold text-gray-400">{{ __('+:count more', ['count' => $speakers->count() - 4]) }}</p>
            @endif
        @endif

        @if($workshop)
            <div class="mt-auto pt-2">
                @if($isFull)
                    <span class="ccs-schedule-book is-full" aria-disabled="true">{{ __('Full') }}</span>
                @else
                    <a href="{{ route('workshops.book', [$event, $workshop]) }}" data-book-seat class="ccs-schedule-book">{{ __('Book your seat') }}</a>
                @endif
            </div>
        @endif
    </article>
@endif

@if($hasDetails)
    <template id="detail-{{ $entry->anchor() }}">
        <div class="flex items-center gap-2 text-sm text-gray-300 mb-3">
            <x-bi-clock class="w-4 h-4 text-ccs-gold" aria-hidden="true" />
            @if($entry->isScheduled())
                <span class="tabular-nums font-semibold">{{ $entry->day()->translatedFormat('j M') }} · <span dir="ltr">{{ $entry->start() }} – {{ $entry->end() }}</span></span>
            @else
                <span class="text-gray-400">{{ __('Time to be announced') }}</span>
            @endif
        </div>
        <span class="ccs-schedule-pill mb-3">{{ $entry->typeLabel() }}</span>
        <h2 class="font-display text-2xl font-extrabold leading-snug mb-4" data-schedule-title>{{ $entry->title() }}</h2>

        @if(filled($entry->description()))
            {{-- Sanitized on save (SanitizedRichText cast) — safe to render unescaped. --}}
            <div class="ccs-richtext text-gray-300 leading-relaxed mb-5">{!! $entry->description() !!}</div>
        @endif

        @if($entry->locationName())
            <div class="flex items-center gap-2 text-sm text-gray-300 mb-5">
                <x-bi-geo-alt class="w-4 h-4 text-ccs-coral" aria-hidden="true" />
                <span>{{ $entry->locationName() }}</span>
            </div>
        @endif

        @if($speakers->isNotEmpty())
            <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                @foreach($speakers as $speaker)
                    @php $jobTitle = app()->getLocale() === 'ar' ? $speaker->title_ar : $speaker->title_en; @endphp
                    <li class="flex items-center gap-3 min-w-0">
                        <x-speaker-avatar :speaker="$speaker" size="w-12 h-12" />
                        <div class="min-w-0">
                            <div class="font-semibold truncate">{{ app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en }}</div>
                            @if($jobTitle)<div class="text-xs text-gray-400 truncate">{{ $jobTitle }}</div>@endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        @if($workshop)
            <div class="flex flex-wrap items-center gap-4">
                @if($isFull)
                    <span class="ccs-schedule-book is-full" aria-disabled="true">{{ __('Full') }}</span>
                @else
                    {{-- null = unlimited capacity: no count to show. --}}
                    @if($seatsLeft !== null)
                        <span class="text-sm text-gray-300">{{ trans_choice(':count seat left|:count seats left', $seatsLeft, ['count' => $seatsLeft]) }}</span>
                    @endif
                    <a href="{{ route('workshops.book', [$event, $workshop]) }}" class="ccs-schedule-book">{{ __('Book your seat') }}</a>
                @endif
            </div>
        @endif
    </template>
@endif
