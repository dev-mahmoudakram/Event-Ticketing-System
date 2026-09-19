{{-- resources/views/admin/influencer-categories/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Influencer Categories').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.influencer-categories.create', $event) }}">{{ __('New Category') }}</x-admin.button>
    </x-admin.page-header>

    @if($influencerCategories->isEmpty())
        <x-admin.empty-state :message="__('No influencer categories yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr><th>{{ __('Name') }}</th><th>{{ __('Sort Order') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($influencerCategories as $influencerCategory)
                    <tr>
                        <td>{{ $influencerCategory->name_en }}</td>
                        <td>{{ $influencerCategory->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.influencer-categories.edit', [$event, $influencerCategory]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.influencer-categories.destroy', [$event, $influencerCategory]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
