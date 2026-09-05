{{-- resources/views/admin/reports/platform.blade.php --}}
@extends('layouts.admin')

@php
    $money = fn (int $amount) => number_format($amount).' '.$totals['currency'];
@endphp

@section('title', __('Platform report'))

@section('content')
    <x-admin.page-header :title="__('Platform report')" />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        @foreach([
            ['label' => __('Events hosted'), 'value' => number_format($totals['events'])],
            ['label' => __('Tickets requested'), 'value' => number_format($totals['tickets'])],
            ['label' => __('People who attended'), 'value' => number_format($totals['attendees'])],
            ['label' => __('Revenue'), 'value' => $money($totals['revenue'])],
        ] as $stat)
            <div class="adm-card p-6">
                <p class="font-display text-3xl font-extrabold text-hub-purple">{{ $stat['value'] }}</p>
                <p class="text-sm text-hub-dark/55 mt-1">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    @if($byEvent->isEmpty())
        <x-admin.empty-state :message="__('Once an event takes its first ticket request, its numbers appear here.')" />
    @else
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            {{-- Events side by side: the comparison the whole page exists for. --}}
            <section class="adm-card p-6 xl:col-span-2">
                <h2 class="font-display font-bold text-lg mb-1">{{ __('Events compared') }}</h2>
                <p class="text-sm text-hub-dark/55 mb-4">{{ __('How far each event carried its audience, from request to door.') }}</p>
                <div data-chart="{{ json_encode([
                    'type' => 'bar',
                    'height' => 340,
                    'categories' => $byEvent->pluck('name'),
                    'series' => [
                        ['name' => __('Requested'), 'data' => $byEvent->pluck('requested')],
                        ['name' => __('Paid'), 'data' => $byEvent->pluck('paid')],
                        ['name' => __('Attended'), 'data' => $byEvent->pluck('arrived')],
                    ],
                ]) }}"></div>
            </section>

            <section class="adm-card p-6">
                <h2 class="font-display font-bold text-lg mb-1">{{ __('Revenue by event') }}</h2>
                <p class="text-sm text-hub-dark/55 mb-4">{{ __('What each event collected, after discounts.') }}</p>
                <div data-chart="{{ json_encode([
                    'type' => 'bar',
                    'horizontal' => true,
                    'currency' => $totals['currency'],
                    'categories' => $byEvent->pluck('name'),
                    'series' => [['name' => __('Revenue'), 'data' => $byEvent->pluck('revenue')]],
                ]) }}"></div>
            </section>

            {{-- Attendance quality, as the three drops in a row rather than three lonely
                 percentages: each rate only means something beside the step before it. --}}
            <section class="adm-card p-6">
                <h2 class="font-display font-bold text-lg mb-1">{{ __('Where people drop off') }}</h2>
                <p class="text-sm text-hub-dark/55 mb-5">{{ __('Of everyone reviewed, how many were approved, paid, and turned up.') }}</p>

                <div class="flex flex-col gap-4">
                    @foreach([
                        ['label' => __('Approved after review'), 'rate' => $quality['approval_rate'], 'part' => $quality['approved'], 'whole' => $quality['reviewed']],
                        ['label' => __('Paid after approval'), 'rate' => $quality['payment_rate'], 'part' => $quality['paid'], 'whole' => $quality['approved']],
                        ['label' => __('Turned up after paying'), 'rate' => $quality['show_up_rate'], 'part' => $quality['arrived'], 'whole' => $quality['paid']],
                    ] as $step)
                        <div>
                            <div class="flex items-baseline justify-between gap-4 mb-1.5">
                                <span class="text-sm font-semibold">{{ $step['label'] }}</span>
                                <span class="text-sm">
                                    <strong class="text-hub-purple">{{ $step['rate'] }}%</strong>
                                    <span class="text-hub-dark/45">{{ number_format($step['part']) }} / {{ number_format($step['whole']) }}</span>
                                </span>
                            </div>
                            <span class="block h-2.5 rounded-full bg-hub-purple/12 overflow-hidden">
                                <span class="block h-full rounded-full bg-hub-purple" style="width: {{ $step['rate'] }}%"></span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="adm-card p-6 xl:col-span-2">
                <h2 class="font-display font-bold text-lg mb-1">{{ __('Growth') }}</h2>
                <p class="text-sm text-hub-dark/55 mb-4">{{ __('Requests and paid tickets over the last twelve months.') }}</p>
                <div data-chart="{{ json_encode([
                    'type' => 'area',
                    'height' => 320,
                    'categories' => $growth->pluck('label'),
                    'series' => [
                        ['name' => __('Requested'), 'data' => $growth->pluck('requested')],
                        ['name' => __('Paid'), 'data' => $growth->pluck('paid')],
                    ],
                ]) }}"></div>
            </section>

            <section class="adm-card p-6 xl:col-span-2">
                <h2 class="font-display font-bold text-lg mb-1">{{ __('Revenue over time') }}</h2>
                <p class="text-sm text-hub-dark/55 mb-4">{{ __('Collected each month, after discounts.') }}</p>
                <div data-chart="{{ json_encode([
                    'type' => 'bar',
                    'height' => 280,
                    'currency' => $totals['currency'],
                    'categories' => $growth->pluck('label'),
                    'series' => [['name' => __('Revenue'), 'data' => $growth->pluck('revenue')]],
                ]) }}"></div>
            </section>

            <section class="adm-card p-6 xl:col-span-2">
                <h2 class="font-display font-bold text-lg mb-1">{{ __('Audience reach') }}</h2>
                <p class="text-sm text-hub-dark/55 mb-4">{{ __('People who wrote in or subscribed, across the platform.') }}</p>
                <div data-chart="{{ json_encode([
                    'type' => 'line',
                    'height' => 280,
                    'categories' => $reach->pluck('label'),
                    'series' => [
                        ['name' => __('Contact messages'), 'data' => $reach->pluck('messages')],
                        ['name' => __('Newsletter subscribers'), 'data' => $reach->pluck('subscribers')],
                    ],
                ]) }}"></div>
            </section>
        </div>
    @endif
@endsection
