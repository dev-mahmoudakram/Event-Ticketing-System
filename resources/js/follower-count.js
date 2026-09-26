/**
 * Follower-count inputs accept shorthand like "30k" or "1.2m" (and Arabic "٣٠ك", "١٫٥م").
 * While typing, a hint under the field shows the number that will be saved. The server does
 * the real reading (App\Support\FollowerCount); this mirrors it only to reassure.
 *
 *   <input data-follower-count data-follower-hint=":count followers" aria-describedby="x-hint">
 *   <p id="x-hint" hidden></p>
 */
const MULTIPLIERS = {
    k: 1e3, 'ك': 1e3, 'ألف': 1e3, 'الف': 1e3,
    m: 1e6, 'م': 1e6, 'مليون': 1e6,
    b: 1e9, 'مليار': 1e9,
};

const suffixPattern = Object.keys(MULTIPLIERS)
    .sort((a, b) => b.length - a.length)
    .join('|');
const countPattern = new RegExp(`^(\\d+(?:\\.\\d+)?)(${suffixPattern})?$`, 'u');

export function parseFollowerCount(value) {
    const text = String(value ?? '')
        .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x0660))
        .replace(/[۰-۹]/g, (digit) => String(digit.charCodeAt(0) - 0x06F0))
        .replace(/٫/g, '.')
        .replace(/[٬,_\s]/g, '')
        .toLowerCase();

    const match = text.match(countPattern);
    if (! match) {
        return null;
    }

    const [, number, suffix] = match;
    if (! suffix && number.includes('.')) {
        return null;
    }

    return Math.round(Number(number) * (suffix ? MULTIPLIERS[suffix] : 1));
}

export default function initFollowerCounts() {
    const formatter = new Intl.NumberFormat(document.documentElement.lang || 'en');

    document.querySelectorAll('input[data-follower-count]').forEach((input) => {
        const hint = document.getElementById(input.getAttribute('aria-describedby'));
        if (! hint) {
            return;
        }

        const update = () => {
            const count = parseFollowerCount(input.value);
            const typedPlainNumber = /^\d+$/.test(input.value.trim());

            // Only worth saying when the shorthand changed something ("30k" → 30,000).
            if (count === null || input.value.trim() === '' || typedPlainNumber) {
                hint.hidden = true;
                hint.textContent = '';

                return;
            }

            hint.textContent = '= ' + input.dataset.followerHint.replace(':count', formatter.format(count));
            hint.hidden = false;
        };

        input.addEventListener('input', update);
        update();
    });
}
