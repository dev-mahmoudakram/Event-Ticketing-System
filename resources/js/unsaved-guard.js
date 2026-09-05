/**
 * Warn before leaving a form with edits that were never saved.
 *
 * Content forms hold paragraphs somebody has just written, and a mistaken click on the
 * section list beside them would otherwise take the lot without asking.
 *
 * Registered as an Alpine component: put x-data="unsavedGuard('message')" and x-bind="form"
 * on the <form>.
 */
export default function unsavedGuard(message) {
    return {
        dirty: false,
        message,

        init() {
            // A submit is the intended way out, so it must not trigger the warning.
            this.$el.addEventListener('submit', () => { this.dirty = false; });

            window.addEventListener('beforeunload', (event) => {
                if (! this.dirty) {
                    return;
                }

                // Browsers show their own wording here and ignore ours; the message is kept
                // for the in-page link check below, which can say something useful.
                event.preventDefault();
                event.returnValue = '';
            });

            // beforeunload does not fire for every in-page navigation in every browser, and
            // it cannot carry our own wording, so links are asked about directly.
            document.addEventListener('click', (event) => {
                const link = event.target.closest('a[href]');

                if (! this.dirty || ! link || link.target === '_blank') {
                    return;
                }

                const href = link.getAttribute('href');

                if (href.startsWith('#') || href.startsWith('javascript:')) {
                    return;
                }

                if (! window.confirm(this.message)) {
                    event.preventDefault();
                }
            });
        },

        form: {
            ['@input']() { this.dirty = true; },
            ['@change']() { this.dirty = true; },
        },
    };
}
