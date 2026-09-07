import Alpine from 'alpinejs';
import initCcsMotion from './ccs-motion';
import initAutoplayVideo from './autoplay-video';
import ticketScanner from './ticket-scanner';
import initCharts from './charts';
import initSortableLists from './sortable';
import unsavedGuard from './unsaved-guard';
import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import 'intl-tel-input/styles';

window.Alpine = Alpine;

Alpine.data('ticketScanner', ticketScanner);
Alpine.data('unsavedGuard', unsavedGuard);

Alpine.store('ticketRequest', {
    open: false,
    ticketTypeId: null,
    show(ticketTypeId) {
        this.ticketTypeId = ticketTypeId;
        this.open = true;
    },
});

Alpine.start();

window.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        Alpine.store('ticketRequest').open = false;
    }
});

const phoneInput = document.querySelector('#phone');
if (phoneInput) {
    const isArabic = document.documentElement.lang === 'ar';

    const iti = intlTelInput(phoneInput, {
        initialCountry: 'eg',
        ...(isArabic && {
            uiTranslations: {
                searchPlaceholder: 'بحث',
                clearSearchAriaLabel: 'مسح البحث',
                countryListAriaLabel: 'قائمة الدول',
                noCountrySelected: 'لم يتم اختيار دولة',
                searchEmptyState: 'لا توجد نتائج',
                selectedCountryAriaLabel: 'الدولة المختارة',
            },
        }),
    });

    phoneInput.closest('form')?.addEventListener('submit', () => {
        phoneInput.value = iti.getNumber();
    });
}

function filterInputCharacters(inputEl, disallowedPattern) {
    inputEl.addEventListener('input', () => {
        const cursorPos = inputEl.selectionStart;
        const originalLength = inputEl.value.length;
        inputEl.value = inputEl.value.replace(disallowedPattern, '');
        const removed = originalLength - inputEl.value.length;
        const newCursorPos = Math.max(0, cursorPos - removed);
        inputEl.setSelectionRange(newCursorPos, newCursorPos);
    });
}

const nameInput = document.querySelector('#name');
if (nameInput) {
    // Mirrors the server-side regex: unicode letters/marks, spaces, apostrophe, hyphen, period only.
    filterInputCharacters(nameInput, /[^\p{L}\p{M}\s'\-.]/gu);
}

const emailInput = document.querySelector('#email');
if (emailInput) {
    filterInputCharacters(emailInput, /[^a-zA-Z0-9@._%+\-]/g);
}

/**
 * Confirm a ticket request with a popup rather than a line of text, so the moment the visitor
 * has been waiting through the form for actually lands. SweetAlert2 is fetched only once a
 * request succeeds, which keeps it out of the bundle every other page loads.
 */
async function announceTicketRequested(form, { message, reference }) {
    const { default: Swal } = await import('sweetalert2');
    const stillMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.Alpine.store('ticketRequest').open = false;

    await Swal.fire({
        icon: 'success',
        title: form.dataset.successTitle,
        html: reference
            // <bdi> keeps a Latin reference number intact inside an Arabic paragraph.
            ? `<p class="ticket-success-label">${form.dataset.successReferenceLabel}</p>
               <p class="ticket-success-reference"><bdi>${reference}</bdi></p>
               <p class="ticket-success-note">${form.dataset.successNote}</p>`
            : `<p class="ticket-success-note">${message}</p>`,
        confirmButtonText: form.dataset.successConfirm,
        buttonsStyling: false,
        customClass: {
            popup: 'ticket-success',
            confirmButton: 'ticket-success-confirm',
        },
        ...(stillMotion && { showClass: { popup: '' }, hideClass: { popup: '' } }),
    });
}

const ticketRequestForm = document.getElementById('ticket-request-form');
if (ticketRequestForm) {
    ticketRequestForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const feedback = document.getElementById('ticket-request-feedback');
        const submitButton = ticketRequestForm.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.textContent;
        const genericError = ticketRequestForm.dataset.genericError;

        ticketRequestForm.querySelectorAll('[id^="error-"]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });
        if (feedback) {
            feedback.textContent = '';
            feedback.classList.add('hidden');
        }

        submitButton.disabled = true;
        submitButton.textContent = '...';

        try {
            const response = await fetch(ticketRequestForm.action, {
                method: 'POST',
                body: new FormData(ticketRequestForm),
                headers: { Accept: 'application/json' },
            });

            const data = await response.json().catch(() => ({}));

            if (response.status === 422) {
                Object.entries(data.errors ?? {}).forEach(([field, messages]) => {
                    const errorEl = document.getElementById('error-' + field);
                    if (errorEl) {
                        errorEl.textContent = messages[0];
                        errorEl.classList.remove('hidden');
                    }
                });
            } else if (response.ok) {
                ticketRequestForm.reset();
                await announceTicketRequested(ticketRequestForm, { message: data.message || '', reference: data.reference });
            } else {
                if (feedback) {
                    feedback.textContent = data.message || genericError;
                    feedback.className = 'text-sm font-bold mb-4 text-red-400';
                }
            }
        } catch (error) {
            if (feedback) {
                feedback.textContent = genericError;
                feedback.className = 'text-sm font-bold mb-4 text-red-400';
            }
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalButtonText;
        }
    });
}

if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

    document.querySelectorAll('[data-reveal]').forEach((el) => revealObserver.observe(el));
} else {
    document.querySelectorAll('[data-reveal]').forEach((el) => el.classList.add('is-visible'));
}

initCcsMotion();
initAutoplayVideo();
initCharts();
initSortableLists();
