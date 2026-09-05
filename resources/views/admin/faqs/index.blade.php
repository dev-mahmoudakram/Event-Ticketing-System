{{-- resources/views/admin/faqs/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('FAQs').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.faqs.create', $event) }}">{{ __('New FAQ') }}</x-admin.button>
        <x-admin.button href="{{ route('landing.show', $event).'#faq' }}" variant="secondary" target="_blank" rel="noopener">{{ __('View on page') }}</x-admin.button>
    </x-admin.page-header>

    @if($faqs->isEmpty())
        <x-admin.empty-state :message="__('No FAQs yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr><th>{{ __('Question') }}</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($faqs as $faq)
                    <tr>
                        <td>{{ $faq->question_en }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.faqs.edit', [$event, $faq]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.faqs.destroy', [$event, $faq]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
