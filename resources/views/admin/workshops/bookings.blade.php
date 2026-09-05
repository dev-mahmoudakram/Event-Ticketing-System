{{-- resources/views/admin/workshops/bookings.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Attendees').' — '.$workshop->name_en">
        <x-admin.button href="{{ route('admin.events.workshops.index', $event) }}" variant="secondary">{{ __('Back') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-hub-dark/60 mb-6">
        {{ $workshop->bookings_count }}@if($workshop->capacity > 0) / {{ $workshop->capacity }} @endif
        {{ __('booked') }}
    </p>

    @if($bookings->isEmpty())
        <x-admin.empty-state :message="__('Nobody has booked this workshop yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Reference') }}</th>
                    <th>{{ __('Attendee') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Ticket Type') }}</th>
                    <th>{{ __('Booked') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    <tr>
                        <td><bdi>{{ $booking->ticket->ticket_number }}</bdi></td>
                        <td>{{ $booking->ticket->name }}</td>
                        <td class="text-hub-dark/60">{{ $booking->ticket->email }}</td>
                        <td class="text-hub-dark/60">{{ $booking->ticket->ticketType?->name_en }}</td>
                        <td class="text-hub-dark/60">{{ $booking->booked_at?->translatedFormat('j M Y, H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
