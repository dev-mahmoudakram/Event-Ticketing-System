{{-- resources/views/components/file-dropzone.blade.php --}}
@props(['name', 'accept' => null, 'label' => null])

<div x-data="{ fileName: null }">
    @if($label)
        <label class="block text-sm text-gray-300 mb-2">{{ $label }}</label>
    @endif

    <div
        class="border-2 border-dashed border-gray-600 rounded-lg p-6 text-center transition-colors hover:border-ccs-gold"
        @dragover.prevent
        @drop.prevent="$refs.input.files = $event.dataTransfer.files; fileName = $event.dataTransfer.files[0]?.name ?? null"
    >
        <input
            type="file"
            name="{{ $name }}"
            x-ref="input"
            @if($accept) accept="{{ $accept }}" @endif
            class="hidden"
            @change="fileName = $event.target.files[0]?.name ?? null"
        >
        <button type="button" @click="$refs.input.click()" class="text-sm font-semibold text-ccs-gold hover:underline">
            {{ __('Choose a file or drag it here') }}
        </button>
        <p class="text-xs text-gray-500 mt-2" x-show="fileName" x-cloak x-text="fileName"></p>
    </div>
</div>
