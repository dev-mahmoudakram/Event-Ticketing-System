{{-- resources/views/admin/partials/sidebar.blade.php --}}
<aside class="w-64 shrink-0 bg-ccs-black border-r border-gray-800 flex flex-col p-6">
    <div class="mb-8">
        <span class="font-display font-bold text-lg">{{ __('CCS Admin') }}</span>
    </div>

    <nav class="flex flex-col gap-1 flex-1">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.dashboard') ? 'bg-gray-900' : 'hover:bg-gray-900' }}">
            @if(request()->routeIs('admin.dashboard'))
                <span class="ccs-flag-accent" style="width:4px;height:16px;"></span>
            @endif
            {{ __('Dashboard') }}
        </a>
        @php
            $eventsResourceRoutes = ['admin.events.index', 'admin.events.create', 'admin.events.store', 'admin.events.edit', 'admin.events.update', 'admin.events.destroy'];
        @endphp
        <a href="{{ route('admin.events.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs(...$eventsResourceRoutes) ? 'bg-gray-900' : 'hover:bg-gray-900' }}">
            @if(request()->routeIs(...$eventsResourceRoutes))
                <span class="ccs-flag-accent" style="width:4px;height:16px;"></span>
            @endif
            {{ __('Events') }}
        </a>

        <a href="{{ route('admin.site-content.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.site-content.*') ? 'bg-gray-900' : 'hover:bg-gray-900' }}">
            @if(request()->routeIs('admin.site-content.*'))
                <span class="ccs-flag-accent" style="width:4px;height:16px;"></span>
            @endif
            {{ __('Creators Hub Content') }}
        </a>
        <a href="{{ route('admin.site-faqs.index') }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs('admin.site-faqs.*') ? 'bg-gray-900' : 'hover:bg-gray-900' }}">
            @if(request()->routeIs('admin.site-faqs.*'))
                <span class="ccs-flag-accent" style="width:4px;height:16px;"></span>
            @endif
            {{ __('Creators Hub FAQs') }}
        </a>

        @if(isset($event) && $event->exists)
            <div class="mt-6 pt-6 border-t border-gray-800">
                <p class="text-xs uppercase tracking-wide text-gray-500 px-3 mb-2">{{ $event->name_en }}</p>
                @php
                    $eventNavItems = [
                        ['prefix' => 'admin.events.speakers', 'route' => 'admin.events.speakers.index', 'label' => __('Speakers')],
                        ['prefix' => 'admin.events.sponsors', 'route' => 'admin.events.sponsors.index', 'label' => __('Partners')],
                        ['prefix' => 'admin.events.ticket-types', 'route' => 'admin.events.ticket-types.index', 'label' => __('Ticket Types')],
                        ['prefix' => 'admin.events.workshops', 'route' => 'admin.events.workshops.index', 'label' => __('Workshops')],
                        ['prefix' => 'admin.events.agenda-items', 'route' => 'admin.events.agenda-items.index', 'label' => __('Agenda')],
                        ['prefix' => 'admin.events.reels', 'route' => 'admin.events.reels.index', 'label' => __('Reels')],
                        ['prefix' => 'admin.events.gallery-photos', 'route' => 'admin.events.gallery-photos.index', 'label' => __('Gallery')],
                        ['prefix' => 'admin.events.testimonials', 'route' => 'admin.events.testimonials.index', 'label' => __('Testimonials')],
                        ['prefix' => 'admin.events.contact-messages', 'route' => 'admin.events.contact-messages.index', 'label' => __('Contact Messages')],
                        ['prefix' => 'admin.events.newsletter-subscribers', 'route' => 'admin.events.newsletter-subscribers.index', 'label' => __('Newsletter')],
                        ['prefix' => 'admin.events.faqs', 'route' => 'admin.events.faqs.index', 'label' => __('FAQs')],
                        ['prefix' => 'admin.events.request-form-fields', 'route' => 'admin.events.request-form-fields.index', 'label' => __('Request Form')],
                        ['prefix' => 'admin.events.ticket-requests', 'route' => 'admin.events.ticket-requests.index', 'label' => __('Ticket Requests')],
                        ['prefix' => 'admin.events.content', 'route' => 'admin.events.content.edit', 'label' => __('Content')],
                    ];
                @endphp
                @foreach($eventNavItems as $item)
                    <a href="{{ route($item['route'], $event) }}" class="flex items-center gap-2 px-3 py-2 rounded {{ request()->routeIs($item['prefix'].'.*') ? 'bg-gray-900' : 'hover:bg-gray-900' }}">
                        @if(request()->routeIs($item['prefix'].'.*'))
                            <span class="ccs-flag-accent" style="width:4px;height:16px;"></span>
                        @endif
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        @endif
    </nav>

    <form method="POST" action="{{ route('admin.logout') }}" class="mt-6 pt-6 border-t border-gray-800">
        @csrf
        <button type="submit" class="text-sm text-gray-400 hover:text-white">{{ __('Log out') }}</button>
    </form>
</aside>
