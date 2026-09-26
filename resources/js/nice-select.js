/**
 * Draws a styled dropdown over any <select data-nice-select>.
 *
 * The real <select> stays in the form as the source of truth, hidden from sight: the form still
 * submits it, old() and validation errors still fill it, and Alpine's x-model still reads it.
 * Choosing an option here sets its value and fires the same input/change events a person
 * picking from the native list would, so anything listening keeps working.
 *
 * Keyboard: Enter/Space/ArrowDown open the list, arrows move, Enter picks, Escape closes, and
 * typing a letter jumps to the next option starting with it.
 */
let openInstance = null;

function enhance(select) {
    if (select.dataset.niceSelectReady || select.multiple) {
        return;
    }
    select.dataset.niceSelectReady = 'yes';

    const wrapper = document.createElement('div');
    wrapper.className = 'adm-select';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'adm-input adm-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const triggerLabel = document.createElement('span');
    triggerLabel.className = 'adm-select-value';
    trigger.append(triggerLabel);
    trigger.insertAdjacentHTML('beforeend', '<svg class="adm-select-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>');

    const list = document.createElement('ul');
    list.className = 'adm-select-list';
    list.setAttribute('role', 'listbox');
    list.hidden = true;

    // Label the button the way the <select> was labelled, so screen readers still announce it.
    const label = select.id ? document.querySelector(`label[for="${CSS.escape(select.id)}"]`) : null;
    if (label) {
        label.id ||= `${select.id}-label`;
        trigger.setAttribute('aria-labelledby', label.id);
        label.addEventListener('click', (event) => {
            event.preventDefault();
            trigger.focus();
        });
    } else if (select.getAttribute('aria-label')) {
        trigger.setAttribute('aria-label', select.getAttribute('aria-label'));
    }

    select.classList.add('adm-select-native');
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');
    select.before(wrapper);
    wrapper.append(select, trigger, list);

    let activeIndex = -1;

    const options = () => Array.from(select.options);

    const syncLabel = () => {
        const chosen = select.options[select.selectedIndex];
        triggerLabel.textContent = chosen ? chosen.textContent.trim() : '';
        trigger.classList.toggle('is-placeholder', ! chosen || chosen.value === '');
    };

    const render = () => {
        list.replaceChildren(...options().map((option, index) => {
            const item = document.createElement('li');
            item.setAttribute('role', 'option');
            item.className = 'adm-select-option';
            item.textContent = option.textContent.trim();
            item.dataset.index = String(index);
            item.setAttribute('aria-selected', String(index === select.selectedIndex));
            if (option.disabled) {
                item.setAttribute('aria-disabled', 'true');
            }

            return item;
        }));
    };

    const highlight = (index) => {
        const items = list.children;
        if (! items.length) {
            return;
        }
        activeIndex = Math.max(0, Math.min(index, items.length - 1));
        Array.from(items).forEach((item, i) => item.classList.toggle('is-active', i === activeIndex));
        items[activeIndex].scrollIntoView({ block: 'nearest' });
    };

    const close = () => {
        list.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        wrapper.classList.remove('is-open');
        if (openInstance === close) {
            openInstance = null;
        }
    };

    const open = () => {
        if (openInstance && openInstance !== close) {
            openInstance();
        }
        render();
        list.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        wrapper.classList.add('is-open');
        highlight(Math.max(select.selectedIndex, 0));
        openInstance = close;
    };

    const choose = (index) => {
        const option = select.options[index];
        if (! option || option.disabled) {
            return;
        }
        if (select.selectedIndex !== index) {
            select.selectedIndex = index;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        syncLabel();
        close();
        trigger.focus();
    };

    trigger.addEventListener('click', () => (list.hidden ? open() : close()));

    list.addEventListener('mousedown', (event) => event.preventDefault());
    list.addEventListener('click', (event) => {
        const item = event.target.closest('.adm-select-option');
        if (item) {
            choose(Number(item.dataset.index));
        }
    });

    trigger.addEventListener('keydown', (event) => {
        const isOpen = ! list.hidden;

        switch (event.key) {
            case 'ArrowDown':
            case 'ArrowUp':
                event.preventDefault();
                if (! isOpen) {
                    open();
                } else {
                    highlight(activeIndex + (event.key === 'ArrowDown' ? 1 : -1));
                }
                break;
            case 'Enter':
            case ' ':
                event.preventDefault();
                if (isOpen) {
                    choose(activeIndex);
                } else {
                    open();
                }
                break;
            case 'Escape':
                if (isOpen) {
                    event.preventDefault();
                    close();
                }
                break;
            case 'Tab':
                close();
                break;
            default:
                if (event.key.length === 1 && /\S/.test(event.key)) {
                    const letter = event.key.toLowerCase();
                    const all = options();
                    const start = (isOpen ? activeIndex : select.selectedIndex) + 1;
                    const found = [...all.slice(start), ...all.slice(0, start)]
                        .find((option) => option.textContent.trim().toLowerCase().startsWith(letter));
                    if (found) {
                        if (isOpen) {
                            highlight(found.index);
                        } else {
                            choose(found.index);
                        }
                    }
                }
        }
    });

    document.addEventListener('click', (event) => {
        if (! wrapper.contains(event.target)) {
            close();
        }
    });

    // Anything else that changes the select (Alpine, a reset, options arriving later) is mirrored.
    select.addEventListener('change', syncLabel);
    new MutationObserver(syncLabel).observe(select, { childList: true, subtree: true, attributes: true });

    syncLabel();
}

export default function initNiceSelects(root = document) {
    root.querySelectorAll('select[data-nice-select]').forEach(enhance);
}
