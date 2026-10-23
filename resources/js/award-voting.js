/**
 * The public Awards page: award tabs, nominee bios, live counts and the four-step vote window
 * (confirm → email + Cloudflare Turnstile → emailed code → done).
 *
 *   <section data-award-voting data-counts-url="…" data-code-url-template="…/__AWARD__/code"
 *            data-vote-url-template="…/__AWARD__/vote" data-site-key="…">
 *
 * The server is the judge of everything — this only carries the visitor through the steps and
 * shows the JSON errors under the field they belong to.
 */
const POLL_INTERVAL_MS = 30000;
const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, iframe, [tabindex]:not([tabindex="-1"])';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function isShown(element) {
    return !! element && element.closest('[hidden]') === null && element.getClientRects().length > 0;
}

function initTabs(root) {
    const tabs = Array.from(root.querySelectorAll('[role="tab"][data-award-tab]'));
    if (tabs.length === 0) {
        return;
    }

    const select = (tab, { focus = false, updateHash = false } = {}) => {
        tabs.forEach((other) => {
            const isSelected = other === tab;
            other.setAttribute('aria-selected', isSelected ? 'true' : 'false');
            other.tabIndex = isSelected ? 0 : -1;
            const panel = document.getElementById(other.getAttribute('aria-controls'));
            if (panel) {
                panel.hidden = ! isSelected;
            }
        });

        tab.scrollIntoView({ block: 'nearest', inline: 'nearest' });

        if (focus) {
            tab.focus();
        }

        if (updateHash) {
            history.replaceState(null, '', '#' + tab.getAttribute('aria-controls'));
        }
    };

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => select(tab, { updateHash: true }));
        tab.addEventListener('keydown', (event) => {
            const isRtl = getComputedStyle(tab).direction === 'rtl';
            const step = { ArrowRight: isRtl ? -1 : 1, ArrowLeft: isRtl ? 1 : -1 }[event.key];
            let target = null;

            if (step !== undefined) {
                target = tabs[(index + step + tabs.length) % tabs.length];
            } else if (event.key === 'Home') {
                target = tabs[0];
            } else if (event.key === 'End') {
                target = tabs[tabs.length - 1];
            }

            if (target) {
                event.preventDefault();
                select(target, { focus: true, updateHash: true });
            }
        });
    });

    const selectFromHash = () => {
        const fromHash = tabs.find((tab) => '#' + tab.getAttribute('aria-controls') === window.location.hash);
        if (fromHash) {
            select(fromHash);
        }
    };

    selectFromHash();
    window.addEventListener('hashchange', selectFromHash);
}

function initBios(root) {
    root.querySelectorAll('[data-nominee-bio-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const bio = document.getElementById(toggle.getAttribute('aria-controls'));
            if (! bio) {
                return;
            }
            const expand = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', expand ? 'true' : 'false');
            bio.hidden = ! expand;
        });
    });
}

/**
 * The translated strings Blade renders into the page as a JSON blob.
 *
 * @param {Element} root
 * @returns {Record<string, any>}
 */
function readStrings(root) {
    try {
        return JSON.parse(root.querySelector('[data-award-voting-strings]')?.textContent ?? '{}');
    } catch (error) {
        return {};
    }
}

/**
 * Plural categories in the order Laravel's trans_choice reads the forms: Arabic's six
 * (zero|one|two|few|many|other) and the two of English and most other languages.
 */
const PLURAL_ORDER = {
    6: ['zero', 'one', 'two', 'few', 'many', 'other'],
    2: ['one', 'other'],
};

/**
 * "1 vote" / "7 أصوات" — the same form the server picked for the first render.
 *
 * @param {number} count
 * @param {string[]} forms the ':count vote|:count votes' translation split on '|'
 */
function voteCountLabel(count, forms) {
    if (! Array.isArray(forms) || forms.length === 0) {
        return String(count);
    }

    const order = PLURAL_ORDER[forms.length];
    let index = forms.length - 1;

    if (order) {
        try {
            const category = new Intl.PluralRules(document.documentElement.lang || undefined).select(count);
            const found = order.indexOf(category);
            index = found === -1 ? forms.length - 1 : found;
        } catch (error) {
            index = count === 1 ? 0 : forms.length - 1;
        }
    }

    return forms[index].replace(':count', String(count));
}

/**
 * Write counts into the cards and size each award's bars as a share of that award's votes.
 *
 * @param {Element} root
 * @param {Record<string, number>} counts nominee id => counted votes
 * @param {string[]} forms the vote-count plural forms
 */
function applyCounts(root, counts, forms) {
    Object.entries(counts).forEach(([nomineeId, count]) => {
        const countEl = root.querySelector(`[data-count-for="${CSS.escape(String(nomineeId))}"]`);
        if (countEl) {
            const number = Number(count) || 0;
            countEl.dataset.count = String(number);
            countEl.textContent = voteCountLabel(number, forms);
        }
    });

    root.querySelectorAll('[data-award-panel]').forEach((panel) => {
        const countEls = Array.from(panel.querySelectorAll('[data-count-for]'));
        const countOf = (el) => parseInt(el.dataset.count, 10) || 0;
        const total = countEls.reduce((sum, el) => sum + countOf(el), 0);

        countEls.forEach((el) => {
            const bar = panel.querySelector(`[data-bar-for="${CSS.escape(el.dataset.countFor)}"]`);
            if (bar) {
                const share = total > 0 ? (countOf(el) / total) * 100 : 0;
                bar.style.width = share.toFixed(1) + '%';
            }
        });
    });
}

function hideVoteButtons(scope) {
    scope.querySelectorAll('[data-vote-nominee]').forEach((button) => {
        button.hidden = true;
    });
}

function initPolling(root) {
    const countsUrl = root.dataset.countsUrl;
    if (! countsUrl) {
        return;
    }

    const voteCountForms = readStrings(root).voteCountForms;
    let inFlight = false;

    const refresh = async () => {
        if (inFlight || document.visibilityState !== 'visible') {
            return;
        }
        inFlight = true;

        try {
            const response = await fetch(countsUrl, { headers: { Accept: 'application/json' } });
            if (! response.ok) {
                return;
            }
            const data = await response.json();
            applyCounts(root, data.counts ?? {}, voteCountForms);
            if (data.open === false) {
                hideVoteButtons(root);
            }
        } catch (error) {
            // A missed refresh is harmless — the next tick tries again.
        } finally {
            inFlight = false;
        }
    };

    setInterval(refresh, POLL_INTERVAL_MS);
    document.addEventListener('visibilitychange', refresh);
}

/**
 * Cloudflare's script loads async; wait for it before rendering a widget.
 *
 * @returns {Promise<object|null>} window.turnstile, or null when it never loads
 */
function whenTurnstileReady() {
    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    const script = document.getElementById('turnstile-script');
    if (! script) {
        return Promise.resolve(null);
    }

    return new Promise((resolve) => {
        script.addEventListener('load', () => resolve(window.turnstile ?? null), { once: true });
        script.addEventListener('error', () => resolve(null), { once: true });
    });
}

function initVoteWindow(root) {
    const windowEl = root.querySelector('[data-vote-window]');
    if (! windowEl) {
        return;
    }

    const dialog = windowEl.querySelector('[data-vote-dialog]');
    const feedback = windowEl.querySelector('[data-vote-feedback]');
    const steps = Object.fromEntries(
        Array.from(windowEl.querySelectorAll('[data-vote-step]')).map((el) => [el.dataset.voteStep, el]),
    );
    const emailInput = windowEl.querySelector('#vote-email');
    const codeInput = windowEl.querySelector('#vote-code');
    const turnstileSlot = windowEl.querySelector('[data-turnstile-slot]');
    const siteKey = root.dataset.siteKey ?? '';

    const strings = readStrings(root);

    const state = { awardId: null, nomineeId: null, trigger: null, widgetId: null, busy: false };

    const urlFor = (template) => template.replace('__AWARD__', encodeURIComponent(state.awardId));

    const clearErrors = () => {
        feedback.hidden = true;
        feedback.textContent = '';
        windowEl.querySelectorAll('[data-error-for]').forEach((el) => {
            el.textContent = '';
            el.classList.add('hidden');
        });
        windowEl.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid'));
    };

    const showFeedback = (message) => {
        feedback.textContent = message || strings.genericError || '';
        feedback.hidden = false;
    };

    const showErrors = (errors, message) => {
        let placed = false;

        Object.entries(errors ?? {}).forEach(([field, messages]) => {
            const errorEl = Array.from(windowEl.querySelectorAll(`[data-error-for="${CSS.escape(field)}"]`)).find(isShown);
            if (errorEl) {
                errorEl.textContent = messages[0];
                errorEl.classList.remove('hidden');
                windowEl.querySelector(`[name="${CSS.escape(field)}"]`)?.setAttribute('aria-invalid', 'true');
                placed = true;
            } else {
                showFeedback(messages[0]);
                placed = true;
            }
        });

        if (! placed) {
            showFeedback(message);
        }
    };

    const renderTurnstile = async () => {
        if (! siteKey || ! turnstileSlot) {
            return;
        }
        const turnstile = await whenTurnstileReady();
        if (! turnstile) {
            return;
        }

        if (state.widgetId !== null) {
            turnstile.reset(state.widgetId);
            return;
        }

        state.widgetId = turnstile.render(turnstileSlot, {
            sitekey: siteKey,
            theme: 'dark',
            language: document.documentElement.lang || 'auto',
        });
    };

    const turnstileToken = () => {
        if (state.widgetId === null || ! window.turnstile) {
            return '';
        }
        return window.turnstile.getResponse(state.widgetId) ?? '';
    };

    const resetTurnstile = () => {
        if (state.widgetId !== null && window.turnstile) {
            window.turnstile.reset(state.widgetId);
        }
    };

    const showStep = (name) => {
        Object.entries(steps).forEach(([stepName, el]) => {
            el.hidden = stepName !== name;
        });
        clearErrors();

        if (name === 'email') {
            renderTurnstile();
            emailInput?.focus();
        } else if (name === 'code') {
            codeInput.value = '';
            codeInput.focus();
        } else if (name === 'done') {
            steps.done.querySelector('[data-vote-done-message]')?.focus();
        } else {
            steps[name]?.querySelector('button')?.focus();
        }
    };

    const open = (button) => {
        state.awardId = button.dataset.voteAward;
        state.nomineeId = button.dataset.voteNominee;
        state.trigger = button;

        windowEl.querySelector('[data-vote-summary]').textContent = (strings.confirm ?? '')
            .replace(':nominee', button.dataset.nomineeName ?? '')
            .replace(':award', button.dataset.awardName ?? '');

        windowEl.hidden = false;
        document.body.style.overflow = 'hidden';
        showStep('confirm');
    };

    const close = () => {
        if (windowEl.hidden) {
            return;
        }
        windowEl.hidden = true;
        document.body.style.overflow = '';

        const trigger = state.trigger;
        if (trigger && isShown(trigger)) {
            trigger.focus();
        } else if (trigger) {
            trigger.closest('[data-award-panel]')?.focus();
        }
    };

    const post = async (url, body) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(body),
        });
        const data = await response.json().catch(() => ({}));

        return { response, data };
    };

    const handleFailure = (response, data) => {
        if (response.status === 422) {
            showErrors(data.errors, data.message);
        } else if (response.status === 429) {
            showFeedback(data.message || strings.tooManyAttempts);
        } else if (response.status === 503) {
            showFeedback(data.message);
        } else {
            showFeedback(strings.genericError);
        }
    };

    const withBusy = async (form, work) => {
        if (state.busy) {
            return;
        }
        state.busy = true;
        const submit = form.querySelector('button[type="submit"]');
        submit?.classList.add('is-loading');
        submit?.setAttribute('aria-busy', 'true');
        if (submit) {
            submit.disabled = true;
        }

        try {
            await work();
        } catch (error) {
            showFeedback(strings.genericError);
        } finally {
            state.busy = false;
            submit?.classList.remove('is-loading');
            submit?.removeAttribute('aria-busy');
            if (submit) {
                submit.disabled = false;
            }
        }
    };

    const markVoted = (awardId, nomineeId) => {
        const panel = root.querySelector(`[data-award-panel="${CSS.escape(String(awardId))}"]`);
        if (! panel) {
            return;
        }
        hideVoteButtons(panel);
        const badge = panel.querySelector('[data-award-voted]');
        if (badge) {
            badge.hidden = false;
        }
        const card = panel.querySelector(`[data-nominee-card="${CSS.escape(String(nomineeId))}"]`);
        const cardBadge = card?.querySelector('[data-nominee-voted]');
        if (cardBadge) {
            cardBadge.hidden = false;
        }
    };

    root.querySelectorAll('[data-vote-nominee]').forEach((button) => {
        button.addEventListener('click', () => open(button));
    });

    windowEl.querySelectorAll('[data-vote-close]').forEach((el) => el.addEventListener('click', close));
    windowEl.querySelectorAll('[data-vote-backdrop]').forEach((el) => {
        el.addEventListener('click', (event) => {
            if (event.target === el) {
                close();
            }
        });
    });

    windowEl.querySelector('[data-vote-continue]')?.addEventListener('click', () => showStep('email'));
    windowEl.querySelector('[data-vote-resend]')?.addEventListener('click', () => showStep('email'));

    codeInput?.addEventListener('input', () => {
        const digits = codeInput.value
            .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x0660))
            .replace(/[۰-۹]/g, (digit) => String(digit.charCodeAt(0) - 0x06F0))
            .replace(/\D/g, '')
            .slice(0, 6);
        if (digits !== codeInput.value) {
            codeInput.value = digits;
        }
    });

    steps.email?.addEventListener('submit', (event) => {
        event.preventDefault();
        withBusy(steps.email, async () => {
            clearErrors();
            const email = emailInput.value.trim();
            const { response, data } = await post(urlFor(root.dataset.codeUrlTemplate), {
                nominee_id: state.nomineeId,
                email,
                'cf-turnstile-response': turnstileToken(),
            });
            resetTurnstile();

            if (! response.ok) {
                handleFailure(response, data);
                return;
            }

            showStep('code');
            steps.code.querySelector('[data-vote-code-sent]').textContent = data.message
                || (strings.codeSentTo ?? '').replace(':email', email);
        });
    });

    steps.code?.addEventListener('submit', (event) => {
        event.preventDefault();
        withBusy(steps.code, async () => {
            clearErrors();
            const { response, data } = await post(urlFor(root.dataset.voteUrlTemplate), {
                nominee_id: state.nomineeId,
                email: emailInput.value.trim(),
                code: codeInput.value.trim(),
            });

            if (! response.ok) {
                handleFailure(response, data);
                return;
            }

            const nomineeId = data.nominee_id ?? state.nomineeId;
            if (typeof data.count === 'number') {
                applyCounts(root, { [nomineeId]: data.count }, strings.voteCountForms);
            }
            markVoted(state.awardId, nomineeId);

            steps.done.querySelector('[data-vote-done-message]').textContent = data.message ?? '';
            showStep('done');
        });
    });

    windowEl.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = Array.from(dialog.querySelectorAll(FOCUSABLE)).filter(isShown);
        if (focusable.length === 0) {
            event.preventDefault();
            return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && (document.activeElement === first || ! dialog.contains(document.activeElement))) {
            event.preventDefault();
            last.focus();
        } else if (! event.shiftKey && (document.activeElement === last || ! dialog.contains(document.activeElement))) {
            event.preventDefault();
            first.focus();
        }
    });
}

export default function initAwardVoting() {
    const root = document.querySelector('[data-award-voting]');
    if (! root) {
        return;
    }

    initTabs(root);
    initBios(root);
    initPolling(root);
    initVoteWindow(root);
}
