@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Invitation Requests').' — '.$event->name_en" />

    @if(session('success'))<div role="status" class="mb-4 rounded border border-hub-purple/20 bg-hub-purple-light/10 px-4 py-3">{{ session('success') }}</div>@endif
    @if(session('error'))<div role="alert" class="mb-4 rounded border border-red-400/40 bg-red-50 px-4 py-3 text-red-700">{{ session('error') }}</div>@endif

    <div class="mb-4 flex gap-2 text-sm">
        @foreach(['pending', 'approved', 'rejected', 'all'] as $option)
            <a href="{{ route('admin.events.invitation-requests.index', ['event' => $event, 'status' => $option]) }}" class="px-3 py-1.5 rounded {{ $status === $option ? 'bg-hub-purple text-white' : 'border border-hub-purple/20 text-hub-dark/75' }}">{{ __(ucfirst($option)) }}</a>
        @endforeach
    </div>

    @if($invitationRequests->isEmpty())
        <x-admin.empty-state :message="__('No invitation requests yet.')" />
    @else
        <x-admin.table>
            <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Ticket Type') }}</th><th>{{ __('Category') }}</th><th>{{ __('Social') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
            <tbody>
                @foreach($invitationRequests as $invitationRequest)
                    <tr class="align-top">
                        <td>{{ $invitationRequest->name }}</td>
                        <td>{{ $invitationRequest->email }}</td>
                        <td>{{ $invitationRequest->phone }}</td>
                        <td>{{ $invitationRequest->invitation->ticketType?->name_en }}</td>
                        <td>{{ $invitationRequest->influencerCategory?->name_en ?? $invitationRequest->influencer_category_other }}</td>
                        <td>
                            @foreach(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'] as $platform => $label)
                                @php $url = $invitationRequest->{$platform.'_url'}; $followers = $invitationRequest->{$platform.'_followers'}; @endphp
                                @if($url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="block text-hub-purple hover:underline">{{ __($label) }} ({{ number_format($followers ?? 0) }})</a>
                                @endif
                            @endforeach
                        </td>
                        <td>{{ __(ucfirst($invitationRequest->status->value)) }}</td>
                        <td>
                            @if($invitationRequest->status === \App\Enums\InvitationRequestStatus::Pending)
                                <form method="POST" action="{{ route('admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'approved']) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit">{{ __('Approve') }}</x-admin.button>
                                </form>
                                <form method="POST" action="{{ route('admin.events.invitation-requests.update-status', [$event, $invitationRequest, 'rejected']) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit" variant="danger">{{ __('Reject') }}</x-admin.button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif

    <x-admin.pagination :paginator="$invitationRequests" />
@endsection
