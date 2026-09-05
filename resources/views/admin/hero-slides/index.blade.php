{{-- resources/views/admin/hero-slides/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Hero Slides')">
        <x-admin.button href="{{ route('admin.hero-slides.create') }}">{{ __('New Slide') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-hub-dark/60 mb-6 max-w-2xl">{{ __('Images shown in the full-screen slider at the top of the Creators Hub page. One slide simply shows that image; with none, the hero falls back to the brand background.') }}</p>

    @if($slides->isEmpty())
        <x-admin.empty-state :message="__('No slides yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Image') }}</th>
                    <th>{{ __('Headline') }}</th>
                    <th>{{ __('Sort Order') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($slides as $slide)
                    <tr>
                        <td>
                            @if($slide->imageUrl())
                                <img src="{{ $slide->imageUrl() }}" class="h-12 w-20 object-cover rounded" alt="">
                            @endif
                        </td>
                        <td>
                            {{ $slide->headline_en ?: __('Uses the shared hero copy') }}
                        </td>
                        <td>{{ $slide->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.hero-slides.edit', $slide) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.hero-slides.destroy', $slide) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
