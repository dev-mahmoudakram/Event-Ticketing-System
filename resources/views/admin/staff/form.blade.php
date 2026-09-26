@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$user->exists ? __('Edit Staff Member') : __('Add Staff Member')" />

    <form method="POST" action="{{ $user->exists ? route('admin.staff.update', $user) : route('admin.staff.store') }}" class="adm-card p-6 max-w-xl">
        @csrf
        @if($user->exists) @method('PUT') @endif

        <x-admin.field name="name" label="{{ __('Name') }}" :value="old('name', $user->name)" required />
        <x-admin.field type="email" name="email" label="{{ __('Email') }}" :value="old('email', $user->email)" required />

        <x-admin.field type="select" name="role" label="{{ __('Role') }}" required>
            @foreach(\App\Enums\UserRole::cases() as $role)
                <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </x-admin.field>

        <x-admin.field type="password" name="password" label="{{ $user->exists ? __('New password (leave blank to keep the current one)') : __('Password') }}" :required="! $user->exists" />
        <x-admin.field type="password" name="password_confirmation" label="{{ __('Confirm password') }}" :required="! $user->exists" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
