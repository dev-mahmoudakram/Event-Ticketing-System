{{-- resources/views/admin/hub-partners/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Creators Hub Partners')">
        <x-admin.button href="{{ route('admin.hub-partners.create') }}">{{ __('New Partner') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-hub-dark/60 mb-6 max-w-2xl">{{ __('Logos shown in the partners strip on the Creators Hub page. These belong to the platform; an individual event has its own sponsors under that event.') }}</p>

    @if($partners->isEmpty())
        <x-admin.empty-state :message="__('No partners yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Logo') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Sort Order') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($partners as $partner)
                    <tr>
                        <td>
                            @if($partner->logoUrl())
                                <img src="{{ $partner->logoUrl() }}" class="h-10 w-24 object-contain" alt="">
                            @endif
                        </td>
                        <td>{{ $partner->name_en }}</td>
                        <td>{{ $partner->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.hub-partners.edit', $partner) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.hub-partners.destroy', $partner) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
