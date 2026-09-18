{{-- resources/views/admin/ticket-types/form.blade.php --}}
@extends('layouts.admin')

@section('content')
    <x-admin.page-header :title="$ticketType->exists ? __('Edit Ticket Type') : __('New Ticket Type')" />

    @php
        // The repeater posts back the same newline-joined shape the controller has always
        // parsed (extractFeatures() in TicketTypeController), so no server change was needed
        // to swap the plain textarea for this UI — only what fills those two hidden fields.
        $existingFeatures = $ticketType->exists ? $ticketType->features : collect();
        $initialPoints = old('features_ar')
            ? collect(explode("\n", (string) old('features_ar')))->zip(explode("\n", (string) old('features_en')))
                ->map(fn ($pair) => ['ar' => $pair[0] ?? '', 'en' => $pair[1] ?? ''])
                ->values()
            : $existingFeatures->map(fn ($feature) => ['ar' => $feature->text_ar, 'en' => $feature->text_en])->values();
    @endphp

    <form
        method="POST"
        action="{{ $ticketType->exists ? route('admin.events.ticket-types.update', [$event, $ticketType]) : route('admin.events.ticket-types.store', $event) }}"
        x-data="{
            points: @js($initialPoints->isNotEmpty() ? $initialPoints : [['ar' => '', 'en' => '']]),
            add() { this.points.push({ ar: '', en: '' }); },
            remove(index) {
                this.points.splice(index, 1);
                if (this.points.length === 0) this.add();
            },
            isPopular: @js(old('is_popular', $ticketType->is_popular ?? false)),
        }"
        @submit="
            $refs.featuresAr.value = points.map(p => p.ar.trim()).filter(Boolean).join('\n');
            $refs.featuresEn.value = points.map(p => p.en.trim()).filter(Boolean).join('\n');
        "
    >
        @csrf
        @if($ticketType->exists) @method('PUT') @endif

        <x-admin.bilingual-field name="name" label="{{ __('Name') }}" :value-ar="old('name_ar', $ticketType->name_ar)" :value-en="old('name_en', $ticketType->name_en)" />
        <x-admin.bilingual-field type="richtext" name="description" label="{{ __('Description') }}" :value-ar="old('description_ar', $ticketType->description_ar)" :value-en="old('description_en', $ticketType->description_en)" />

        {{-- One point at a time: fill in the Arabic and English text for the first item, then
             press "Add point" for the next. This is the order the list renders on the public
             card, top to bottom. --}}
        <div class="mb-5">
            <label class="adm-label">{{ __('Points (one per line)') }}</label>

            <div class="flex flex-col gap-3">
                <template x-for="(point, index) in points" :key="index">
                    <div class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-start">
                        <input type="text" x-model="point.ar" dir="rtl" class="adm-input" placeholder="{{ __('Point text (Arabic)') }}">
                        <input type="text" x-model="point.en" class="adm-input" placeholder="{{ __('Point text (English)') }}">
                        <button type="button" @click="remove(index)" class="adm-btn adm-btn-secondary shrink-0" aria-label="{{ __('Remove this point') }}">&times;</button>
                    </div>
                </template>
            </div>

            <button type="button" @click="add()" class="adm-btn adm-btn-secondary mt-3">+ {{ __('Add point') }}</button>

            <input type="hidden" name="features_ar" x-ref="featuresAr">
            <input type="hidden" name="features_en" x-ref="featuresEn">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-admin.field type="number" name="price" label="{{ __('Price') }}" :value="old('price', $ticketType->price)" />
            <x-admin.field type="number" name="original_price" label="{{ __('Original Price (before sale, optional)') }}" :value="old('original_price', $ticketType->original_price)" />
            <x-admin.field name="currency" label="{{ __('Currency') }}" :value="old('currency', $ticketType->currency ?? 'EGP')" />
        </div>

        <x-admin.field type="number" name="workshop_slot_count" label="{{ __('Workshop Slots (blank = unlimited)') }}" :value="old('workshop_slot_count', $ticketType->workshop_slot_count)" />

        <x-admin.field type="checkbox" name="is_active" label="{{ __('Active') }}" :checked="old('is_active', $ticketType->is_active ?? true)" />

        {{-- Plain markup rather than <x-admin.field>: that component does not forward
             arbitrary attributes onto its checkbox input, so x-model could not reach it. --}}
        <div class="flex items-center gap-2 mb-4">
            <input type="checkbox" name="is_popular" id="is_popular" value="1" x-model="isPopular" class="w-4 h-4 rounded border-hub-purple/30 text-hub-purple focus:ring-hub-purple/40">
            <label for="is_popular" class="text-sm text-hub-dark/75">{{ __('Mark as Popular (highlights this ticket on the public page)') }}</label>
        </div>

        <div x-show="isPopular" x-cloak class="mb-5">
            <x-admin.bilingual-field
                name="popular_label"
                label="{{ __('Popular Badge Text') }}"
                :value-ar="old('popular_label_ar', $ticketType->popular_label_ar)"
                :value-en="old('popular_label_en', $ticketType->popular_label_en)"
                :placeholder="__('Most Popular')"
            />
        </div>

        <x-admin.field type="number" name="sort_order" label="{{ __('Sort Order') }}" :value="old('sort_order', $ticketType->sort_order ?? 0)" />

        <x-admin.button type="submit">{{ __('Save') }}</x-admin.button>
    </form>
@endsection
