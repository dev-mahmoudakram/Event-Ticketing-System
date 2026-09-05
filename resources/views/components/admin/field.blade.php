@props([
    'type' => 'text',
    'name',
    'label' => null,
    'value' => null,
    'dir' => null,
    'placeholder' => null,
    'checked' => false,
    'required' => false,
])

@php $inputClasses = 'adm-input'; @endphp

@if($type === 'checkbox')
    <div class="flex items-center gap-2 mb-4">
        <input type="checkbox" name="{{ $name }}" id="{{ $name }}" value="1" class="w-4 h-4 rounded border-hub-purple/30 text-hub-purple focus:ring-hub-purple/40" @checked($checked)>
        @if($label)
            <label for="{{ $name }}" class="text-sm text-hub-dark/75">{{ $label }}</label>
        @endif
    </div>
@else
    <div class="mb-5">
        @if($label)
            <label for="{{ $name }}" class="adm-label">{{ $label }}</label>
        @endif

        @if($type === 'textarea')
            <textarea name="{{ $name }}" id="{{ $name }}"
                @if($dir) dir="{{ $dir }}" @endif
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if($required) required @endif
                class="{{ $inputClasses }}">{{ $value }}</textarea>
        @elseif($type === 'select')
            <select name="{{ $name }}" id="{{ $name }}" @if($required) required @endif class="{{ $inputClasses }}">
                {{ $slot }}
            </select>
        @else
            <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}"
                @if($dir) dir="{{ $dir }}" @endif
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if($required) required @endif
                class="{{ $inputClasses }}">
        @endif

        @error($name)
            <p class="text-sm mt-1.5 text-[#b42318]">{{ $message }}</p>
        @enderror
    </div>
@endif
