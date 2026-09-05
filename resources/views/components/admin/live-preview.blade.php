{{-- resources/views/components/admin/live-preview.blade.php --}}

{{-- The real page, beside the form.

     Deliberately an iframe of the live site rather than a drawing of it: there is one copy
     of the section's markup, so the preview cannot drift from what visitors see, and every
     section gets one without a per-section sketch being written and maintained.

     $url      the page to show
     $anchor   the element to scroll to, if the section has one
     $label    what the "open in a new tab" link is called --}}
@props([
    'url',
    'anchor' => null,
    'label' => null,
])

@php
    // The anchor rides in the URL so a reload lands back on the same section.
    $target = $anchor ? $url.'#'.$anchor : $url;
@endphp

<div
    class="adm-preview-frame"
    x-data="{
        width: 'desktop',
        reloading: false,
        reload() {
            this.reloading = true;
            const frame = this.$refs.frame;
            // Re-assigning src rather than calling location.reload(), which a cross-origin
            // guard can refuse; the cache-buster stops the browser serving the old render.
            frame.src = @js($target).replace('#', '?_p=' + Date.now() + '#');
        },
    }"
>
    <div class="adm-preview-bar">
        <span class="adm-preview-title">{{ __('Live preview') }}</span>

        <div class="adm-preview-actions">
            {{-- The section has to work on a phone too, and that is easiest to judge by
                 looking at it rather than by remembering to check later. --}}
            <button type="button" class="adm-preview-size" :class="width === 'desktop' ? 'is-active' : ''" @click="width = 'desktop'" title="{{ __('Desktop') }}" aria-label="{{ __('Desktop') }}">▭</button>
            <button type="button" class="adm-preview-size" :class="width === 'phone' ? 'is-active' : ''" @click="width = 'phone'" title="{{ __('Phone') }}" aria-label="{{ __('Phone') }}">▯</button>

            <button type="button" class="adm-preview-refresh" @click="reload()" title="{{ __('Refresh') }}" aria-label="{{ __('Refresh') }}">⟳</button>

            <a href="{{ $target }}" target="_blank" rel="noopener" class="adm-preview-open">{{ $label ?? __('Open') }}</a>
        </div>
    </div>

    <div class="adm-preview-stage" :class="width === 'phone' ? 'is-phone' : ''">
        <iframe
            x-ref="frame"
            src="{{ $target }}"
            title="{{ __('Preview of the page') }}"
            loading="lazy"
            @load="reloading = false"
        ></iframe>
    </div>

    <p class="adm-preview-note">{{ __('Save to see your changes here.') }}</p>
</div>
