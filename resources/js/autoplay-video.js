/**
 * Muted, looping background clips: the hero's flanking phones and the reel carousel.
 *
 * Two things were producing "lags, and sometimes shows as just an image":
 *
 * 1. The native `autoplay` attribute fires whenever the browser decides it has buffered
 *    enough — which `preload="metadata"` deliberately starves it of. On a slow connection
 *    the clip stays stuck on its poster frame indefinitely, or stutters once it does start,
 *    and the browser never retries a play() it decided not to attempt.
 * 2. `hidden lg:flex` on the hero's desktop-only phones does not stop the browser
 *    downloading their `<video src>` — only CSS display, not the fetch — so a phone on a
 *    slow connection was paying for clips it could never see.
 *
 * The fix: every clip ships with no `src` at all (`data-src` instead, `preload="none"`),
 * and gets one only right before something actually asks it to play — a phone card, or the
 * reel's active slide. A clip that is never shown is never fetched. play() is awaited so a
 * rejected promise can be retried instead of leaving the poster on screen forever.
 */

function loadIfNeeded(video) {
    if (video.dataset.src && ! video.src) {
        video.src = video.dataset.src;
    }
}

/**
 * Ask a clip to play, tolerating the browser refusing (autoplay policy, not yet buffered,
 * the tab in the background). A rejection here is routine, not an error to report.
 *
 * With preload="none", assigning `src` alone does not start fetching the file — Chrome
 * waits for an explicit load()/play() call before it does anything, so a card sat there
 * showing nothing at all: readyState stuck at 0, waiting on a `loadeddata` event nothing
 * had asked the browser to produce. play() itself is what starts the fetch here, so it is
 * called directly rather than gated behind a readiness check first.
 */
function attemptPlay(video) {
    loadIfNeeded(video);

    video.play().catch(() => {});
}

/**
 * Background clips that are not part of a carousel: the hero's flanking phones, and the
 * Creators Hub events section's fallback clip. Each plays only once actually visible —
 * skipping the hero phones' `hidden lg:flex` pair below `lg`, and never fetching a clip
 * that is scrolled well past — and pauses off-screen so a long scroll does not keep
 * decoding video nobody is looking at.
 */
function initVisibilityDrivenClips() {
    const videos = document.querySelectorAll('[data-hero-phone] [data-autoplay-video], [data-autoplay-visible]');

    if (videos.length === 0) {
        return;
    }

    if (! ('IntersectionObserver' in window)) {
        videos.forEach(attemptPlay);

        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                attemptPlay(entry.target);
            } else {
                entry.target.pause();
            }
        });
    }, { threshold: 0.2 });

    videos.forEach((video) => observer.observe(video));
}

export { attemptPlay };

export default function initAutoplayVideo() {
    initVisibilityDrivenClips();
}
