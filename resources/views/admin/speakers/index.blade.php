{{-- resources/views/admin/speakers/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Speakers').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.speakers.create', $event) }}">{{ __('New Speaker') }}</x-admin.button>
    </x-admin.page-header>

    @if($speakers->isEmpty())
        <x-admin.empty-state :message="__('No speakers yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr><th>{{ __('Name') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($speakers as $speaker)
                    <tr>
                        <td>{{ $speaker->name_en }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.speakers.edit', [$event, $speaker]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.speakers.destroy', [$event, $speaker]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
