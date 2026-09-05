{{-- resources/views/admin/testimonials/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Testimonials').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.testimonials.create', $event) }}">{{ __('New Testimonial') }}</x-admin.button>
        <x-admin.button href="{{ route('landing.show', $event).'#testimonials' }}" variant="secondary" target="_blank" rel="noopener">{{ __('View on page') }}</x-admin.button>
    </x-admin.page-header>

    @if($testimonials->isEmpty())
        <x-admin.empty-state :message="__('No testimonials yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Quote') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($testimonials as $testimonial)
                    <tr>
                        <td>{{ $testimonial->name_en }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($testimonial->quote_en, 60) }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.testimonials.edit', [$event, $testimonial]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.testimonials.destroy', [$event, $testimonial]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
