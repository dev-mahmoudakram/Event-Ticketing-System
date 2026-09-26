/**
 * The schedule details pop-up. A click (or Enter/Space) on any [data-schedule-open] card clones
 * that card's <template id="detail-…"> into the pop-up. The URL hash follows the open pop-up so a
 * session can be shared, and opening the page with such a hash opens it straight away. An unknown
 * hash is ignored. Clicks on links and buttons inside a card (e.g. "Book your seat") pass through.
 */
export default function schedulePopup() {
    return {
        open: false,
        returnFocus: null,

        init() {
            document.addEventListener('click', (event) => {
                const card = event.target.closest('[data-schedule-open]');
                if (card && ! event.target.closest('a, button')) {
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

            const anchor = decodeURIComponent(window.location.hash.slice(1));
            if (anchor) {
                this.show(anchor, false);
            }
        },

        show(anchor, updateHash = true) {
            const template = document.getElementById(`detail-${anchor}`);
            if (! template) {
                return;
            }

            this.returnFocus = document.getElementById(anchor) ?? document.activeElement;
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
            this.returnFocus?.focus?.();
        },
    };
}
