{{-- resources/views/admin/discount-coupons/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Discount Coupons').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.discount-coupons.create', $event) }}">{{ __('New Coupon') }}</x-admin.button>
    </x-admin.page-header>

    <p class="text-sm text-hub-dark/60 mb-6 max-w-2xl">{{ __('Attendees type a code on the ticket request form. The discount is worked out then and stored on the ticket, so a later change to the coupon never rewrites what someone was charged.') }}</p>

    @if($coupons->isEmpty())
        <x-admin.empty-state :message="__('No coupons yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Code') }}</th>
                    <th>{{ __('Discount') }}</th>
                    <th>{{ __('Used') }}</th>
                    <th>{{ __('Window') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($coupons as $coupon)
                    <tr>
                        <td class="font-bold text-hub-purple"><bdi>{{ $coupon->code }}</bdi></td>
                        <td>
                            @if($coupon->type === \App\Enums\DiscountType::Percentage)
                                {{ $coupon->value }}%
                            @else
                                {{ $coupon->value }} {{ $event->ticketTypes->first()?->currency }}
                            @endif
                        </td>
                        <td>
                            {{ $coupon->times_used }}@if($coupon->usage_limit) / {{ $coupon->usage_limit }} @endif
                        </td>
                        <td class="text-hub-dark/60 text-sm">
                            @if($coupon->starts_at || $coupon->expires_at)
                                {{ $coupon->starts_at?->translatedFormat('j M Y') ?? '—' }} – {{ $coupon->expires_at?->translatedFormat('j M Y') ?? '—' }}
                            @else
                                {{ __('Always') }}
                            @endif
                        </td>
                        <td>
                            @if($coupon->isRedeemable())
                                <span class="text-hub-purple font-semibold text-sm">{{ __('Redeemable') }}</span>
                            @else
                                <span class="text-hub-dark/45 text-sm">{{ __('Not redeemable') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.events.discount-coupons.edit', [$event, $coupon]) }}" class="text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.discount-coupons.destroy', [$event, $coupon]) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure? This cannot be undone.') }}')">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="ms-2">{{ __('Delete') }}</x-admin.button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
