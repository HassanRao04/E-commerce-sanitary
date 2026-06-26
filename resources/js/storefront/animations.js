import AOS from 'aos';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
const mobileQuery = window.matchMedia('(max-width: 768px)');

const STAGGER_PRESETS = {
    'fade-up': { y: 28, x: 0, opacity: 0, scale: 1 },
    'fade-in': { y: 0, x: 0, opacity: 0, scale: 1 },
    'slide-left': { y: 0, x: -36, opacity: 0, scale: 1 },
    'slide-right': { y: 0, x: 36, opacity: 0, scale: 1 },
    scale: { y: 0, x: 0, opacity: 0, scale: 0.92 },
};

const HOVER_PRESETS = {
    lift: { y: -6, scale: 1.015, boxShadow: '0 12px 32px -8px rgb(11 11 15 / 0.12)' },
    scale: { y: 0, scale: 1.04, boxShadow: '0 8px 24px -8px rgb(11 11 15 / 0.1)' },
    glow: { y: -2, scale: 1.01, boxShadow: '0 0 0 1px rgb(0 113 227 / 0.15), 0 8px 24px -8px rgb(0 113 227 / 0.35)' },
    tilt: { y: -4, scale: 1.02, rotateX: 2, rotateY: -2 },
};

function prefersReducedMotion() {
    return reducedMotionQuery.matches;
}

function revealStaticElements(root) {
    root.querySelectorAll('[data-aos]').forEach((el) => {
        el.classList.add('aos-animate');
        el.style.opacity = '1';
        el.style.transform = 'none';
    });

    root.querySelectorAll('[data-gsap-stagger-item]').forEach((el) => {
        el.style.opacity = '1';
        el.style.transform = 'none';
    });
}

function initAos() {
    AOS.init({
        once: true,
        duration: mobileQuery.matches ? 450 : 650,
        easing: 'ease-out-cubic',
        offset: mobileQuery.matches ? 24 : 40,
        delay: 0,
        anchorPlacement: 'top-bottom',
        disableMutationObserver: true,
        throttleDelay: 99,
        debounceDelay: 50,
        disable: prefersReducedMotion(),
    });
}

function initGsapStagger() {
    document.documentElement.classList.add('js-animations-ready');

    gsap.utils.toArray('[data-gsap-stagger]').forEach((container) => {
        const effect = container.dataset.gsapStagger || 'fade-up';
        const preset = STAGGER_PRESETS[effect] ?? STAGGER_PRESETS['fade-up'];
        const stagger = parseFloat(container.dataset.gsapStaggerDelay || '0.08');
        const items = container.querySelectorAll('[data-gsap-stagger-item]');

        if (items.length === 0) {
            return;
        }

        gsap.set(items, {
            opacity: preset.opacity,
            x: preset.x,
            y: preset.y,
            scale: preset.scale,
            transformOrigin: 'center center',
            willChange: 'transform, opacity',
        });

        gsap.to(items, {
            opacity: 1,
            x: 0,
            y: 0,
            scale: 1,
            duration: mobileQuery.matches ? 0.45 : 0.6,
            stagger,
            ease: 'power2.out',
            clearProps: 'willChange',
            onComplete: () => {
                items.forEach((item) => item.classList.add('gsap-revealed'));
            },
            scrollTrigger: {
                trigger: container,
                start: 'top 88%',
                once: true,
            },
        });
    });
}

function initGsapHover() {
    if (window.matchMedia('(hover: none)').matches) {
        return;
    }

    document.querySelectorAll('[data-gsap-hover]').forEach((el) => {
        const presetName = el.dataset.gsapHover || 'lift';
        const preset = HOVER_PRESETS[presetName] ?? HOVER_PRESETS.lift;
        const isTilt = presetName === 'tilt';

        if (isTilt) {
            el.style.transformPerspective = '800px';
            el.style.transformStyle = 'preserve-3d';
        }

        const reset = { y: 0, scale: 1, rotateX: 0, rotateY: 0, boxShadow: 'none', duration: 0.35, ease: 'power2.out' };
        const enter = { ...preset, duration: 0.28, ease: 'power2.out', overwrite: 'auto' };

        el.addEventListener('mouseenter', () => gsap.to(el, enter));
        el.addEventListener('mouseleave', () => gsap.to(el, reset));
        el.addEventListener('focusin', () => gsap.to(el, enter));
        el.addEventListener('focusout', () => gsap.to(el, reset));
    });
}

function initGsapParallax() {
    document.querySelectorAll('[data-gsap-parallax]').forEach((el) => {
        const amount = parseFloat(el.dataset.gsapParallax || '40');

        gsap.to(el, {
            y: amount,
            ease: 'none',
            scrollTrigger: {
                trigger: el.closest('[data-gsap-parallax-root]') ?? el,
                start: 'top bottom',
                end: 'bottom top',
                scrub: true,
            },
        });
    });
}

function refreshOnResize() {
    let timer;

    window.addEventListener('resize', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            AOS.refresh();
            ScrollTrigger.refresh();
        }, 200);
    }, { passive: true });
}

export function initStorefrontAnimations() {
    const root = document.querySelector('.ds-root');

    if (!root) {
        return;
    }

    if (prefersReducedMotion()) {
        revealStaticElements(root);
        return;
    }

    initAos();
    initGsapStagger();
    initGsapHover();
    initGsapParallax();
    refreshOnResize();

    reducedMotionQuery.addEventListener('change', (event) => {
        if (event.matches) {
            AOS.refreshHard();
            ScrollTrigger.getAll().forEach((trigger) => trigger.kill());
            revealStaticElements(root);
        }
    });
}
