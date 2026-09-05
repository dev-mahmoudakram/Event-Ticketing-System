{{-- resources/views/admin/contact-messages/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Contact Messages').' — '.$event->name_en" />

    @if($contactMessages->isEmpty())
        <x-admin.empty-state :message="__('No messages yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Message') }}</th>
                    <th>{{ __('Received') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contactMessages as $message)
                    <tr>
                        <td>{{ $message->name }}</td>
                        <td>{{ $message->email }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($message->message, 80) }}</td>
                        <td>{{ $message->created_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif
@endsection
