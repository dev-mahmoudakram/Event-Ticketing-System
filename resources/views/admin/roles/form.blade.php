@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$role->exists ? __('Edit Role') : __('New Role')" />

    @php $chosen = old('permissions', $role->permissions ?? []); @endphp

    <form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="max-w-5xl">
        @csrf
        @if($role->exists) @method('PUT') @endif

        <div class="adm-card p-6 mb-6 max-w-xl">
            <x-admin.field name="name" label="{{ __('Role name') }}" :value="old('name', $role->name)" required />
        </div>

        <h2 class="font-display font-bold text-lg text-hub-purple mb-1">{{ __('What this role can open') }}</h2>
        <p class="text-sm text-hub-dark/60 mb-4">{{ __('Each tick lets the role view and act in that section, for every event. Staff and Roles stay with admins.') }}</p>

        @if($errors->has('permissions') || $errors->has('permissions.*'))
            <p class="text-sm mb-4 text-[#b42318]">{{ $errors->first('permissions') ?: collect($errors->get('permissions.*'))->flatten()->first() }}</p>
        @endif

        <div class="grid gap-5 md:grid-cols-2">
            @foreach(\App\Enums\Permission::grouped() as $group => $permissions)
                <fieldset class="adm-card p-5">
                    <legend class="sr-only">{{ $group }}</legend>
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h3 class="font-display font-bold text-hub-purple" aria-hidden="true">{{ $group }}</h3>
                        <button type="button"
                            class="text-xs font-bold text-hub-purple hover:underline"
                            x-data
                            @click="const boxes = [...$el.closest('fieldset').querySelectorAll('input[type=checkbox]')]; const all = boxes.every((box) => box.checked); boxes.forEach((box) => { box.checked = ! all })">
                            {{ __('Select all') }}
                        </button>
                    </div>
                    @foreach($permissions as $permission)
                        <label class="flex items-center gap-2.5 py-1.5 text-sm">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->value }}" @checked(in_array($permission->value, $chosen, true)) class="w-4 h-4 rounded border-hub-purple/30 text-hub-purple focus:ring-hub-purple/40">
                            {{ $permission->label() }}
                        </label>
                    @endforeach
                </fieldset>
            @endforeach
        </div>

        <x-admin.button type="submit" class="mt-6">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
