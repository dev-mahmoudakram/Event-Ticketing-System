{{-- resources/views/admin/events/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Events')">
        <x-admin.button href="{{ route('admin.events.create') }}">{{ __('New Event') }}</x-admin.button>
    </x-admin.page-header>

    @if($events->isEmpty())
        <x-admin.empty-state :message="__('No events yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($events as $event)
                    <tr>
                        <td>{{ $event->name_en }}</td>
                        <td>{{ $event->status->value }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.edit', $event) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="inline" data-confirm="{{ $event->tickets_count > 0 ? trans_choice('Delete this event and its :count ticket? This cannot be undone.|Delete this event and all :count of its tickets? This cannot be undone.', $event->tickets_count) : __('Are you sure? This cannot be undone.') }}">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="ml-2">{{ __('Delete') }}</x-admin.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
