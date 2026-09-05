{{-- resources/views/admin/sponsors/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Sponsors').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.sponsors.create', $event) }}">{{ __('New Sponsor') }}</x-admin.button>
        <x-admin.button href="{{ route('landing.show', $event).'#partners' }}" variant="secondary" target="_blank" rel="noopener">{{ __('View on page') }}</x-admin.button>
    </x-admin.page-header>

    @if($sponsors->isEmpty())
        <x-admin.empty-state :message="__('No sponsors yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr><th>{{ __('Name') }}</th><th>{{ __('Tier') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($sponsors as $sponsor)
                    <tr>
                        <td>{{ $sponsor->name_en }}</td>
                        <td>{{ $sponsor->tier }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.sponsors.edit', [$event, $sponsor]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.sponsors.destroy', [$event, $sponsor]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
