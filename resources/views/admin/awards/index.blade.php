{{-- resources/views/admin/awards/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Awards').' — '.(app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en)">
        @if(Route::has('admin.events.award-votes.index'))
            <x-admin.button href="{{ route('admin.events.award-votes.index', $event) }}" variant="secondary">{{ __('Votes') }}</x-admin.button>
        @endif
        <x-admin.button href="{{ route('admin.events.awards.create', $event) }}">{{ __('New Award') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.events.awards.settings', $event) }}" class="mb-8 max-w-2xl rounded border border-hub-purple/15 bg-white p-5">
        @csrf @method('PUT')
        <h2 class="mb-1 font-display text-base font-bold text-hub-purple">{{ __('Voting window') }}</h2>
        <p class="mb-4 text-sm text-hub-dark/60">{{ __('"Follow the schedule" opens voting between the two dates below. With no dates it stays closed.') }}</p>

        <x-admin.field type="select" name="voting_mode" :label="__('Voting')" required>
            @foreach($votingModes as $mode)
                <option value="{{ $mode->value }}" @selected(old('voting_mode', $event->voting_mode?->value ?? 'schedule') === $mode->value)>{{ $mode->label() }}</option>
            @endforeach
        </x-admin.field>
        <x-admin.field type="datetime-local" name="voting_opens_at" :label="__('Voting opens')" :value="old('voting_opens_at', $event->voting_opens_at?->format('Y-m-d\TH:i'))" />
        <x-admin.field type="datetime-local" name="voting_closes_at" :label="__('Voting closes')" :value="old('voting_closes_at', $event->voting_closes_at?->format('Y-m-d\TH:i'))" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>

    @if($awards->isEmpty())
        <x-admin.empty-state :message="__('No awards yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr><th class="w-10"></th><th>{{ __('Name') }}</th><th>{{ __('Nominees') }}</th><th>{{ __('Votes') }}</th><th></th></tr>
            </thead>
            <tbody data-sortable data-sortable-url="{{ route('admin.events.awards.reorder', $event) }}" data-sortable-error="{{ __('The new order could not be saved. Reload and try again.') }}">
                @foreach($awards as $award)
                    <tr data-sortable-item="{{ $award->id }}">
                        <td><button type="button" class="adm-drag-handle" aria-label="{{ __('Drag to reorder') }}" title="{{ __('Drag to reorder') }}">⠿</button></td>
                        <td class="font-semibold">{{ $award->name() }}</td>
                        <td data-nominee-count>{{ $award->nominees_count }}</td>
                        <td data-vote-count>{{ $award->counted_votes_count }}</td>
                        <td class="text-end whitespace-nowrap">
                            <a href="{{ route('admin.events.awards.nominees.index', [$event, $award]) }}" class="text-hub-purple hover:underline">{{ __('Nominees') }}</a>
                            <a href="{{ route('admin.events.awards.edit', [$event, $award]) }}" class="ms-3 text-hub-purple hover:underline">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('admin.events.awards.destroy', [$event, $award]) }}" class="inline" data-confirm="{{ $award->counted_votes_count > 0 ? __('This also deletes its votes. Are you sure?') : __('Are you sure? This cannot be undone.') }}">
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
