@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Ticket Verification').' — '.$event->name_en" />

    @if($result === 'verified')
        <div class="mb-6 rounded border border-ccs-teal-light/40 bg-ccs-teal-light/10 px-4 py-3 text-lg font-bold text-ccs-teal-light" role="status">
            {{ __('Verified. Entry allowed.') }}
        </div>
    @elseif($result === 'used')
        <div class="mb-6 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-lg font-bold text-red-300" role="alert">
            {{ __('This ticket has already been checked in. Entry denied.') }}
        </div>
    @elseif($result === 'unpaid')
        <div class="mb-6 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-lg font-bold text-red-300" role="alert">
            {{ __('Unverified. Payment is not completed. Entry denied.') }}
        </div>
    @else
        <div class="mb-6 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-lg font-bold text-red-300" role="alert">
            {{ __('Unverified ticket. Entry denied.') }}
        </div>
    @endif

    @if($ticket)
        <div class="max-w-xl rounded border border-gray-800 bg-gray-900 p-6">
            <h2 class="font-display text-xl font-bold">{{ __('Ticket details') }}</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-gray-400">{{ __('Name') }}</dt><dd>{{ $ticket->name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-400">{{ __('Email') }}</dt><dd>{{ $ticket->email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-400">{{ __('Phone') }}</dt><dd>{{ $ticket->phone }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-400">{{ __('Reference') }}</dt><dd>{{ $ticket->ticket_number }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-gray-400">{{ __('Status') }}</dt><dd>{{ $ticket->status->value }}</dd></div>
                @if($ticket->checked_in_at)
                    <div class="flex justify-between gap-4"><dt class="text-gray-400">{{ __('Checked in at') }}</dt><dd>{{ $ticket->checked_in_at }}</dd></div>
                @endif
            </dl>
        </div>
    @endif
@endsection