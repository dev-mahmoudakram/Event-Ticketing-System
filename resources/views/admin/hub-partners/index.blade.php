{{-- resources/views/admin/hub-partners/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Creators Hub Partners')">
        <x-admin.button href="{{ route('admin.hub-partners.create') }}">{{ __('New Partner') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-gray-400 mb-6 max-w-2xl">{{ __('Logos shown in the partners strip on the Creators Hub page. These belong to the platform; an individual event has its own sponsors under that event.') }}</p>

    @if($partners->isEmpty())
        <x-admin.empty-state :message="__('No partners yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr class="border-b border-gray-700">
                    <th class="py-2 px-3">{{ __('Logo') }}</th>
                    <th class="py-2 px-3">{{ __('Name') }}</th>
                    <th class="py-2 px-3">{{ __('Sort Order') }}</th>
                    <th class="py-2 px-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($partners as $partner)
                    <tr class="border-b border-gray-800">
                        <td class="py-2 px-3">
                            @if($partner->logoUrl())
                                <img src="{{ $partner->logoUrl() }}" class="h-10 w-24 object-contain" alt="">
                            @endif
                        </td>
                        <td class="py-2 px-3">{{ $partner->name_en }}</td>
                        <td class="py-2 px-3">{{ $partner->sort_order }}</td>
                        <td class="py-2 px-3 text-right">
                            <a href="{{ route('admin.hub-partners.edit', $partner) }}" class="text-ccs-teal-light hover:underline">{{ __('Edit') }}</a>
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
