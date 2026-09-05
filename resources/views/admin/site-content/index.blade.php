{{-- resources/views/admin/site-content/index.blade.php --}}
@extends('layouts.admin')

@section('title', __('Creators Hub Content'))

@section('content')
    <x-admin.page-header :title="__('Creators Hub Content')">
        <x-admin.button href="{{ route('home') }}" variant="secondary" target="_blank" rel="noopener">{{ __('View site') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-hub-dark/60 mb-8 max-w-2xl">{{ __('Every piece of text on the Creators Hub landing page. Anything you leave blank keeps the wording the page ships with, so you only need to fill in what you want to change.') }}</p>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($sections as $key => $definition)
            @php
                $total = count($definition['fields']);
                $edited = $filled[$key] ?? 0;
            @endphp

            <a href="{{ route('admin.site-content.edit', $key) }}" class="adm-section-card">
                {{-- The section's own image where it has one, so the list can be scanned by
                     eye instead of read line by line. --}}
                <div class="adm-section-card-media">
                    @if(isset($images[$key]))
                        <img src="{{ $images[$key] }}" alt="" loading="lazy">
                    @else
                        <span class="adm-section-card-glyph" aria-hidden="true">{{ mb_substr($definition['label'], 0, 1) }}</span>
                    @endif
                </div>

                <div class="adm-section-card-body">
                    <h2 class="font-display font-bold mb-1">{{ $definition['label'] }}</h2>
                    <p class="text-sm text-hub-dark/45">{{ $definition['description'] }}</p>

                    @if($total > 0)
                        <p class="text-xs mt-3 {{ $edited > 0 ? 'text-hub-purple font-bold' : 'text-hub-dark/40' }}">
                            @if($edited > 0)
                                {{ __(':filled of :total edited', ['filled' => $edited, 'total' => $total]) }}
                            @else
                                {{ __('Using the wording it ships with') }}
                            @endif
                        </p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>
@endsection
