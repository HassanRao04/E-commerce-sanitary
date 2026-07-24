import Swiper from 'swiper';
import { Autoplay, EffectFade, Navigation, Pagination } from 'swiper/modules';
import gsap from 'gsap';

/**
 * Premium hero — Swiper fades + restrained GSAP reveals.
 * Animate: background/product image, heading, subtitle, buttons (CSS hover).
 * AOS: light chrome (pagination / nav).
 */
export default function initHeroSwiper() {
    const root = document.querySelector('[data-hero-swiper]');

    if (!root) {
        return;
    }

    const slider = root.querySelector('.hero-swiper__slider');
    const progressBar = root.querySelector('[data-hero-progress]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const isCoarse = window.matchMedia('(max-width: 768px), (hover: none)').matches;
    const autoplayDelay = Number(root.dataset.autoplayDelay ?? 5500);

    if (!slider) {
        return;
    }

    /** @type {gsap.core.Timeline | null} */
    let activeTimeline = null;

    if (reducedMotion) {
        root.classList.add('hero-swiper--reduced-motion');
        revealAllStatic(slider);
    }

    const slideCount = slider.querySelectorAll('.swiper-slide').length;
    const slideLabels = parseSlideLabels(root.dataset.slideLabels);
    const enableLoop = slideCount > 1;

    const swiper = new Swiper(slider, {
        modules: [Autoplay, EffectFade, Navigation, Pagination],
        effect: 'fade',
        fadeEffect: { crossFade: true },
        speed: reducedMotion ? 0 : (isCoarse ? 550 : 850),
        loop: enableLoop,
        grabCursor: enableLoop && !reducedMotion && !isCoarse,
        watchSlidesProgress: true,
        autoplay: enableLoop && !reducedMotion
            ? {
                delay: autoplayDelay,
                disableOnInteraction: false,
                pauseOnMouseEnter: !isCoarse,
            }
            : false,
        pagination: {
            el: root.querySelector('.hero-swiper__pagination'),
            clickable: true,
            renderBullet(index, className) {
                const label = slideLabels[index] ?? `Slide ${index + 1}`;

                return `<button type="button" class="${className}" aria-label="${label}"><span class="hero-swiper__bullet-label">${label}</span></button>`;
            },
        },
        navigation: {
            nextEl: root.querySelector('.hero-swiper__next'),
            prevEl: root.querySelector('.hero-swiper__prev'),
        },
        on: {
            init(instance) {
                playSlideIntro(instance, reducedMotion, autoplayDelay, isCoarse, (tl) => {
                    activeTimeline = tl;
                });
            },
            autoplayTimeLeft(_instance, _time, progress) {
                if (progressBar) {
                    progressBar.style.transition = 'none';
                    progressBar.style.transform = `scaleX(${1 - progress})`;
                }
            },
            slideChangeTransitionStart(instance) {
                if (activeTimeline) {
                    activeTimeline.kill();
                    activeTimeline = null;
                }

                playSlideIntro(instance, reducedMotion, autoplayDelay, isCoarse, (tl) => {
                    activeTimeline = tl;
                });
            },
        },
    });

    root.classList.add('is-enhanced');

    if (!enableLoop) {
        root.querySelector('.hero-swiper__prev')?.classList.add('hidden');
        root.querySelector('.hero-swiper__next')?.classList.add('hidden');
    }

    if (progressBar && enableLoop && !reducedMotion) {
        progressBar.style.transform = 'scaleX(1)';
    }

    return swiper;
}

function parseSlideLabels(raw) {
    if (!raw) {
        return [];
    }

    try {
        const parsed = JSON.parse(raw);

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function revealAllStatic(slider) {
    slider.querySelectorAll('[data-hero-animate], [data-hero-bg], [data-hero-overlay]').forEach((el) => {
        el.classList.add('is-visible');
        gsap.set(el, { clearProps: 'all' });
    });
}

/**
 * Elegant per-slide intro — background/product image, copy, CTAs.
 */
function playSlideIntro(swiper, reducedMotion, autoplayDelay, isCoarse, onTimeline) {
    const active = swiper.slides[swiper.activeIndex];

    if (!active) {
        return;
    }

    swiper.slides.forEach((slide) => {
        if (slide === active) {
            return;
        }

        gsap.killTweensOf(slide.querySelectorAll('[data-hero-animate], [data-hero-bg], [data-hero-overlay]'));
        slide.querySelectorAll('[data-hero-animate]').forEach((el) => {
            el.classList.remove('is-visible');
        });
    });

    const bg = active.querySelector('[data-hero-bg]');
    const overlay = active.querySelector('[data-hero-overlay]');
    const eyebrow = active.querySelector('[data-hero-animate="eyebrow"]');
    const heading = active.querySelector('[data-hero-animate="heading"]');
    const subtitle = active.querySelector('[data-hero-animate="subtitle"]');
    const actions = active.querySelector('[data-hero-animate="actions"]');
    const buttons = actions ? Array.from(actions.querySelectorAll('.hero-swiper__btn')) : [];

    if (reducedMotion) {
        [bg, overlay, eyebrow, heading, subtitle, actions].forEach((el) => {
            if (!el) {
                return;
            }

            el.classList.add('is-visible');
            gsap.set(el, { clearProps: 'all' });
        });
        buttons.forEach((btn) => gsap.set(btn, { clearProps: 'all' }));

        return;
    }

    const tl = gsap.timeline({
        defaults: { ease: 'power2.out', force3D: true },
        onComplete: () => {
            [eyebrow, heading, subtitle, actions].forEach((el) => el?.classList.add('is-visible'));
            if (bg) {
                gsap.set(bg, { clearProps: 'opacity' });
            }
        },
    });

    onTimeline?.(tl);

    // Background — lighter on mobile (no continuous Ken Burns)
    if (bg) {
        gsap.killTweensOf(bg);
        tl.fromTo(
            bg,
            { scale: isCoarse ? 1.04 : 1.08, opacity: 0.88, xPercent: isCoarse ? 0 : -1 },
            {
                scale: isCoarse ? 1 : 1.02,
                opacity: 1,
                xPercent: 0,
                duration: isCoarse ? 0.85 : 1.25,
                ease: 'power1.out',
            },
            0,
        );

        if (!isCoarse) {
            const breatheDuration = Math.max(4, (autoplayDelay / 1000) * 0.88);
            tl.to(
                bg,
                { scale: 1.045, duration: breatheDuration, ease: 'none' },
                0.35,
            );
        }
    }

    if (overlay) {
        tl.fromTo(
            overlay,
            { opacity: 0.7 },
            { opacity: 1, duration: isCoarse ? 0.6 : 0.9, ease: 'power1.out' },
            0,
        );
    }

    const copyItems = [eyebrow, heading, subtitle].filter(Boolean);

    copyItems.forEach((el, index) => {
        gsap.killTweensOf(el);
        const y = isCoarse ? (el === heading ? 18 : 14) : (el === heading ? 28 : 20);
        const duration = isCoarse ? 0.55 : (el === heading ? 0.8 : 0.65);

        tl.fromTo(
            el,
            { autoAlpha: 0, y },
            { autoAlpha: 1, y: 0, duration },
            0.15 + index * (isCoarse ? 0.08 : 0.1),
        );
    });

    if (buttons.length) {
        gsap.killTweensOf(buttons);
        tl.fromTo(
            buttons,
            { autoAlpha: 0, y: isCoarse ? 12 : 16 },
            {
                autoAlpha: 1,
                y: 0,
                duration: isCoarse ? 0.45 : 0.55,
                stagger: isCoarse ? 0.05 : 0.08,
            },
            0.15 + copyItems.length * (isCoarse ? 0.08 : 0.1) + 0.04,
        );
    } else if (actions) {
        gsap.killTweensOf(actions);
        tl.fromTo(
            actions,
            { autoAlpha: 0, y: 14 },
            { autoAlpha: 1, y: 0, duration: 0.5 },
            0.45,
        );
    }
}
