{{-- resources/views/admin/awards/nominees.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Nominees').' — '.$award->name()">
        <x-admin.button href="{{ route('admin.events.awards.index', $event) }}" variant="secondary">{{ __('Awards') }}</x-admin.button>
        <x-admin.button href="{{ route('admin.events.awards.nominees.create', [$event, $award]) }}">{{ __('New Nominee') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif

    @if($nominees->isEmpty())
        <x-admin.empty-state :message="__('No nominees yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr><th class="w-10"></th><th>{{ __('Photo') }}</th><th>{{ __('Name') }}</th><th>{{ __('Votes') }}</th><th></th></tr>
            </thead>
            <tbody data-sortable data-sortable-url="{{ route('admin.events.awards.nominees.reorder', [$event, $award]) }}" data-sortable-error="{{ __('The new order could not be saved. Reload and try again.') }}">
                @foreach($nominees as $nominee)
                    <tr data-sortable-item="{{ $nominee->id }}">
                        <td><button type="button" class="adm-drag-handle" aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">⠿</button></td>
                        <td>
                            @if($nominee->imageUrl())
                                <img src="{{ $nominee->imageUrl() }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            @endif
                        </td>
                        <td class="font-semibold">{{ $nominee->name() }}</td>
                        <td data-vote-count>{{ $nominee->counted_votes_count }}</td>
                        <td class="text-end whitespace-nowrap">
                            <a href="{{ route('admin.events.awards.nominees.edit', [$event, $award, $nominee]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.awards.nominees.destroy', [$event, $award, $nominee]) }}" class="inline" data-confirm="{{ $nominee->counted_votes_count > 0 ? __('This also deletes its votes. Are you sure?') : __('Are you sure? This cannot be undone.') }}">
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
