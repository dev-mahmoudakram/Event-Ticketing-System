@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$title.' — '.(app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en)">
        <x-admin.button href="{{ route($routePrefix.'.create', $event) }}">{{ $newLabel }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-[#b42318]" role="alert">{{ session('error') }}</div>
    @endif

    @if($items->isEmpty())
        <x-admin.empty-state :message="__('Nothing here yet.')" />
    @else
        <p class="mb-4 text-sm text-hub-dark/60">{{ __('Drag to change the order they appear in.') }}</p>
        <x-admin.table>
            <thead>
                <tr><th class="w-10"></th><th>{{ __('Name (Arabic)') }}</th><th>{{ __('Name (English)') }}</th><th>{{ __('Used by') }}</th><th></th></tr>
            </thead>
            <tbody data-sortable data-sortable-url="{{ route($routePrefix.'.reorder', $event) }}" data-sortable-error="{{ __('The new order could not be saved. Reload and try again.') }}">
                @foreach($items as $item)
                    <tr data-sortable-item="{{ $item->id }}">
                        <td><button type="button" class="adm-drag-handle" aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">⠿</button></td>
                        <td>{{ $item->name_ar }}</td>
                        <td>
                            {{ $item->name_en }}
                            @if($showBreakToggle && $item->is_break)
                                <span class="ms-2 rounded-full bg-hub-lavender px-2 py-0.5 text-xs font-bold text-hub-purple">{{ __('Break') }}</span>
                            @endif
                        </td>
                        <td>{{ $item->usage_count }}</td>
                        <td class="text-end">
                            <a href="{{ route($routePrefix.'.edit', [$event, $item]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route($routePrefix.'.destroy', [$event, $item]) }}" class="inline" data-confirm="{{ __('Are you sure? This cannot be undone.') }}">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="ms-2">{{ __('Delete') }}</x-admin.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
