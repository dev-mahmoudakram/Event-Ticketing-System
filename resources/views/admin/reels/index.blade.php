{{-- resources/views/admin/reels/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Reels').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.reels.create', $event) }}">{{ __('New Reel') }}</x-admin.button>
        <x-admin.button href="{{ route('landing.show', $event).'#reel' }}" variant="secondary" target="_blank" rel="noopener">{{ __('View on page') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-hub-dark/60 mb-6">{{ __('Vertical clips shown in The Reel section, and in the hero cards at the top of the landing page.') }}</p>

    @if($reels->isEmpty())
        <x-admin.empty-state :message="__('No reels yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Clip') }}</th>
                    <th>{{ __('Caption') }}</th>
                    <th>{{ __('Sort Order') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($reels as $reel)
                    <tr>
                        <td>
                            @if($reel->posterUrl())
                                <img src="{{ $reel->posterUrl() }}" class="h-16 w-10 object-cover rounded" alt="">
                            @else
                                <video src="{{ $reel->videoUrl() }}" class="h-16 w-10 object-cover rounded" muted playsinline preload="metadata"></video>
                            @endif
                        </td>
                        <td>{{ $reel->caption_en }}</td>
                        <td>{{ $reel->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.reels.edit', [$event, $reel]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.reels.destroy', [$event, $reel]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
