import confirmDialog from './confirm-dialog';

/**
 * Small page behaviours declared with data attributes instead of inline onsubmit/onclick
 * handlers, which a content security policy without 'unsafe-inline' refuses to run.
 *
 *   <form data-confirm="Are you sure?">   asks in a dialog before submitting
 *   <input data-select-on-click>          selects its text on click, for copying
 *   <button data-print>                   opens the print dialog
 */
export default function initInlineActions() {
    document.addEventListener('submit', (event) => {
        const form = event.target;
        const message = form.dataset?.confirm;

        if (! message) {
            return;
        }

        // The second pass, after the person said yes, goes through untouched.
        if (form.dataset.confirmed === 'yes') {
            delete form.dataset.confirmed;

            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const submitter = event.submitter;
        const isDelete = form.querySelector('input[name="_method"]')?.value.toUpperCase() === 'DELETE';

        confirmDialog(message, { danger: isDelete }).then((confirmed) => {
            if (confirmed) {
                form.dataset.confirmed = 'yes';
                form.requestSubmit(submitter);
            }
        });
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
