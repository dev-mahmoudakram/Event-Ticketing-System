/**
 * Motion for the CCS landing page: hero entrance, parallax phone cards, the Reel
 * coverflow and the scroll progress bar.
 *
 * Every routine bails out when its markup is absent, so this module is safe to load on
 * every page. Generic section reveals are still handled by the IntersectionObserver in
 * app.js — GSAP is reserved for the orchestrated moments.
 */
import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
import { attemptPlay } from './autoplay-video';

gsap.registerPlugin(ScrollTrigger);

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initScrollProgress() {
    const bar = document.querySelector('[data-scroll-progress]');
    if (!bar) return;

    gsap.to(bar, {
        scaleX: 1,
        ease: 'none',
        scrollTrigger: { start: 0, end: 'max', scrub: prefersReducedMotion ? true : 0.25 },
    });
}

function initHero() {
    const hero = document.querySelector('[data-hero]');
    if (!hero) return;

    const lines = hero.querySelectorAll('[data-hero-line] > span');
    const bits = hero.querySelectorAll('[data-hero-bit]');
    const phones = hero.querySelectorAll('[data-hero-phone]');

    if (prefersReducedMotion) {
        gsap.set([...lines, ...bits, ...phones], { clearProps: 'all', opacity: 1, y: 0 });
        return;
    }

    const intro = gsap.timeline({ defaults: { ease: 'power3.out' } });

    intro
        .from(lines, { yPercent: 115, duration: 1.05, stagger: 0.09 })
        .from(bits, { y: 26, opacity: 0, duration: 0.65, stagger: 0.09 }, '-=0.55')
        .from(
            phones,
            {
                // Cards fly in from beyond their own side of the headline, so the two
                // columns resolve inward rather than all sliding the same direction.
                x: (i, el) => (el.dataset.heroPhoneSide === 'start' ? -140 : 140),
                y: 60,
                rotateZ: (i, el) => (el.dataset.heroPhoneSide === 'start' ? -14 : 14),
                opacity: 0,
                duration: 1.1,
                stagger: 0.08,
            },
            '-=0.9',
        );

    // Cards drift at their own pace as the hero scrolls away.
    phones.forEach((phone, index) => {
        gsap.to(phone, {
            yPercent: index % 2 === 0 ? -22 : -38,
            ease: 'none',
            scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: 0.4 },
        });
    });

    // Pointer parallax, weighted so nearer cards move further.
    const quickTo = [...phones].map((phone) => ({
        x: gsap.quickTo(phone, 'x', { duration: 0.7, ease: 'power3.out' }),
        y: gsap.quickTo(phone, 'y', { duration: 0.7, ease: 'power3.out' }),
    }));

    hero.addEventListener('pointermove', (event) => {
        const relX = event.clientX / window.innerWidth - 0.5;
        const relY = event.clientY / window.innerHeight - 0.5;

        quickTo.forEach((setter, index) => {
            const depth = 14 + index * 6;
            setter.x(relX * depth);
            setter.y(relY * depth);
        });
    });

    hero.addEventListener('pointerleave', () => {
        quickTo.forEach((setter) => {
            setter.x(0);
            setter.y(0);
        });
    });
}

function initHubHero() {
    const copy = document.querySelector('[data-hero-copy]');
    if (!copy || prefersReducedMotion) return;

    // A single page-load sequence: the headline, its supporting line and the actions
    // resolve in order. Everything below the fold is left to the existing reveals.
    gsap.from(copy.children, {
        y: 28,
        opacity: 0,
        duration: 0.9,
        stagger: 0.12,
        ease: 'power3.out',
    });
}

function initReel() {
    const stage = document.querySelector('[data-reel-stage]');
    if (!stage) return;

    const cards = [...stage.querySelectorAll('[data-reel-card]')];
    if (cards.length === 0) return;

    const caption = document.querySelector('[data-reel-caption-output]');
    const prev = document.querySelector('[data-reel-prev]');
    const next = document.querySelector('[data-reel-next]');

    // Two cards each side of the focused one, so the fan reads 2 · 1 · 2.
    const VISIBLE_EACH_SIDE = 2;
    let active = Math.floor(cards.length / 2);
    // One switch for the whole section, not per clip: whichever card is active picks it up,
    // and every other clip stays muted regardless of this flag.
    let soundOn = false;

    stage.dataset.enhanced = 'true';

    /**
     * Shortest signed distance from the active card around the ring, so the strip wraps
     * instead of running out of cards at either end. With n cards the result lands in
     * roughly [-n/2, n/2], which is what keeps the fan populated on every step.
     */
    const wrappedOffset = (index) => {
        const count = cards.length;
        let offset = (((index - active) % count) + count) % count;
        if (offset > count / 2) {
            offset -= count;
        }
        return offset;
    };

    const layout = (animate = true) => {
        // Spacing follows the rendered card width so the fan holds together across the
        // clamp()'d sizes and after a resize.
        const cardWidth = cards[0].offsetWidth || 240;

        cards.forEach((card, index) => {
            const offset = wrappedOffset(index);
            const distance = Math.abs(offset);
            const isActive = offset === 0;
            const isVisible = distance <= VISIBLE_EACH_SIDE;

            card.dataset.active = String(isActive);
            card.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            card.tabIndex = isActive ? 0 : -1;
            // Cards parked off-stage must not swallow clicks meant for the visible fan.
            card.style.pointerEvents = isVisible ? 'auto' : 'none';

            gsap.to(card, {
                x: offset * cardWidth * 0.64,
                z: -distance * 170,
                rotateY: offset * -20,
                // Every card in the visible window reads at full size and brightness — only
                // its position in the fan (and the click-through disabling above) marks it as
                // off to the side, not a faded/shrunken look.
                scale: 1,
                opacity: isVisible ? 1 : 0,
                zIndex: 100 - distance,
                duration: animate ? 0.7 : 0,
                ease: 'power3.out',
                overwrite: 'auto',
            });

            // Only the focused clip plays; the rest hold on their poster frame. The active
            // clip's source is attached here too — a card that is never brought to focus is
            // never fetched at all.
            const video = card.querySelector('video');
            if (video) {
                if (isActive) {
                    video.muted = !soundOn;
                    attemptPlay(video);
                } else {
                    // Every clip but the active one stays muted, so switching cards never
                    // plays more than one clip's sound at once.
                    video.muted = true;
                    video.pause();
                }
            }
        });

        if (caption) {
            const text = cards[active].dataset.reelCaption ?? '';
            if (animate && !prefersReducedMotion) {
                gsap.fromTo(caption, { opacity: 0, y: 8 }, { opacity: 1, y: 0, duration: 0.4, ease: 'power2.out' });
            }
            caption.textContent = text;
        }
    };

    const move = (delta) => {
        active = (active + delta + cards.length) % cards.length;
        layout();
    };

    prev?.addEventListener('click', () => move(-1));
    next?.addEventListener('click', () => move(1));

    /**
     * Reflects soundOn onto every card's mute button, not just the active one — CSS shows
     * only the active card's copy, but keeping all of them in sync means whichever card
     * becomes active next is already showing the right icon/label the instant it appears.
     */
    const syncMuteButtons = () => {
        cards.forEach((card) => {
            const button = card.querySelector('[data-reel-mute]');
            if (!button) return;
            button.setAttribute('aria-pressed', String(soundOn));
            button.setAttribute('aria-label', soundOn ? button.dataset.labelMute : button.dataset.labelUnmute);
        });
    };

    cards.forEach((card, index) => {
        card.addEventListener('click', () => {
            if (index === active) return;
            // Step towards the clicked card the short way round, so clicking the card on
            // the left always walks left even when it wraps past the end of the list.
            move(wrappedOffset(index));
        });

        card.querySelector('[data-reel-mute]')?.addEventListener('click', (event) => {
            // The button sits inside the clickable card; without this its click would also
            // fire the card's own "navigate here" handler above.
            event.stopPropagation();

            soundOn = !soundOn;
            syncMuteButtons();

            const activeVideo = cards[active].querySelector('video');
            if (activeVideo) {
                activeVideo.muted = !soundOn;
            }
        });
    });

    stage.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowRight') { event.preventDefault(); move(1); }
        if (event.key === 'ArrowLeft') { event.preventDefault(); move(-1); }
    });

    window.addEventListener('resize', () => layout(false));

    layout(false);

    if (!prefersReducedMotion) {
        gsap.from(cards, {
            y: 90,
            opacity: 0,
            duration: 0.9,
            stagger: 0.06,
            ease: 'power3.out',
            scrollTrigger: { trigger: stage, start: 'top 78%' },
            onComplete: () => layout(false),
        });
    }
}

export default function initCcsMotion() {
    initScrollProgress();
    initHero();
    initHubHero();
    initReel();
}
