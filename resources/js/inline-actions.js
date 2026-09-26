/**
 * Small page behaviours declared with data attributes instead of inline onsubmit/onclick
 * handlers, which a content security policy without 'unsafe-inline' refuses to run.
 *
 *   <form data-confirm="Are you sure?">   asks before submitting
 *   <input data-select-on-click>          selects its text on click, for copying
 *   <button data-print>                   opens the print dialog
 */
export default function initInlineActions() {
    document.addEventListener('submit', (event) => {
        const message = event.target.dataset?.confirm;

        if (message && ! window.confirm(message)) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);

    document.addEventListener('click', (event) => {
        const selectable = event.target.closest('[data-select-on-click]');
        if (selectable) {
            selectable.select();
        }

        if (event.target.closest('[data-print]')) {
            window.print();
        }
    });
}
