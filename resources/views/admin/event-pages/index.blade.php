{{-- resources/views/admin/event-pages/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Pages').' — '.(app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en)">
        <x-admin.button href="{{ route('admin.events.pages.create', $event) }}">{{ __('New Page') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif

    <p class="mb-4 max-w-2xl text-sm text-hub-dark/60">{{ __('Payment gateways check these pages before approving the site. Replace every [placeholder] in the drafts and have them reviewed before going live.') }}</p>

    <x-admin.table>
        <thead>
            <tr><th class="w-10"></th><th>{{ __('Title') }}</th><th>{{ __('Address') }}</th><th>{{ __('Status') }}</th><th>{{ __('Footer') }}</th><th></th></tr>
        </thead>
        <tbody data-sortable data-sortable-url="{{ route('admin.events.pages.reorder', $event) }}" data-sortable-error="{{ __('The new order could not be saved. Reload and try again.') }}">
            @foreach($pages as $page)
                <tr data-sortable-item="{{ $page->id }}">
                    <td><button type="button" class="adm-drag-handle" aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">⠿</button></td>
                    <td class="font-semibold">{{ $page->title() }}</td>
                    <td dir="ltr" class="text-sm text-hub-dark/60">/pages/{{ $page->slug }}</td>
                    <td>
                        <div class="flex flex-wrap gap-1.5">
                            @if($page->isRequired())<span class="rounded-full bg-hub-lavender px-2 py-0.5 text-xs font-bold text-hub-purple">{{ __('Required') }}</span>@endif
                            @unless($page->is_published)<span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-bold text-hub-dark/70">{{ __('Draft') }}</span>@endunless
                            @if($page->needsDetails())<span class="rounded-full bg-[#fdecea] px-2 py-0.5 text-xs font-bold text-[#b42318]">{{ __('Needs your details') }}</span>@endif
                        </div>
                    </td>
                    <td>{{ $page->show_in_footer ? __('Yes') : __('No') }}</td>
                    <td class="text-end whitespace-nowrap">
                        <a href="{{ route('admin.events.pages.edit', [$event, $page]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                        @if($page->is_published && Route::has('event-pages.show'))
                            <a href="{{ route('event-pages.show', [$event, $page->slug]) }}" target="_blank" rel="noopener" class="ms-3 text-hub-purple hover:underline">{{ __('View page') }}</a>
                        @endif
                        @unless($page->isRequired())
                            <form method="POST" action="{{ route('admin.events.pages.destroy', [$event, $page]) }}" class="inline" data-confirm="{{ __('Are you sure? This cannot be undone.') }}">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="ms-2">{{ __('Delete') }}</x-admin.button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>
@endsection
