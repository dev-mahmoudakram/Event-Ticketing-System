{{-- resources/views/admin/sponsor-tiers/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Sponsor Tiers').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.sponsor-tiers.create', $event) }}">{{ __('New Tier') }}</x-admin.button>
        <x-admin.button href="{{ route('admin.events.sponsors.index', $event) }}" variant="secondary">{{ __('Back to Sponsors') }}</x-admin.button>
    </x-admin.page-header>

    @if($sponsorTiers->isEmpty())
        <x-admin.empty-state :message="__('No sponsor tiers yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr><th>{{ __('Name') }}</th><th>{{ __('Sort Order') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($sponsorTiers as $sponsorTier)
                    <tr>
                        <td>{{ $sponsorTier->name_en }}</td>
                        <td>{{ $sponsorTier->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.sponsor-tiers.edit', [$event, $sponsorTier]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.sponsor-tiers.destroy', [$event, $sponsorTier]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
