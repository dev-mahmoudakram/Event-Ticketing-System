@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Invitations').' — '.$event->name_en" />

    @if(session('generated_invitation_id'))
        @php $generated = $invitations->firstWhere('id', session('generated_invitation_id')); @endphp
        @if($generated)
            <div class="mb-6 rounded-2xl border border-hub-purple/30 bg-hub-lavender px-6 py-5" role="status">
                <p class="font-bold mb-3">{{ __('Invitation created. Copy the link and code to send to the invitee.') }}</p>
                <label for="generated-link" class="block text-sm mb-1">{{ __('Link') }}</label>
                <input id="generated-link" type="text" readonly value="{{ route('invitations.verify', [$event, $generated->token]) }}" class="w-full mb-3 adm-input" onclick="this.select()">
                <label for="generated-code" class="block text-sm mb-1">{{ __('One-time code') }}</label>
                <input id="generated-code" type="text" readonly value="{{ $generated->otp }}" class="w-full adm-input" onclick="this.select()">
            </div>
        @endif
    @endif

    <form method="POST" action="{{ route('admin.events.invitations.store', $event) }}" class="mb-8 flex flex-wrap items-end gap-3">
        @csrf
        <x-admin.field type="select" name="ticket_type_id" label="{{ __('Ticket Type') }}" required>
            <option value="">{{ __('Select a ticket type') }}</option>
            @foreach($ticketTypes as $ticketType)
                <option value="{{ $ticketType->id }}" @selected(old('ticket_type_id') == $ticketType->id)>{{ app()->getLocale() === 'ar' ? $ticketType->name_ar : $ticketType->name_en }}</option>
            @endforeach
        </x-admin.field>
        <x-admin.button type="submit">{{ __('Generate Invitation') }}</x-admin.button>
    </form>

    @if($invitations->isEmpty())
        <x-admin.empty-state :message="__('No invitations yet.')" />
    @else
        <x-admin.table>
            <thead><tr><th>{{ __('Ticket Type') }}</th><th>{{ __('Link') }}</th><th>{{ __('OTP') }}</th><th>{{ __('Status') }}</th><th>{{ __('Expires') }}</th><th></th></tr></thead>
            <tbody>
                @foreach($invitations as $invitation)
                    <tr>
                        <td>{{ $invitation->ticketType?->name_en }}</td>
                        <td><input type="text" readonly value="{{ route('invitations.verify', [$event, $invitation->token]) }}" class="adm-input w-64" aria-label="{{ __('Invitation link') }}" onclick="this.select()"></td>
                        <td><code>{{ $invitation->otp }}</code></td>
                        <td>{{ $invitation->status === \App\Enums\InvitationStatus::Unused && $invitation->expires_at->isPast() ? __('Expired') : $invitation->status->label() }}</td>
                        <td>{{ $invitation->expires_at->format('Y-m-d') }}</td>
                        <td>
                            @if($invitation->isUsable())
                                <form method="POST" action="{{ route('admin.events.invitations.revoke', [$event, $invitation]) }}" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit" variant="danger">{{ __('Revoke') }}</x-admin.button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
