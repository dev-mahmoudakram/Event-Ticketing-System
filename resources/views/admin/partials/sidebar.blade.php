{{-- resources/views/admin/partials/sidebar.blade.php --}}
@php
    // Every section an event owns. One list, used for each event in the tree below.
    $eventSections = [
        ['prefix' => 'admin.events.content', 'route' => 'admin.events.content.edit', 'label' => __('Landing Page Content')],
        ['prefix' => 'admin.events.speakers', 'route' => 'admin.events.speakers.index', 'label' => __('Speakers')],
        ['prefix' => 'admin.events.sponsors', 'route' => 'admin.events.sponsors.index', 'label' => __('Partners')],
        ['prefix' => 'admin.events.ticket-types', 'route' => 'admin.events.ticket-types.index', 'label' => __('Ticket Types')],
        ['prefix' => 'admin.events.request-form-fields', 'route' => 'admin.events.request-form-fields.index', 'label' => __('Request Form')],
        ['prefix' => 'admin.events.ticket-requests', 'route' => 'admin.events.ticket-requests.index', 'label' => __('Ticket Requests')],
        ['prefix' => 'admin.events.discount-coupons', 'route' => 'admin.events.discount-coupons.index', 'label' => __('Discount Coupons')],
        ['prefix' => 'admin.events.reports', 'route' => 'admin.events.reports.show', 'label' => __('Report')],
        ['prefix' => 'check-in', 'route' => 'check-in.index', 'label' => __('Registration Desk')],
        ['prefix' => 'admin.events.workshops', 'route' => 'admin.events.workshops.index', 'label' => __('Workshops')],
        ['prefix' => 'admin.events.agenda-items', 'route' => 'admin.events.agenda-items.index', 'label' => __('Agenda')],
        ['prefix' => 'admin.events.reels', 'route' => 'admin.events.reels.index', 'label' => __('Reels')],
        ['prefix' => 'admin.events.gallery-photos', 'route' => 'admin.events.gallery-photos.index', 'label' => __('Gallery')],
        ['prefix' => 'admin.events.testimonials', 'route' => 'admin.events.testimonials.index', 'label' => __('Testimonials')],
        ['prefix' => 'admin.events.faqs', 'route' => 'admin.events.faqs.index', 'label' => __('FAQs')],
        ['prefix' => 'admin.events.contact-messages', 'route' => 'admin.events.contact-messages.index', 'label' => __('Contact Messages')],
        ['prefix' => 'admin.events.newsletter-subscribers', 'route' => 'admin.events.newsletter-subscribers.index', 'label' => __('Newsletter')],
    ];

    // The event being worked on, so its branch opens on arrival rather than after a click.
    $currentEvent = request()->route('event');
    $currentEventId = $currentEvent instanceof \App\Models\Event ? $currentEvent->id : null;

    $inEvents = request()->routeIs('admin.events.*');
    $inHub = request()->routeIs('admin.site-content.*', 'admin.hero-slides.*', 'admin.hub-partners.*', 'admin.site-faqs.*', 'admin.reports.*');
@endphp

<div class="flex items-center gap-2.5 px-5 py-5 border-b border-hub-purple/10">
    <img src="{{ asset('images/creators-hub/mark.png') }}" alt="" aria-hidden="true" class="h-7 w-auto">
    <span class="font-display font-extrabold text-base tracking-tight text-hub-purple">{{ __('Creators Hub Admin') }}</span>
</div>

<nav class="flex-1 overflow-y-auto px-3 py-4 flex flex-col gap-1">
    <a href="{{ route('admin.dashboard') }}" class="adm-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
        {{ __('Dashboard') }}
    </a>

    {{-- Events: the list lives in the menu, and each event opens onto its own sections. --}}
    <div class="mt-4" x-data="{ open: {{ $inEvents ? 'true' : 'false' }}, event: {{ $currentEventId ?? 'null' }} }">
        <button
            type="button"
            @click="open = ! open"
            class="adm-nav-link w-full justify-between {{ request()->routeIs('admin.events.index') ? 'is-active' : '' }}"
            :aria-expanded="open ? 'true' : 'false'"
        >
            <span>{{ __('Events') }}</span>
            <span class="text-xs transition-transform duration-200" :class="open ? 'rotate-90' : ''" aria-hidden="true">&rsaquo;</span>
        </button>

        <div class="grid transition-[grid-template-rows] duration-200 ease-out motion-reduce:transition-none" :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
        <div class="overflow-hidden">
        <div class="adm-nav-branch mt-1 flex flex-col gap-0.5">
            <a href="{{ route('admin.events.index') }}" class="adm-nav-link adm-nav-sub {{ request()->routeIs('admin.events.index') ? 'is-active' : '' }}">
                {{ __('All events') }}
            </a>
            <a href="{{ route('admin.events.create') }}" class="adm-nav-link adm-nav-sub {{ request()->routeIs('admin.events.create') ? 'is-active' : '' }}">
                {{ __('New Event') }}
            </a>

            @forelse($sidebarEvents as $sidebarEvent)
                @php $eventName = app()->getLocale() === 'ar' ? $sidebarEvent->name_ar : $sidebarEvent->name_en; @endphp
                <div class="mt-1">
                    <button
                        type="button"
                        @click="event = event === {{ $sidebarEvent->id }} ? null : {{ $sidebarEvent->id }}"
                        class="adm-nav-link adm-nav-sub w-full justify-between text-start"
                        :class="event === {{ $sidebarEvent->id }} ? 'is-active' : ''"
                        :aria-expanded="event === {{ $sidebarEvent->id }} ? 'true' : 'false'"
                    >
                        <span class="truncate">{{ $eventName }}</span>
                        <span class="text-xs shrink-0 transition-transform duration-200" :class="event === {{ $sidebarEvent->id }} ? 'rotate-90' : ''" aria-hidden="true">&rsaquo;</span>
                    </button>

                    <div class="grid transition-[grid-template-rows] duration-200 ease-out motion-reduce:transition-none" :class="event === {{ $sidebarEvent->id }} ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                    <div class="overflow-hidden">
                    <div class="adm-nav-branch mt-0.5 flex flex-col gap-0.5">
                        <a href="{{ route('admin.events.edit', $sidebarEvent) }}" class="adm-nav-link adm-nav-sub {{ request()->routeIs('admin.events.edit') && $currentEventId === $sidebarEvent->id ? 'is-active' : '' }}">
                            {{ __('Event Details') }}
                        </a>
                        @foreach($eventSections as $section)
                            <a href="{{ route($section['route'], $sidebarEvent) }}" class="adm-nav-link adm-nav-sub {{ request()->routeIs($section['prefix'].'.*') && $currentEventId === $sidebarEvent->id ? 'is-active' : '' }}">
                                {{ $section['label'] }}
                            </a>
                        @endforeach
                    </div>
                    </div>
                    </div>
                </div>
            @empty
                <p class="px-3 py-2 text-xs text-hub-dark/45">{{ __('No events yet.') }}</p>
            @endforelse
        </div>
        </div>
        </div>
    </div>

    {{-- The platform's own pages, kept apart from the events it hosts. --}}
    <div class="mt-4" x-data="{ open: {{ $inHub ? 'true' : 'false' }} }">
        <button
            type="button"
            @click="open = ! open"
            class="adm-nav-link w-full justify-between"
            :aria-expanded="open ? 'true' : 'false'"
        >
            <span>{{ __('Creators Hub') }}</span>
            <span class="text-xs transition-transform duration-200" :class="open ? 'rotate-90' : ''" aria-hidden="true">&rsaquo;</span>
        </button>

        <div class="grid transition-[grid-template-rows] duration-200 ease-out motion-reduce:transition-none" :class="open ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
        <div class="overflow-hidden">
        <div class="adm-nav-branch mt-1 flex flex-col gap-0.5">
            <a href="{{ route('admin.reports.show') }}" class="adm-nav-link adm-nav-sub {{ request()->routeIs('admin.reports.*') ? 'is-active' : '' }}">{{ __('Platform Report') }}</a>
            <a href="{{ route('admin.site-content.index') }}" class="adm-nav-link adm-nav-sub {{ request()->routeIs('admin.site-content.*') ? 'is-active' : '' }}">{{ __('Landing Page Content') }}</a>
            <a href="{{ route('admin.hero-slides.index') }}" class="adm-nav-link adm-nav-sub {{ request()->routeIs('admin.hero-slides.*') ? 'is-active' : '' }}">{{ __('Hero Slides') }}</a>
            <a href="{{ route('admin.hub-partners.index') }}" class="adm-nav-link adm-nav-sub {{ request()->routeIs('admin.hub-partners.*') ? 'is-active' : '' }}">{{ __('Partners') }}</a>
            <a href="{{ route('admin.site-faqs.index') }}" class="adm-nav-link adm-nav-sub {{ request()->routeIs('admin.site-faqs.*') ? 'is-active' : '' }}">{{ __('FAQs') }}</a>
        </div>
        </div>
        </div>
    </div>
</nav>

<form method="POST" action="{{ route('admin.logout') }}" class="px-5 py-4 border-t border-hub-purple/10">
    @csrf
    <button type="submit" class="text-sm font-semibold text-hub-dark/55 hover:text-hub-purple transition-colors">{{ __('Log out') }}</button>
</form>
