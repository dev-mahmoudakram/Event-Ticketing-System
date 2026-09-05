{{-- resources/views/admin/workshops/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Workshops').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.workshops.create', $event) }}">{{ __('New Workshop') }}</x-admin.button>
    </x-admin.page-header>

    @if($workshops->isEmpty())
        <x-admin.empty-state :message="__('No workshops yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr><th>{{ __('Name') }}</th><th>{{ __('Capacity') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($workshops as $workshop)
                    <tr>
                        <td>{{ $workshop->name_en }}</td>
                        <td>{{ $workshop->capacity }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.workshops.edit', [$event, $workshop]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.workshops.destroy', [$event, $workshop]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
