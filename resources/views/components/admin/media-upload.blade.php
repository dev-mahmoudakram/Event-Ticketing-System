{{-- resources/views/components/admin/media-upload.blade.php --}}
@props([
    'name',
    'label',
    'accept' => 'image/*',
    'current' => null,
    'kind' => 'image',
    'hint' => null,
    'required' => false,
])

<div class="mb-5" x-data="{ fileName: null, preview: null }">
    <label for="{{ $name }}" class="adm-label">
        {{ $label }}
        @if($required)<span class="text-red-400">*</span>@endif
    </label>

    <div
        class="relative border-2 border-dashed border-hub-purple/25 bg-white rounded-2xl p-6 text-center transition-colors hover:border-hub-purple focus-within:border-hub-purple"
        @dragover.prevent="$el.classList.add('border-hub-purple')"
        @dragleave.prevent="$el.classList.remove('border-hub-purple')"
        @drop.prevent="
            $el.classList.remove('border-hub-purple');
            $refs.input.files = $event.dataTransfer.files;
            $refs.input.dispatchEvent(new Event('change'));
        "
    >
        <input
            id="{{ $name }}"
            type="file"
            name="{{ $name }}"
            x-ref="input"
            accept="{{ $accept }}"
            class="sr-only"
            @change="
                const file = $event.target.files[0];
                fileName = file?.name ?? null;
                if (preview) { URL.revokeObjectURL(preview); }
                preview = file && file.type.startsWith('image/') ? URL.createObjectURL(file) : null;
            "
        >

        {{-- Newly picked image, previewed straight from the browser before it is uploaded --}}
        <template x-if="preview">
            <img :src="preview" alt="" class="mx-auto mb-3 h-32 w-auto rounded object-contain">
        </template>

        {{-- Whatever is already saved on the record, shown until a replacement is chosen --}}
        @if($current)
            <div x-show="!preview" class="mb-3">
                @if($kind === 'video')
                    <video src="{{ $current }}" class="mx-auto h-32 rounded" muted playsinline preload="metadata"></video>
                @else
                    <img src="{{ $current }}" alt="" class="mx-auto h-32 w-auto rounded object-contain">
                @endif
                <p class="text-xs text-hub-dark/50 mt-2">{{ __('Current file — choosing a new one replaces it.') }}</p>
            </div>
        @endif

        <button type="button" @click="$refs.input.click()" class="text-sm font-semibold text-hub-purple hover:underline">
            {{ __('Choose a file or drag it here') }}
        </button>

        <p class="text-xs text-hub-dark/50 mt-2" x-show="fileName" x-cloak x-text="fileName"></p>
        @if($hint)
            <p class="text-xs text-hub-dark/50 mt-2">{{ $hint }}</p>
        @endif
    </div>

    @error($name)
        <p class="text-sm mt-1.5 text-[#b42318]">{{ $message }}</p>
    @enderror
</div>
