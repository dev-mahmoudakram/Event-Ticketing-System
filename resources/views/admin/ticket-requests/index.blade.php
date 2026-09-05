{{-- resources/views/admin/ticket-requests/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Ticket Requests').' — '.$event->name_en" />

    @if(session('success'))
        <div class="mb-4 rounded border border-ccs-teal-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-4 flex gap-2 text-sm">
        @foreach(['pending', 'approved', 'rejected', 'payment_pending', 'all'] as $option)
            <a href="{{ route('admin.events.ticket-requests.index', $event) }}?status={{ $option }}"
               class="px-3 py-1.5 rounded {{ $status === $option ? 'bg-hub-purple text-hub-dark' : 'border border-hub-purple/20 text-hub-dark/75' }}">
                {{ ucfirst(str_replace('_', ' ', $option)) }}
            </a>
        @endforeach
    </div>

    @if($tickets->isEmpty())
        <x-admin.empty-state :message="__('No ticket requests yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Ticket Type') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Answers') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $ticket)
                    <tr class="align-top">
                        <td>{{ $ticket->name }}</td>
                        <td>{{ $ticket->email }}</td>
                        <td>{{ $ticket->ticketType->name_en }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $ticket->status->value)) }}</td>
                        <td>
                            @foreach($ticket->answers as $answer)
                                <div class="text-xs text-hub-dark/60">
                                    {{ $answer->field->label_en }}:
                                    @if($answer->file_path)
                                        <a href="{{ route('admin.events.ticket-requests.answers.download', [$event, $ticket, $answer]) }}" class="text-hub-purple hover:underline">{{ __('Download') }}</a>
                                    @else
                                        {{ $answer->value }}
                                    @endif
                                </div>
                            @endforeach
                        </td>
                        <td class="text-end">
                            @if($ticket->status === \App\Enums\TicketStatus::Pending)
                                <form method="POST" action="{{ route('admin.events.ticket-requests.update-status', [$event, $ticket, 'approved']) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit">{{ __('Approve') }}</x-admin.button>
                                </form>
                                <form method="POST" action="{{ route('admin.events.ticket-requests.update-status', [$event, $ticket, 'rejected']) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit" variant="danger" class="ml-2">{{ __('Reject') }}</x-admin.button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
