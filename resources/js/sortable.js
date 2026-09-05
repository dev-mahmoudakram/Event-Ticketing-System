/**
 * Drag-to-reorder for admin lists.
 *
 * A list opts in with [data-sortable] and a [data-sortable-url] to post the new order to;
 * each row carries [data-sortable-item="<id>"] and a [.adm-drag-handle] to grab. The order
 * is saved as soon as a row is dropped, so there is no separate save step to forget.
 *
 * Written against the HTML drag-and-drop API rather than a library: the behaviour needed
 * here is one list of rows, and a dependency would cost more than it saves.
 */
function rowsOf(list) {
    return [...list.querySelectorAll('[data-sortable-item]')];
}

async function persist(list) {
    const url = list.dataset.sortableUrl;

    if (! url) {
        return;
    }

    const ids = rowsOf(list).map((row) => Number(row.dataset.sortableItem));

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ ids }),
        });

        if (! response.ok) {
            throw new Error('reorder rejected');
        }

        list.classList.add('is-saved');
        setTimeout(() => list.classList.remove('is-saved'), 1200);
    } catch (error) {
        // The rows have already moved on screen, so saying nothing would leave the admin
        // believing an order that was never stored.
        list.classList.add('is-unsaved');
    }
}

/**
 * Which row the pointer is currently above, ignoring the one being dragged.
 */
function rowUnder(list, y, dragged) {
    return rowsOf(list)
        .filter((row) => row !== dragged)
        .find((row) => {
            const box = row.getBoundingClientRect();

            return y < box.top + box.height / 2;
        }) ?? null;
}

function makeSortable(list) {
    let dragged = null;

    rowsOf(list).forEach((row) => {
        const handle = row.querySelector('.adm-drag-handle');

        if (! handle) {
            return;
        }

        // Only the handle starts a drag, so text inside the row stays selectable.
        handle.addEventListener('mousedown', () => { row.draggable = true; });
        handle.addEventListener('mouseup', () => { row.draggable = false; });

        row.addEventListener('dragstart', () => {
            dragged = row;
            row.classList.add('is-dragging');
        });

        row.addEventListener('dragend', () => {
            row.classList.remove('is-dragging');
            row.draggable = false;
            dragged = null;
            persist(list);
        });
    });

    list.addEventListener('dragover', (event) => {
        if (! dragged) {
            return;
        }

        event.preventDefault();

        const next = rowUnder(list, event.clientY, dragged);

        if (next) {
            list.insertBefore(dragged, next);
        } else {
            list.appendChild(dragged);
        }
    });
}

export default function initSortableLists() {
    document.querySelectorAll('[data-sortable]').forEach(makeSortable);
}
