{{-- resources/views/admin/auth/login.blade.php --}}
@extends('layouts.app')

@section('title', __('Creators Hub Admin'))

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('bodyClass', 'adm-body')

@section('content')
    <main class="min-h-screen grid lg:grid-cols-2">
        {{-- The panel that does the work. --}}
        <div class="flex items-center justify-center px-6 py-16">
            <div class="w-full max-w-sm">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 mb-10">
                    <img src="{{ asset('images/creators-hub/mark.png') }}" alt="" aria-hidden="true" class="h-8 w-auto">
                    <span class="font-display font-extrabold text-lg tracking-tight text-hub-purple">Creators Hub</span>
                </a>

                <h1 class="font-display text-[clamp(1.7rem,3vw,2.25rem)] font-extrabold leading-tight tracking-tight text-hub-dark mb-2">
                    {{ __('Sign in to the admin.') }}
                </h1>
                <p class="text-sm text-hub-dark/60 mb-8">{{ __('Events, tickets and everything the site shows.') }}</p>

                @if($errors->any())
                    <div class="mb-6 rounded-2xl border border-[#b42318]/25 bg-[#b42318]/5 px-4 py-3">
                        <p class="text-sm font-semibold text-[#b42318]">{{ $errors->first() }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf
                    <x-admin.field type="email" name="email" :label="__('Email')" :value="old('email')" required />
                    <x-admin.field type="password" name="password" :label="__('Password')" required />
                    <x-admin.button type="submit" class="w-full mt-2">{{ __('Log in') }}</x-admin.button>
                </form>
            </div>
        </div>

        {{-- A quiet brand panel rather than a photograph the platform does not have. --}}
        <div class="hidden lg:flex flex-col justify-between p-14 bg-hub-purple text-white">
            {{-- self-start, or the column's stretch alignment pulls the mark out of shape. --}}
            <img src="{{ asset('images/creators-hub/mark-white.png') }}" alt="" aria-hidden="true" class="h-10 w-auto self-start">

            <p class="font-display text-[clamp(1.6rem,2.4vw,2.4rem)] font-extrabold leading-[1.25] tracking-tight max-w-md">
                {{ __('Everything the events and the site show is edited from here.') }}
            </p>

            <p class="text-sm text-white/55">&copy; {{ now()->year }} Creators Hub</p>
        </div>
    </main>
@endsection
