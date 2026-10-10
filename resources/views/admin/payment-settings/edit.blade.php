{{-- resources/views/admin/payment-settings/edit.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Payment Settings')" />

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif

    @if(! $settings->isComplete())
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-[#b42318]" role="alert">
            {{ __('Payments are not set up yet. Approving a ticket request will fail until these settings are complete.') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.payment-settings.update') }}" class="max-w-xl">
        @csrf
        @method('PUT')

        <div class="adm-card p-6 mb-6">
            <p class="text-sm text-hub-dark/60 mb-5">{{ __('Payments are taken through Kashier. Copy these values from the Integrations page of your Kashier dashboard. Test and live mode each have their own keys.') }}</p>

            <x-admin.field type="select" name="mode" label="{{ __('Mode') }}" required>
                @foreach(\App\Enums\PaymentMode::cases() as $mode)
                    <option value="{{ $mode->value }}" @selected(old('mode', $settings->mode?->value) === $mode->value)>{{ $mode->label() }}</option>
                @endforeach
            </x-admin.field>

            <x-admin.field name="merchant_id" label="{{ __('Merchant ID') }}" :value="old('merchant_id', $settings->merchant_id)" placeholder="MID-12345-678" dir="ltr" required />

            @foreach(['api_key' => __('Payment API key'), 'secret_key' => __('Secret key')] as $key => $label)
                <x-admin.field type="password" :name="$key" :label="$label" dir="ltr"
                    :placeholder="filled($settings->{$key}) ? __('Saved, ending in :last. Leave blank to keep it.', ['last' => mb_substr($settings->{$key}, -4)]) : null"
                    :required="blank($settings->{$key})" />
            @endforeach
        </div>

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
