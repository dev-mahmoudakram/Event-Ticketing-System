@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('No access yet')" />

    <div class="adm-card p-6 max-w-xl">
        {{-- A role with sections that all belong to an event lands here before the first event
             exists; that isn't a missing permission, so it gets its own message. --}}
        @php $hasAnySection = collect(\App\Enums\Permission::cases())->contains(fn ($permission) => auth()->user()->hasPermission($permission)); @endphp
        <p class="text-hub-dark/75 leading-relaxed">
            {{ $hasAnySection
                ? __('There are no events yet. Your sections will appear here once an admin creates one.')
                : __('Your role has no access yet. Ask an admin to give it some.') }}
        </p>
    </div>
@endsection
