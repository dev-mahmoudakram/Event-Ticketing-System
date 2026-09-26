{{-- resources/views/components/nice-select.blade.php --}}
{{-- A styled dropdown bound to an Alpine model, submitted through a hidden input.
     Keyboard: Enter/Space/ArrowDown open, arrows move, Enter picks, Escape closes. --}}
@props(['name', 'model', 'options' => [], 'id' => null, 'placeholder' => null])

<div
    x-data="{
        open: false,
        active: 0,
        options: {{ \Illuminate\Support\Js::from(array_values($options), JSON_UNESCAPED_UNICODE) }},
        get chosen() { return this.options.find((option) => String(option.value) === String({{ $model }})) },
        show() {
            this.open = true;
            const index = this.options.findIndex((option) => String(option.value) === String({{ $model }}));
            this.active = index < 0 ? 0 : index;
        },
        move(step) {
            if (! this.open) { return this.show(); }
            this.active = (this.active + step + this.options.length) % this.options.length;
            this.$nextTick(() => this.$refs.list.children[this.active + 1]?.scrollIntoView({ block: 'nearest' }));
        },
        pick(index) {
            const option = this.options[index];
            if (option) { {{ $model }} = option.value; }
            this.open = false;
            this.$refs.trigger.focus();
        },
    }"
    class="ccs-select"
    :class="open && 'is-open'"
    @click.outside="open = false"
>
    <button
        type="button"
        x-ref="trigger"
        @if($id) id="{{ $id }}" @endif
        @click="open ? (open = false) : show()"
        @keydown.arrow-down.prevent="move(1)"
        @keydown.arrow-up.prevent="move(-1)"
        @keydown.enter.prevent="open ? pick(active) : show()"
        @keydown.space.prevent="open ? pick(active) : show()"
        @keydown.escape="if (open) { $event.stopPropagation(); open = false }"
        @keydown.tab="open = false"
        class="ccs-form-input ccs-select-trigger"
        :aria-expanded="open"
        aria-haspopup="listbox"
    >
        <span x-text="chosen ? chosen.label : @js($placeholder ?? '')" :class="chosen ? '' : 'is-placeholder'"></span>
        <svg class="w-4 h-4 shrink-0 text-ccs-coral transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    <ul x-ref="list" x-show="open" x-cloak x-transition.opacity role="listbox" class="ccs-select-list">
        <template x-for="(option, index) in options" :key="option.value">
            <li
                role="option"
                @mousedown.prevent
                @click="pick(index)"
                @mouseenter="active = index"
                class="ccs-select-option"
                :class="index === active && 'is-active'"
                :aria-selected="String(option.value) === String({{ $model }})"
                x-text="option.label"
            ></li>
        </template>
    </ul>

    <input type="hidden" name="{{ $name }}" :value="{{ $model }}">
</div>
