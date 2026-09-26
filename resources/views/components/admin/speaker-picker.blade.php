{{-- An ordered list of speakers for a session or workshop. Submits speaker_ids[] in the order
     shown; the first speaker is the one shown first on the public card. A plain <select> (not
     the styled one) because its options change as speakers are added and removed. --}}
@props(['speakers', 'selected' => []])

@php
    $options = $speakers->map(fn ($speaker) => [
        'id' => $speaker->id,
        'name' => app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en,
        'photo' => $speaker->photoUrl(),
        'initials' => mb_strtoupper(mb_substr(app()->getLocale() === 'ar' ? $speaker->name_ar : $speaker->name_en, 0, 1)),
    ])->values();
@endphp

<div class="mb-5" data-speaker-picker
    x-data="{
        options: @js($options),
        chosen: @js(array_values(array_map('intval', (array) $selected))),
        adding: '',
        speaker(id) { return this.options.find((option) => option.id === id) },
        get available() { return this.options.filter((option) => ! this.chosen.includes(option.id)) },
        add() { if (this.adding !== '') { this.chosen.push(Number(this.adding)); this.adding = '' } },
        move(index, step) {
            const target = index + step;
            if (target < 0 || target >= this.chosen.length) { return }
            [this.chosen[index], this.chosen[target]] = [this.chosen[target], this.chosen[index]];
        },
        remove(index) { this.chosen.splice(index, 1) },
    }">
    <span class="adm-label">{{ __('Speakers') }}</span>

    <ol class="flex flex-col gap-2 mb-3" x-show="chosen.length > 0">
        <template x-for="(id, index) in chosen" :key="id">
            <li class="flex items-center gap-3 rounded-xl border border-hub-purple/15 bg-white px-3 py-2">
                <input type="hidden" name="speaker_ids[]" :value="id">
                <template x-if="speaker(id)?.photo"><img :src="speaker(id).photo" alt="" class="w-9 h-9 rounded-full object-cover"></template>
                <template x-if="! speaker(id)?.photo"><span class="w-9 h-9 rounded-full bg-hub-lavender text-hub-purple font-bold flex items-center justify-center" x-text="speaker(id)?.initials"></span></template>
                <span class="flex-1 font-semibold text-sm" x-text="speaker(id)?.name"></span>
                <button type="button" class="adm-btn adm-btn-secondary px-2.5 py-1" @click="move(index, -1)" :disabled="index === 0" aria-label="{{ __('Move up') }}">↑</button>
                <button type="button" class="adm-btn adm-btn-secondary px-2.5 py-1" @click="move(index, 1)" :disabled="index === chosen.length - 1" aria-label="{{ __('Move down') }}">↓</button>
                <button type="button" class="adm-btn-danger" @click="remove(index)">{{ __('Remove') }}</button>
            </li>
        </template>
    </ol>
    <p class="text-sm text-hub-dark/55 mb-3" x-show="chosen.length === 0">{{ __('No speakers yet.') }}</p>

    <div class="flex gap-2" x-show="available.length > 0">
        <select class="adm-input" x-model="adding" aria-label="{{ __('Add speaker') }}">
            <option value="">{{ __('Add speaker…') }}</option>
            <template x-for="option in available" :key="option.id">
                <option :value="option.id" x-text="option.name"></option>
            </template>
        </select>
        <button type="button" class="adm-btn adm-btn-primary" @click="add()">{{ __('Add') }}</button>
    </div>

    @error('speaker_ids.*') <p class="text-sm mt-1.5 text-[#b42318]">{{ $message }}</p> @enderror
</div>
