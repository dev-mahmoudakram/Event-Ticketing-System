{{-- resources/views/admin/ticket-types/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Ticket Types').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.ticket-types.create', $event) }}">{{ __('New Ticket Type') }}</x-admin.button>
    </x-admin.page-header>

    @if($ticketTypes->isEmpty())
        <x-admin.empty-state :message="__('No ticket types yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Price') }}</th>
                    <th>{{ __('Workshop Slots') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($ticketTypes as $ticketType)
                    <tr>
                        <td>{{ $ticketType->name_en }}</td>
                        <td>{{ $ticketType->price }} {{ $ticketType->currency }}</td>
                        <td>{{ $ticketType->workshop_slot_count ?? __('Unlimited') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.ticket-types.edit', [$event, $ticketType]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.ticket-types.destroy', [$event, $ticketType]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
