/**
 * The schedule details pop-up. A click on any [data-schedule-open] card clones that card's
 * <template id="detail-…"> into the pop-up. The URL hash follows the open pop-up so a session can
 * be shared, and opening the page with such a hash opens it straight away (switching to that
 * day's tab first). An unknown or malformed hash is ignored. Clicks on links and buttons inside a
 * card (e.g. "Book your seat") pass through; the card's title button is its own open control.
 * While open, Tab stays inside the pop-up.
 */
const FOCUSABLE = 'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

export default function schedulePopup() {
    return {
        open: false,
        returnFocus: null,

        init() {
            document.addEventListener('click', (event) => {
                const card = event.target.closest('[data-schedule-open]');
                // Links and other buttons inside a card do their own thing; the title button
                // (data-schedule-trigger) is the card's own "open details" control.
                if (card && ! event.target.closest('a, button:not([data-schedule-trigger])')) {
                    this.show(card.dataset.scheduleOpen);
                }
            });

            document.addEventListener('keydown', (event) => {
                const card = event.target.closest?.('[data-schedule-open]');
                if (card && event.target === card && (event.key === 'Enter' || event.key === ' ')) {
                    event.preventDefault();
                    this.show(card.dataset.scheduleOpen);
                }
            });

            let anchor = '';
            try {
                anchor = decodeURIComponent(window.location.hash.slice(1));
            } catch {
                anchor = '';
            }
            if (anchor) {
                this.show(anchor, false);
            }
        },

        show(anchor, updateHash = true) {
            const template = document.getElementById(`detail-${anchor}`);
            if (! template) {
                return;
            }

            const card = document.getElementById(anchor);

            // A card on another day's tab: switch to that day so the card is there to come
            // back to when the pop-up closes.
            const dayIndex = card?.closest('[data-day-index]')?.dataset.dayIndex;
            if (dayIndex !== undefined) {
                window.dispatchEvent(new CustomEvent('schedule-day', { detail: Number(dayIndex) }));
            }

            this.returnFocus = card?.querySelector('[data-schedule-trigger]') ?? card ?? document.activeElement;
            this.$refs.body.replaceChildren(template.content.cloneNode(true));
            this.$refs.body.querySelector('[data-schedule-title]')?.setAttribute('id', 'schedule-popup-title');
            this.open = true;
            document.body.style.overflow = 'hidden';

            if (updateHash) {
                history.replaceState(null, '', `#${anchor}`);
            }

            this.$nextTick(() => this.$refs.close.focus());
        },

        close() {
            if (! this.open) {
                return;
            }

            this.open = false;
            document.body.style.overflow = '';
            history.replaceState(null, '', window.location.pathname + window.location.search);
            this.$nextTick(() => this.returnFocus?.focus?.());
        },

        /**
         * Keep Tab and Shift+Tab cycling inside the open pop-up.
         */
        trapFocus(event) {
            const focusable = [...this.$refs.panel.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);
            if (focusable.length === 0) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    };
}
