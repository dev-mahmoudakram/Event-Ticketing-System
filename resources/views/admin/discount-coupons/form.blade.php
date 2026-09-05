{{-- resources/views/admin/discount-coupons/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$coupon->exists ? __('Edit Coupon') : __('New Coupon')">
        <x-admin.button href="{{ route('admin.events.discount-coupons.index', $event) }}" variant="secondary">{{ __('Back') }}</x-admin.button>
    </x-admin.page-header>

    <form method="POST" action="{{ $coupon->exists ? route('admin.events.discount-coupons.update', [$event, $coupon]) : route('admin.events.discount-coupons.store', $event) }}">
        @csrf
        @if($coupon->exists) @method('PUT') @endif

        <x-admin.field name="code" :label="__('Code')" :value="old('code', $coupon->code)" placeholder="EARLYBIRD" required />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-admin.field type="select" name="type" :label="__('Discount type')">
                @foreach(\App\Enums\DiscountType::cases() as $type)
                    <option value="{{ $type->value }}" @selected(old('type', $coupon->type?->value) === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </x-admin.field>
            <x-admin.field type="number" name="value" :label="__('Amount (a percentage, or a price)')" :value="old('value', $coupon->value)" required />
        </div>

        <x-admin.field type="number" name="usage_limit" :label="__('Usage limit')" :value="old('usage_limit', $coupon->usage_limit)" placeholder="{{ __('Leave empty for unlimited') }}" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-admin.field type="date" name="starts_at" :label="__('Valid from')" :value="old('starts_at', $coupon->starts_at?->toDateString())" />
            <x-admin.field type="date" name="expires_at" :label="__('Valid until')" :value="old('expires_at', $coupon->expires_at?->toDateString())" />
        </div>

        <x-admin.field type="checkbox" name="is_active" :label="__('Active')" :checked="old('is_active', $coupon->is_active ?? true)" />

        @if($coupon->exists)
            <p class="text-sm text-hub-dark/60 mb-5">{{ __('Used :count times so far.', ['count' => $coupon->times_used]) }}</p>
        @endif

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
