{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Dashboard')" />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="adm-card p-6">
            <p class="font-display text-3xl font-bold">{{ $totalEvents }}</p>
            <p class="text-hub-dark/60 text-sm mt-1">{{ __('Total Events') }}</p>
        </div>
        <div class="adm-card p-6">
            <p class="font-display text-3xl font-bold text-hub-purple">{{ $publishedEvents }}</p>
            <p class="text-hub-dark/60 text-sm mt-1">{{ __('Published') }}</p>
        </div>
        <div class="adm-card p-6">
            <p class="font-display text-3xl font-bold text-hub-dark/60">{{ $draftEvents }}</p>
            <p class="text-hub-dark/60 text-sm mt-1">{{ __('Draft') }}</p>
        </div>
    </div>

    <div class="mt-6">
        <x-admin.button href="{{ route('admin.events.index') }}">{{ __('View Events') }}</x-admin.button>
    </div>
@endsection
