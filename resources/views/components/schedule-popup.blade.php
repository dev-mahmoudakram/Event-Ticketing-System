{{-- The one pop-up a schedule page needs, filled from the clicked card's <template>
     (resources/js/schedule-popup.js). The ccs-schedule-dialog class drops the fade and zoom for
     people who ask for reduced motion. --}}
<div x-data="schedulePopup" x-show="open" x-cloak @keydown.escape.window="close()" @keydown.tab="trapFocus($event)"
     class="ccs-schedule-dialog fixed inset-0 z-[110]" role="dialog" aria-modal="true" aria-labelledby="schedule-popup-title">
    <div class="absolute inset-0 bg-black/70" @click="close()" x-show="open" x-transition.opacity></div>
    <div class="absolute inset-0 overflow-y-auto p-4 flex items-start md:items-center justify-center" @click.self="close()">
        <div x-ref="panel" class="relative w-full max-w-2xl my-8 rounded-2xl border border-white/10 bg-ccs-black p-6 md:p-8 shadow-2xl" x-show="open" x-transition>
            <button type="button" x-ref="close" @click="close()" class="absolute top-4 end-4 text-gray-400 hover:text-white text-2xl leading-none" aria-label="{{ __('Close') }}">&times;</button>
            <div x-ref="body" class="pe-8"></div>
        </div>
    </div>
</div>
