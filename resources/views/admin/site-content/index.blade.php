{{-- resources/views/admin/site-content/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Creators Hub Content')" />

    <p class="text-sm text-gray-400 mb-8 max-w-2xl">{{ __('Every piece of text on the Creators Hub landing page. Anything you leave blank keeps the wording the page ships with, so you only need to fill in what you want to change.') }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($sections as $key => $definition)
            <a href="{{ route('admin.site-content.edit', $key) }}" class="block rounded-lg border border-gray-800 p-5 transition-colors hover:border-ccs-teal-light">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="font-display font-bold mb-1">{{ $definition['label'] }}</h2>
                        <p class="text-sm text-gray-500 max-w-sm">{{ $definition['description'] }}</p>
                    </div>
                    <span class="shrink-0 text-xs text-gray-500">
                        {{ trans_choice(':filled of :total edited|:filled of :total edited', count($definition['fields']), ['filled' => $filled[$key] ?? 0, 'total' => count($definition['fields'])]) }}
                    </span>
                </div>
            </a>
        @endforeach
    </div>
@endsection
