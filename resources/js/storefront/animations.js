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

const STAGGER_PRESETS_MOBILE = {
    'fade-up': { y: 16, x: 0, opacity: 0, scale: 1 },
    'fade-in': { y: 0, x: 0, opacity: 0, scale: 1 },
    'slide-left': { y: 0, x: -16, opacity: 0, scale: 1 },
    'slide-right': { y: 0, x: 16, opacity: 0, scale: 1 },
    scale: { y: 0, x: 0, opacity: 0, scale: 0.96 },
};

function prefersReducedMotion() {
    return reducedMotionQuery.matches;
}

function isMobile() {
    return mobileQuery.matches;
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
        el.classList.add('gsap-revealed');
    });
}

function initAos() {
    AOS.init({
        once: true,
        duration: isMobile() ? 380 : 550,
        easing: 'ease-out-cubic',
        offset: isMobile() ? 16 : 36,
        delay: 0,
        anchorPlacement: 'top-bottom',
        disableMutationObserver: true,
        throttleDelay: 120,
        debounceDelay: 80,
        disable: prefersReducedMotion() || (isMobile() && window.matchMedia('(max-width: 480px)').matches),
    });
}

function initGsapStagger() {
    document.documentElement.classList.add('js-animations-ready');

    const presets = isMobile() ? STAGGER_PRESETS_MOBILE : STAGGER_PRESETS;

    gsap.utils.toArray('[data-gsap-stagger]').forEach((container) => {
        const effect = container.dataset.gsapStagger || 'fade-up';
        const preset = presets[effect] ?? presets['fade-up'];
        const stagger = parseFloat(container.dataset.gsapStaggerDelay || (isMobile() ? '0.05' : '0.08'));
        const items = container.querySelectorAll('[data-gsap-stagger-item]');

        if (items.length === 0) {
            return;
        }

        gsap.set(items, {
            opacity: preset.opacity,
            x: preset.x,
            y: preset.y,
            scale: preset.scale,
            force3D: true,
        });

        gsap.to(items, {
            opacity: 1,
            x: 0,
            y: 0,
            scale: 1,
            duration: isMobile() ? 0.38 : 0.55,
            stagger,
            ease: 'power2.out',
            force3D: true,
            clearProps: 'transform,opacity',
            onComplete: () => {
                items.forEach((item) => item.classList.add('gsap-revealed'));
            },
            scrollTrigger: {
                trigger: container,
                start: 'top 92%',
                once: true,
                fastScrollEnd: true,
                invalidateOnRefresh: true,
            },
        });
    });
}

/**
 * Prefer CSS :hover when possible — only wire remaining data-gsap-hover nodes.
 */
function initGsapHover() {
    if (isMobile() || window.matchMedia('(hover: none)').matches) {
        return;
    }

    const nodes = document.querySelectorAll('[data-gsap-hover]');

    if (nodes.length === 0) {
        return;
    }

    const HOVER_PRESETS = {
        lift: { y: -4, scale: 1.01, boxShadow: '0 10px 28px -10px rgb(11 11 15 / 0.12)' },
        scale: { y: 0, scale: 1.03, boxShadow: '0 8px 22px -10px rgb(11 11 15 / 0.1)' },
        glow: { y: -2, scale: 1.01, boxShadow: '0 0 0 1px rgb(0 113 227 / 0.15), 0 8px 24px -8px rgb(0 113 227 / 0.3)' },
        tilt: { y: -3, scale: 1.015, rotateX: 1.5, rotateY: -1.5 },
    };

    nodes.forEach((el) => {
        const presetName = el.dataset.gsapHover || 'lift';
        const preset = HOVER_PRESETS[presetName] ?? HOVER_PRESETS.lift;

        const reset = { y: 0, scale: 1, rotateX: 0, rotateY: 0, boxShadow: 'none', duration: 0.28, ease: 'power2.out', overwrite: 'auto' };
        const enter = { ...preset, duration: 0.22, ease: 'power2.out', overwrite: 'auto', force3D: true };

        el.addEventListener('mouseenter', () => gsap.to(el, enter), { passive: true });
        el.addEventListener('mouseleave', () => gsap.to(el, reset), { passive: true });
    });
}

function refreshOnResize() {
    let timer;

    window.addEventListener('resize', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (window.AOS) {
                AOS.refresh();
            }
            ScrollTrigger.refresh();
        }, 280);
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
    refreshOnResize();

    reducedMotionQuery.addEventListener('change', (event) => {
        if (event.matches) {
            try {
                AOS.refreshHard();
            } catch {
                // AOS may be disabled
            }
            ScrollTrigger.getAll().forEach((trigger) => trigger.kill());
            revealStaticElements(root);
        }
    });
}
