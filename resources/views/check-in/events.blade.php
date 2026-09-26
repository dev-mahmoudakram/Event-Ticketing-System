@extends('layouts.admin')

@section('title', __('Registration desk'))

@section('content')
    <x-admin.page-header :title="__('Registration desk')" />

    <p class="mb-5 text-sm text-hub-dark/60">{{ __('Choose the event you are checking people in for.') }}</p>

    @if($events->isEmpty())
        <x-admin.empty-state :message="__('No events yet.')" />
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach($events as $event)
                <a href="{{ route('check-in.index', $event) }}" class="adm-card flex items-center justify-between gap-4 p-5 transition-colors hover:border-hub-purple/40">
                    <span>
                        <span class="block font-display font-bold">{{ app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en }}</span>
                        <span class="block text-sm text-hub-dark/55">{{ $event->start_date?->translatedFormat('j F Y') }}</span>
                    </span>
                    <span class="text-hub-purple" aria-hidden="true">&rsaquo;</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
