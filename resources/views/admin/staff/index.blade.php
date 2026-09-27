@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Staff')">
        <x-admin.button href="{{ route('admin.staff.create') }}">{{ __('Add Staff Member') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-300" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <p class="mb-5 text-sm text-hub-dark/60">{{ __('What each person can open depends on their role. Admins can use every page, including Staff and Roles.') }}</p>

    <x-admin.table>
        <thead>
            <tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th>{{ __('Role') }}</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($staff as $member)
                <tr>
                    <td>{{ $member->name }}@if($member->is(auth()->user())) <span class="text-hub-dark/50">({{ __('you') }})</span>@endif</td>
                    <td>{{ $member->email }}</td>
                    <td>{{ $member->role?->name }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.staff.edit', $member) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                        @unless($member->is(auth()->user()))
                            <form method="POST" action="{{ route('admin.staff.destroy', $member) }}" class="inline" data-confirm="{{ __('Are you sure? This cannot be undone.') }}">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="ms-2">{{ __('Remove') }}</x-admin.button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>
@endsection
