{{-- resources/views/admin/sponsor-requests/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Sponsor Requests').' — '.$event->name_en" />

    @if(session('success'))
        <div class="mb-4 rounded border border-ccs-teal-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4 flex gap-2 text-sm">
        @foreach(['pending', 'approved', 'rejected', 'all'] as $option)
            <a href="{{ route('admin.events.sponsor-requests.index', $event) }}?status={{ $option }}"
               class="px-3 py-1.5 rounded {{ $status === $option ? 'bg-hub-purple text-hub-dark' : 'border border-hub-purple/20 text-hub-dark/75' }}">
                {{ ucfirst($option) }}
            </a>
        @endforeach
    </div>

    @if($sponsorRequests->isEmpty())
        <x-admin.empty-state :message="__('No sponsor requests yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Logo') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Contact Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Phone') }}</th>
                    <th>{{ __('Links') }}</th>
                    <th>{{ __('Message') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($sponsorRequests as $sponsorRequest)
                    <tr class="align-top">
                        <td>
                            @if($sponsorRequest->logoUrl())
                                <img src="{{ $sponsorRequest->logoUrl() }}" alt="" class="h-10 w-10 object-contain rounded border border-hub-purple/10">
                            @endif
                        </td>
                        <td>{{ $sponsorRequest->name_en }}<br><span dir="rtl" class="text-hub-dark/60">{{ $sponsorRequest->name_ar }}</span></td>
                        <td>{{ $sponsorRequest->contact_name }}</td>
                        <td>{{ $sponsorRequest->email }}</td>
                        <td>{{ $sponsorRequest->phone }}</td>
                        <td>
                            @if($sponsorRequest->website_url)
                                <a href="{{ $sponsorRequest->website_url }}" target="_blank" rel="noopener noreferrer" class="block text-hub-purple hover:underline">{{ __('Website') }}</a>
                            @endif
                            @if($sponsorRequest->instagram_url)
                                <a href="{{ $sponsorRequest->instagram_url }}" target="_blank" rel="noopener noreferrer" class="block text-hub-purple hover:underline">{{ __('Instagram') }}</a>
                            @endif
                            @if($sponsorRequest->facebook_url)
                                <a href="{{ $sponsorRequest->facebook_url }}" target="_blank" rel="noopener noreferrer" class="block text-hub-purple hover:underline">{{ __('Facebook') }}</a>
                            @endif
                        </td>
                        <td class="max-w-xs">{{ $sponsorRequest->message }}</td>
                        <td>{{ ucfirst($sponsorRequest->status->value) }}</td>
                        <td class="text-end" x-data="{ approveOpen: false }">
                            @if($sponsorRequest->status === \App\Enums\SponsorRequestStatus::Pending)
                                <x-admin.button type="button" @click="approveOpen = true">{{ __('Approve') }}</x-admin.button>
                                <form method="POST" action="{{ route('admin.events.sponsor-requests.update-status', [$event, $sponsorRequest, 'rejected']) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit" variant="danger" class="ml-2">{{ __('Reject') }}</x-admin.button>
                                </form>

                                <div x-show="approveOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="approveOpen = false">
                                    <div class="fixed inset-0 bg-hub-dark/50" @click="approveOpen = false"></div>
                                    <div class="relative bg-white rounded-2xl max-w-sm w-full p-6 text-start">
                                        <h3 class="font-display text-lg font-bold text-hub-purple mb-1">{{ __('Approve Sponsor Request') }}</h3>
                                        <p class="text-sm text-hub-dark/60 mb-4">{{ __('Choose the tier this sponsor will be listed under.') }}</p>
                                        <form method="POST" action="{{ route('admin.events.sponsor-requests.update-status', [$event, $sponsorRequest, 'approved']) }}">
                                            @csrf @method('PATCH')
                                            <x-admin.field type="select" name="sponsor_tier_id" label="{{ __('Tier') }}" required>
                                                <option value="" disabled selected>{{ __('Select a tier') }}</option>
                                                @foreach($sponsorTiers as $sponsorTier)
                                                    <option value="{{ $sponsorTier->id }}">{{ $sponsorTier->name_en }}</option>
                                                @endforeach
                                            </x-admin.field>
                                            <div class="flex gap-2 justify-end mt-2">
                                                <button type="button" @click="approveOpen = false" class="px-4 py-2 text-sm text-hub-dark/60 hover:text-hub-dark">{{ __('Cancel') }}</button>
                                                <x-admin.button type="submit">{{ __('Approve') }}</x-admin.button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
