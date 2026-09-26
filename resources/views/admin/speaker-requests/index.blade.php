{{-- resources/views/admin/speaker-requests/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Speaker Requests').' — '.$event->name_en" />

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
        @foreach(['pending', 'approved', 'rejected', 'all'] as $option)
            <a href="{{ route('admin.events.speaker-requests.index', $event) }}?status={{ $option }}"
               class="px-3 py-1.5 rounded {{ $status === $option ? 'bg-hub-purple text-hub-dark' : 'border border-hub-purple/20 text-hub-dark/75' }}">
                {{ ucfirst($option) }}
            </a>
        @endforeach
    </div>

    @if($speakerRequests->isEmpty())
        <x-admin.empty-state :message="__('No speaker requests yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Photo') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Title') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Phone') }}</th>
                    <th>{{ __('Bio') }}</th>
                    <th>{{ __('What would you like to speak about?') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($speakerRequests as $speakerRequest)
                    <tr class="align-top">
                        <td>
                            @if($speakerRequest->photoUrl())
                                <img src="{{ $speakerRequest->photoUrl() }}" alt="" class="h-10 w-10 object-cover rounded-full border border-hub-purple/10">
                            @endif
                        </td>
                        <td>{{ $speakerRequest->name_en }}<br><span dir="rtl" class="text-hub-dark/60">{{ $speakerRequest->name_ar }}</span></td>
                        <td>{{ $speakerRequest->title_en }}<br><span dir="rtl" class="text-hub-dark/60">{{ $speakerRequest->title_ar }}</span></td>
                        <td>{{ $speakerRequest->email }}</td>
                        <td>{{ $speakerRequest->phone }}</td>
                        <td class="max-w-xs">{{ $speakerRequest->bio_en }}</td>
                        <td class="max-w-xs">{{ $speakerRequest->message }}</td>
                        <td>{{ ucfirst($speakerRequest->status->value) }}</td>
                        <td class="text-end">
                            @if($speakerRequest->status === \App\Enums\SpeakerRequestStatus::Pending)
                                <form method="POST" action="{{ route('admin.events.speaker-requests.update-status', [$event, $speakerRequest, 'approved']) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit">{{ __('Approve') }}</x-admin.button>
                                </form>
                                <form method="POST" action="{{ route('admin.events.speaker-requests.update-status', [$event, $speakerRequest, 'rejected']) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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

    <x-admin.pagination :paginator="$speakerRequests" />
@endsection
