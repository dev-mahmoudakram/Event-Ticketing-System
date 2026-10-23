{{-- resources/views/admin/award-votes/index.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="__('Votes').' — '.(app()->getLocale() === 'ar' ? $event->name_ar : $event->name_en)">
        <x-admin.button href="{{ route('admin.events.awards.index', $event) }}" variant="secondary">{{ __('Awards') }}</x-admin.button>
        <x-admin.button href="{{ route('admin.events.award-votes.export', [$event, 'award' => $selectedAward?->id]) }}" variant="secondary">{{ __('Download votes (CSV)') }}</x-admin.button>
    </x-admin.page-header>

    @if(session('success'))
        <div class="mb-4 rounded border border-hub-purple-light/40 bg-hub-purple-light/10 px-4 py-3 text-sm text-hub-purple" role="status">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded border border-red-400/40 bg-red-400/10 px-4 py-3 text-sm text-red-600" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('admin.events.award-votes.index', $event) }}" class="mb-4 flex flex-wrap items-end gap-3 text-sm">
        <div>
            <label for="award" class="adm-label">{{ __('Award') }}</label>
            <select name="award" id="award" class="adm-input">
                <option value="">{{ __('All awards') }}</option>
                @foreach($awards as $award)
                    <option value="{{ $award->id }}" @selected($selectedAward?->id === $award->id)>{{ $award->name() }}</option>
                @endforeach
            </select>
        </div>
        @if($selectedAward)
            <div>
                <label for="nominee" class="adm-label">{{ __('Nominee') }}</label>
                <select name="nominee" id="nominee" class="adm-input">
                    <option value="">{{ __('All nominees') }}</option>
                    @foreach($selectedAward->nominees as $nominee)
                        <option value="{{ $nominee->id }}" @selected($selectedNominee?->id === $nominee->id)>{{ $nominee->name() }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <x-admin.button type="submit" variant="secondary">{{ __('Filter') }}</x-admin.button>
    </form>

    <p class="mb-4 text-sm text-hub-dark/60">{{ __('An IP is flagged when it casts :count or more counted votes in one award within :minutes minutes. Flags are for review only; a shared network can be genuine.', ['count' => \App\Services\SuspiciousVotes::THRESHOLD, 'minutes' => \App\Services\SuspiciousVotes::WINDOW_MINUTES]) }} {{ __('Removed votes can be restored; they count again.') }}</p>

    @if($votes->isEmpty())
        <x-admin.empty-state :message="__('No votes yet.')" />
    @else
        <x-admin.table>
            <thead>
                <tr>
                    <th>{{ __('Email') }}</th>
                    <th>{{ __('Award') }}</th>
                    <th>{{ __('Nominee') }}</th>
                    <th>{{ __('Time') }}</th>
                    <th>{{ __('IP') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($votes as $vote)
                    @php $isSuspicious = $suspicious->isSuspicious($vote); @endphp
                    <tr class="align-top {{ $vote->isRemoved() ? 'opacity-60' : '' }}">
                        <td dir="ltr" class="text-start">{{ $vote->email }}</td>
                        <td>{{ $vote->award->name() }}</td>
                        <td>{{ $vote->nominee?->name() }}</td>
                        <td dir="ltr" class="whitespace-nowrap text-start">{{ $vote->created_at?->format('Y-m-d H:i') }}</td>
                        <td dir="ltr" class="whitespace-nowrap text-start">
                            {{ $vote->ip_address }}
                            @if($isSuspicious)
                                <span class="ms-1 inline-block rounded bg-red-500/10 px-2 py-0.5 text-xs font-semibold text-red-600" data-suspicious>{{ __('Suspicious') }}</span>
                            @endif
                        </td>
                        <td>{{ $vote->isRemoved() ? __('Removed') : __('Counted') }}</td>
                        <td class="text-end whitespace-nowrap">
                            @unless($vote->isRemoved())
                                <form method="POST" action="{{ route('admin.events.award-votes.destroy', [$event, $vote]) }}" class="inline" data-confirm="{{ __('Remove this vote? It stops counting and the email still cannot vote again in this award.') }}">
                                    @csrf @method('DELETE')
                                    <x-admin.button type="submit" variant="danger">{{ __('Remove') }}</x-admin.button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.events.award-votes.restore', [$event, $vote]) }}" class="inline" data-confirm="{{ __('Restore this vote? It will count again.') }}">
                                    @csrf @method('PATCH')
                                    <x-admin.button type="submit" variant="secondary">{{ __('Restore') }}</x-admin.button>
                                </form>
                            @endunless
                            @if($isSuspicious)
                                <form method="POST" action="{{ route('admin.events.award-votes.destroy-ip', $event) }}" class="inline" data-confirm="{{ __('Remove every counted vote from :ip in this award?', ['ip' => $vote->ip_address]) }}">
                                    @csrf
                                    <input type="hidden" name="award" value="{{ $vote->award_id }}">
                                    <input type="hidden" name="ip" value="{{ $vote->ip_address }}">
                                    <x-admin.button type="submit" variant="danger" class="ms-2">{{ __('Remove all from this IP') }}</x-admin.button>
                                </form>
                            @endif
                            @if($vote->ip_address !== null && isset($restorableIps[$vote->award_id.'|'.$vote->ip_address]))
                                <form method="POST" action="{{ route('admin.events.award-votes.restore-ip', $event) }}" class="inline" data-confirm="{{ __('Restore every removed vote from :ip in this award?', ['ip' => $vote->ip_address]) }}">
                                    @csrf
                                    <input type="hidden" name="award" value="{{ $vote->award_id }}">
                                    <input type="hidden" name="ip" value="{{ $vote->ip_address }}">
                                    <x-admin.button type="submit" variant="secondary" class="ms-2">{{ __('Restore all from this IP') }}</x-admin.button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-admin.table>
    @endif

    <x-admin.pagination :paginator="$votes" />
@endsection
