{{-- resources/views/admin/request-form-fields/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Request Form').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.request-form-fields.create', $event) }}">{{ __('New Request Field') }}</x-admin.button>
    </x-admin.page-header>

    @if($requestFields->isEmpty())
        <x-admin.empty-state :message="__('No request fields yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Label') }}</th>
                    <th>{{ __('Required') }}</th>
                    <th>{{ __('Sort Order') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($requestFields as $requestField)
                    <tr>
                        <td>{{ ucfirst($requestField->type->value) }}</td>
                        <td>{{ $requestField->label_en }}</td>
                        <td>{{ $requestField->is_required ? __('Yes') : __('No') }}</td>
                        <td>{{ $requestField->sort_order }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.request-form-fields.edit', [$event, $requestField]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.request-form-fields.destroy', [$event, $requestField]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
