{{-- resources/views/admin/newsletter-subscribers/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Newsletter Subscribers').' — '.$event->name_en" />

    @if($newsletterSubscribers->isEmpty())
        <x-admin.empty-state :message="__('No subscribers yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Subscribed') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($newsletterSubscribers as $subscriber)
                    <tr>
                        <td>{{ $subscriber->email }}</td>
                        <td>{{ $subscriber->created_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
