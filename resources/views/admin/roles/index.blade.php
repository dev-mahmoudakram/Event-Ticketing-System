@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Roles')">
        <x-admin.button href="{{ route('admin.roles.create') }}">{{ __('New Role') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-[#b42318]" role="alert">{{ session('error') }}</div>
    @endif

    <p class="mb-5 text-sm text-hub-dark/60">{{ __('A role decides which sections its staff can open. Give each person a role on the Staff page.') }}</p>

    <x-admin.table>
        <thead>
            <tr><th>{{ __('Role') }}</th><th>{{ __('Staff') }}</th><th>{{ __('Sections') }}</th><th></th></tr>
        </thead>
        <tbody>
            @foreach($roles as $role)
                <tr>
                    <td class="font-semibold">
                        {{ $role->name }}
                        @if($role->is_system)
                            <span class="ms-2 inline-flex rounded-full bg-hub-lavender px-2.5 py-0.5 text-xs font-bold text-hub-purple">{{ __('Locked') }}</span>
                        @endif
                    </td>
                    <td>{{ $role->users_count }}</td>
                    <td>
                        @if($role->is_system)
                            {{ __('Everything, including Staff and Roles') }}
                        @else
                            {{ trans_choice('{0} No sections|{1} :count section|[2,*] :count sections', count($role->permissions ?? []), ['count' => count($role->permissions ?? [])]) }}
                        @endif
                    </td>
                    <td class="text-end">
                        @unless($role->is_system)
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline" data-confirm="{{ __('Are you sure? This cannot be undone.') }}">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="ml-2">{{ __('Delete') }}</x-admin.button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </x-admin.table>
@endsection
