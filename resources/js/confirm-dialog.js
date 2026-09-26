/**
 * An in-page confirmation dialog, in place of the browser's own confirm() box.
 *
 * Button wording comes from data attributes on <body> (written by the layout in the reading
 * language), and SweetAlert2 is fetched only the first time a dialog opens.
 *
 * Resolves true when the person confirms, false when they cancel or dismiss it.
 */
export default async function confirmDialog(message, { danger = false } = {}) {
    const { default: Swal } = await import('sweetalert2');
    const labels = document.body.dataset;

    const result = await Swal.fire({
        text: message,
        icon: danger ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonText: danger ? labels.confirmDelete : labels.confirmYes,
        cancelButtonText: labels.confirmNo,
        reverseButtons: true,
        focusCancel: danger,
        buttonsStyling: false,
        customClass: {
            popup: 'adm-confirm',
            confirmButton: danger ? 'adm-btn adm-confirm-danger' : 'adm-btn adm-btn-primary',
            cancelButton: 'adm-btn adm-btn-secondary',
            actions: 'adm-confirm-actions',
        },
        ...(window.matchMedia('(prefers-reduced-motion: reduce)').matches && {
            showClass: { popup: '' },
            hideClass: { popup: '' },
        }),
    });

    return result.isConfirmed === true;
}
