@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('No access yet')" />

    <div class="adm-card p-6 max-w-xl">
        <p class="text-hub-dark/75 leading-relaxed">{{ __('Your role has no access yet. Ask an admin to give it some.') }}</p>
    </div>
@endsection
