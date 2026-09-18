{{-- resources/views/admin/request-form-fields/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$requestField->exists ? __('Edit Request Field') : __('New Request Field')" />

    <form
        method="POST"
        action="{{ $requestField->exists ? route('admin.events.request-form-fields.update', [$event, $requestField]) : route('admin.events.request-form-fields.store', $event) }}"
        x-data="{ type: '{{ old('type', $requestField->type?->value ?? '') }}' }"
    >
        @csrf
        @if($requestField->exists) @method('PUT') @endif

        <x-admin.field type="select" name="type" label="{{ __('Type') }}" x-model="type">
            @foreach(\App\Enums\TicketRequestFieldType::cases() as $type)
                <option value="{{ $type->value }}" @selected(old('type', $requestField->type?->value) === $type->value)>{{ ucfirst(str_replace('_', ' ', $type->value)) }}</option>
            @endforeach
        </x-admin.field>

        <div x-show="type === 'social_link'" x-cloak>
            <x-admin.field type="select" name="platform" label="{{ __('Platform') }}">
                <option value="">{{ __('Select a platform') }}</option>
                @foreach(\App\Support\SocialPlatforms::all() as $key => $platform)
                    <option value="{{ $key }}" @selected(old('platform', $requestField->platform) === $key)>{{ $platform['label'] }}</option>
                @endforeach
            </x-admin.field>

            <x-admin.field type="checkbox" name="show_follower_count" label="{{ __('Ask for follower count') }}" :checked="old('show_follower_count', $requestField->show_follower_count ?? false)" />
        </div>

        <x-admin.bilingual-field name="label" label="{{ __('Label') }}" :value-ar="old('label_ar', $requestField->label_ar)" :value-en="old('label_en', $requestField->label_en)" />

        <x-admin.field type="checkbox" name="is_required" label="{{ __('Required') }}" :checked="old('is_required', $requestField->is_required ?? false)" />
        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $requestField->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
