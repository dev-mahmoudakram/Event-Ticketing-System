{{-- resources/views/awards/show.blade.php --}}
@extends('layouts.app')

@section('title', __('Awards').' — '.(app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en))

@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if($votingIsOpen && $awards->isNotEmpty())
        <script id="turnstile-script" src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
    @endif
@endsection

@section('content')
    @include('landing.partials.nav', ['event' => $event, 'onLandingPage' => false])

    <section
        class="ccs-section scroll-mt-24 pt-32 pb-24"
        data-award-voting
        data-counts-url="{{ route('awards.counts', $event) }}"
        data-code-url-template="{{ route('awards.code', [$event, '__AWARD__']) }}"
        data-vote-url-template="{{ route('awards.vote', [$event, '__AWARD__']) }}"
        data-site-key="{{ $turnstileSiteKey }}"
    >
        @php
            $eventName = app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en;
            $awardVotingStrings = [
                'confirm' => __('You are voting for :nominee in :award.'),
                'genericError' => __('Something went wrong. Please try again.'),
                'tooManyAttempts' => __('Too many attempts. Please wait a minute and try again.'),
                'pageExpired' => __('This page has expired. Please reload the page and try again.'),
                'codeSentTo' => __('We sent a six-digit code to :email.'),
                'voteCountForms' => explode('|', __(':count vote|:count votes')),
            ];
        @endphp
        <script type="application/json" data-award-voting-strings>@json($awardVotingStrings)</script>
        <p class="sr-only" role="status" aria-live="polite" data-vote-announcer></p>

        <a href="{{ route('landing.show', $event) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-400 hover:text-white transition-colors mb-10">
            <span aria-hidden="true" class="rtl:rotate-180">&larr;</span> {{ __('Back to :event', ['event' => $eventName]) }}
        </a>

        <div class="ccs-eyebrow text-ccs-coral">{{ __(':event Awards', ['event' => $eventName]) }}</div>
        <h1 class="font-display text-3xl md:text-5xl font-extrabold mb-4">{{ __('Awards') }}</h1>

        @if($blurb)
            {{-- Sanitized on save against the strict 'cms' Purifier profile — see
                 App\Support\RichText — so it is safe to render unescaped here. --}}
            <div class="ccs-richtext text-lg text-gray-300 max-w-2xl leading-relaxed mb-6">{!! app()->getLocale() === 'ar' ? $blurb->value_ar : $blurb->value_en !!}</div>
        @endif

        <p class="inline-flex items-center gap-2 text-sm font-semibold {{ $votingIsOpen ? 'text-ccs-gold' : 'text-gray-400' }}" data-voting-status>
            <span aria-hidden="true" class="inline-block size-2 rounded-full {{ $votingIsOpen ? 'bg-ccs-gold' : 'bg-gray-500' }}"></span>
            {{ $statusLine }}
        </p>

        @if($awards->isEmpty())
            <p class="mt-12 text-gray-400">{{ __('Awards will be announced soon.') }}</p>
        @else
            <div role="tablist" aria-label="{{ __('Awards') }}" class="mt-10 flex gap-2 overflow-x-auto pb-2 -mx-1 px-1">
                @foreach($awards as $award)
                    <button
                        type="button"
                        role="tab"
                        id="award-tab-{{ $award->id }}"
                        aria-controls="award-{{ $award->id }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        tabindex="{{ $loop->first ? '0' : '-1' }}"
                        data-award-tab="{{ $award->id }}"
                        class="shrink-0 whitespace-nowrap rounded-full border border-white/20 px-4 py-2 text-sm font-semibold text-gray-300 transition-colors hover:border-white/50 hover:text-white aria-selected:border-ccs-coral aria-selected:bg-ccs-coral aria-selected:text-ccs-red focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                    >{{ $award->name() }}</button>
                @endforeach
            </div>

            @foreach($awards as $award)
                @php
                    $hasVoted = in_array($award->id, $votedAwardIds, true);
                    $awardTotal = $award->nominees->sum(fn ($nominee) => $counts[$nominee->id] ?? 0);
                @endphp
                <div
                    role="tabpanel"
                    id="award-{{ $award->id }}"
                    aria-labelledby="award-tab-{{ $award->id }}"
                    tabindex="0"
                    data-award-panel="{{ $award->id }}"
                    class="mt-8 scroll-mt-28 focus-visible:outline-none"
                    @unless($loop->first) hidden @endunless
                >
                    <div class="mb-6 flex flex-wrap items-center gap-3">
                        <h2 class="font-display text-2xl md:text-3xl font-extrabold">{{ $award->name() }}</h2>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-ccs-gold px-3 py-1 text-xs font-bold text-ccs-gold" data-award-voted @unless($hasVoted) hidden @endunless>
                            <span aria-hidden="true">&#10003;</span> {{ __('You voted') }}
                        </span>
                    </div>

                    @if($award->nominees->isEmpty())
                        <p class="text-gray-400">{{ __('Nominees will be announced soon.') }}</p>
                    @else
                        <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                            @foreach($award->nominees as $nominee)
                                @php
                                    $count = $counts[$nominee->id] ?? 0;
                                    $share = $awardTotal > 0 ? round($count / $awardTotal * 100, 1) : 0;
                                    $bio = $nominee->bio();
                                    $imageUrl = $nominee->imageUrl();
                                    $faceTag = $bio ? 'button' : 'div';
                                @endphp
                                <li class="flex flex-col rounded-2xl border border-white/10 bg-white/5 p-5" data-nominee-card="{{ $nominee->id }}">
                                    <{{ $faceTag }}
                                        class="flex items-center gap-4 text-start rounded-xl focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white"
                                        @if($bio)
                                            type="button"
                                            data-nominee-bio-toggle
                                            aria-expanded="false"
                                            aria-controls="nominee-bio-{{ $nominee->id }}"
                                        @endif
                                    >
                                        @if($imageUrl)
                                            <img src="{{ $imageUrl }}" alt="" loading="lazy" class="size-16 shrink-0 rounded-full object-cover bg-white/10">
                                        @else
                                            <span aria-hidden="true" class="flex size-16 shrink-0 items-center justify-center rounded-full bg-white/10 font-display text-xl font-extrabold text-gray-300">{{ mb_substr($nominee->name(), 0, 1) }}</span>
                                        @endif
                                        <span class="min-w-0">
                                            <span class="block font-display text-lg font-extrabold leading-snug break-words">{{ $nominee->name() }}</span>
                                            @if($bio)
                                                <span class="mt-0.5 block text-xs font-semibold text-gray-400">{{ __('About the nominee') }}</span>
                                            @endif
                                        </span>
                                    </{{ $faceTag }}>

                                    @if($bio)
                                        <p id="nominee-bio-{{ $nominee->id }}" class="mt-4 text-sm leading-relaxed text-gray-300" hidden>{{ $bio }}</p>
                                    @endif

                                    <div class="mt-5 h-2 overflow-hidden rounded-full bg-white/10" aria-hidden="true">
                                        <span class="block h-full rounded-full bg-ccs-gold transition-[width] duration-700 ease-out" data-bar-for="{{ $nominee->id }}" style="width: {{ $share }}%"></span>
                                    </div>
                                    <p class="mt-2 text-sm">
                                        <span class="font-bold text-white" data-count-for="{{ $nominee->id }}" data-count="{{ $count }}">{{ trans_choice(':count vote|:count votes', $count, ['count' => $count]) }}</span>
                                    </p>

                                    <div class="mt-auto pt-4">
                                        <span class="flex items-center justify-center gap-1.5 rounded-lg border border-ccs-gold py-2.5 text-sm font-bold text-ccs-gold" data-nominee-voted hidden>
                                            <span aria-hidden="true">&#10003;</span> {{ __('Your vote') }}
                                        </span>
                                        @if($votingIsOpen && ! $hasVoted)
                                            <button
                                                type="button"
                                                class="w-full rounded-lg bg-ccs-coral py-2.5 text-sm font-extrabold text-ccs-red transition hover:brightness-105 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                                                data-vote-nominee="{{ $nominee->id }}"
                                                data-vote-award="{{ $award->id }}"
                                                data-nominee-name="{{ $nominee->name() }}"
                                                data-award-name="{{ $award->name() }}"
                                                aria-label="{{ __('Vote for :nominee', ['nominee' => $nominee->name()]) }}"
                                            >{{ __('Vote') }}</button>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            @if($votingIsOpen)
                @include('awards.partials.vote-window')
            @endif
        @endif
    </section>

    @include('landing.partials.footer', ['event' => $event, 'onLandingPage' => false])
@endsection
