{{-- resources/views/admin/site-faqs/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Creators Hub FAQs')">
        <x-admin.button href="{{ route('admin.site-faqs.create') }}">{{ __('New FAQ') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-gray-400 mb-6">{{ __('Questions shown on the Creators Hub landing page. The section stays hidden while there are none.') }}</p>

    @if($faqs->isEmpty())
        <x-admin.empty-state :message="__('No FAQs yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr class="border-b border-gray-700">
                    <th class="py-2 px-3">{{ __('Question') }}</th>
                    <th class="py-2 px-3">{{ __('Sort Order') }}</th>
                    <th class="py-2 px-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($faqs as $faq)
                    <tr class="border-b border-gray-800">
                        <td class="py-2 px-3">{{ $faq->question_en }}</td>
                        <td class="py-2 px-3">{{ $faq->sort_order }}</td>
                        <td class="py-2 px-3 text-right">
                            <a href="{{ route('admin.site-faqs.edit', $faq) }}" class="text-ccs-teal-light hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.site-faqs.destroy', $faq) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
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
