{{-- resources/views/admin/reports/show.blade.php --}}
@extends('layouts.admin')

@php
    $money = fn (int $amount) => number_format($amount).' '.$revenue['currency'];
    $issued = $checkIns['issued'];
@endphp

@section('content')
    <x-admin.page-header :title="__('Report').' — '.$event->name_en">
        <x-admin.button href="{{ route('admin.events.reports.export', $event) }}" variant="secondary">{{ __('Download attendee list (CSV)') }}</x-admin.button>
    </x-admin.page-header>

    {{-- The funnel first: the four numbers anyone asks for before any breakdown. --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach([
            ['label' => __('Requested'), 'value' => $funnel['requested']],
            ['label' => __('Approved'), 'value' => $funnel['approved']],
            ['label' => __('Paid'), 'value' => $funnel['paid']],
            ['label' => __('Checked in'), 'value' => $funnel['checked_in']],
        ] as $stat)
            <div class="adm-card p-6">
                <p class="font-display text-3xl font-extrabold text-hub-purple">{{ number_format($stat['value']) }}</p>
                <p class="text-sm text-hub-dark/55 mt-1">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <section class="adm-card p-6">
            <h2 class="font-display font-bold text-lg mb-4">{{ __('Tickets by status') }}</h2>
            <ul class="flex flex-col gap-2.5">
                @foreach($statuses as $row)
                    <li class="flex items-center justify-between gap-4 text-sm">
                        <span class="{{ $row['count'] === 0 ? 'text-hub-dark/40' : '' }}">{{ $row['label'] }}</span>
                        <span class="font-bold {{ $row['count'] === 0 ? 'text-hub-dark/40' : 'text-hub-purple' }}">{{ number_format($row['count']) }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="adm-card p-6">
            <h2 class="font-display font-bold text-lg mb-4">{{ __('Revenue') }}</h2>

            <div class="flex flex-col gap-3 mb-6">
                <div class="flex items-baseline justify-between gap-4">
                    <span class="text-sm text-hub-dark/60">{{ __('Collected') }}</span>
                    <span class="font-display text-2xl font-extrabold text-hub-purple">{{ $money($revenue['collected']) }}</span>
                </div>
                <div class="flex items-baseline justify-between gap-4 text-sm">
                    <span class="text-hub-dark/60">{{ __('Given away in discounts') }}</span>
                    <span class="font-bold">{{ $money($revenue['discounted']) }}</span>
                </div>
                <div class="flex items-baseline justify-between gap-4 text-sm">
                    <span class="text-hub-dark/60">{{ __('Approved but not yet paid') }}</span>
                    <span class="font-bold">{{ $money($revenue['outstanding']) }}</span>
                </div>
            </div>

            @if($ticketTypes->isNotEmpty())
                <h3 class="text-sm font-bold text-hub-dark/70 mb-2">{{ __('By ticket type') }}</h3>
                <ul class="flex flex-col gap-2 text-sm">
                    @foreach($ticketTypes as $type)
                        <li class="flex items-center justify-between gap-4">
                            <span>{{ $type['name'] }} <span class="text-hub-dark/45">&times;{{ $type['sold'] }}</span></span>
                            <span class="font-bold">{{ $money($type['revenue']) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="adm-card p-6">
            <h2 class="font-display font-bold text-lg mb-1">{{ __('On the day') }}</h2>
            <p class="text-sm text-hub-dark/55 mb-4">
                {{ __(':arrived of :issued issued tickets have arrived.', ['arrived' => number_format($checkIns['arrived']), 'issued' => number_format($issued)]) }}
            </p>

            @if($issued > 0)
                @php $rate = (int) round($checkIns['arrived'] / $issued * 100); @endphp
                <div class="h-2.5 rounded-full bg-hub-purple/12 overflow-hidden mb-6" role="img" aria-label="{{ __(':percent% checked in', ['percent' => $rate]) }}">
                    <div class="h-full rounded-full bg-hub-purple" style="width: {{ $rate }}%"></div>
                </div>
            @endif

            @if($checkIns['by_hour']->isNotEmpty())
                <h3 class="text-sm font-bold text-hub-dark/70 mb-2">{{ __('Arrivals by hour') }}</h3>
                @php $busiest = $checkIns['by_hour']->max('count'); @endphp
                <ul class="flex flex-col gap-2">
                    @foreach($checkIns['by_hour'] as $slot)
                        <li class="flex items-center gap-3 text-sm">
                            <span class="w-32 shrink-0 text-hub-dark/60" dir="ltr">{{ $slot['hour'] }}</span>
                            <span class="flex-1 h-2 rounded-full bg-hub-purple/12 overflow-hidden">
                                <span class="block h-full rounded-full bg-hub-purple" style="width: {{ (int) round($slot['count'] / $busiest * 100) }}%"></span>
                            </span>
                            <span class="w-10 text-end font-bold">{{ $slot['count'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @elseif($issued > 0)
                <p class="text-sm text-hub-dark/45">{{ __('Nobody has been checked in yet.') }}</p>
            @endif
        </section>

        @if($coupons->isNotEmpty())
            <section class="adm-card p-6">
                <h2 class="font-display font-bold text-lg mb-4">{{ __('Coupons used') }}</h2>
                <ul class="flex flex-col gap-2.5 text-sm">
                    @foreach($coupons as $coupon)
                        <li class="flex items-center justify-between gap-4">
                            <span class="font-bold text-hub-purple"><bdi>{{ $coupon['code'] }}</bdi></span>
                            <span class="text-hub-dark/60">{{ trans_choice('{1} :count ticket|[2,*] :count tickets', $coupon['used'], ['count' => $coupon['used']]) }} &middot; {{ $money($coupon['discounted']) }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
@endsection
