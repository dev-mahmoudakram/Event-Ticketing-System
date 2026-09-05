{{-- resources/views/admin/gallery-photos/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Gallery Photos').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.gallery-photos.create', $event) }}">{{ __('New Photo') }}</x-admin.button>
        <x-admin.button href="{{ route('landing.show', $event).'#gallery' }}" variant="secondary" target="_blank" rel="noopener">{{ __('View on page') }}</x-admin.button>
    </x-admin.page-header>

    @if($galleryPhotos->isEmpty())
        <x-admin.empty-state :message="__('No gallery photos yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Photo') }}</th>
                    <th>{{ __('Caption') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($galleryPhotos as $photo)
                    <tr>
                        <td><img src="{{ $photo->imageUrl() }}" class="h-12 w-12 object-cover rounded" alt=""></td>
                        <td>{{ $photo->caption_en }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.gallery-photos.edit', [$event, $photo]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.gallery-photos.destroy', [$event, $photo]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
