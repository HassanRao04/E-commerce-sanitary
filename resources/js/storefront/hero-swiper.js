import Swiper from 'swiper';
import { Autoplay, EffectFade, Navigation, Pagination, Parallax } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/effect-fade';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

/**
 * Premium hero slider — fade transitions, parallax, autoplay with progress bar.
 */
export default function initHeroSwiper() {
    const root = document.querySelector('[data-hero-swiper]');

    if (!root) {
        return;
    }

    const slider = root.querySelector('.hero-swiper__slider');
    const progressBar = root.querySelector('[data-hero-progress]');

    if (!slider) {
        return;
    }

    const autoplayDelay = Number(root.dataset.autoplayDelay ?? 5500);

    const swiper = new Swiper(slider, {
        modules: [Autoplay, EffectFade, Navigation, Pagination, Parallax],
        effect: 'fade',
        fadeEffect: { crossFade: true },
        speed: 900,
        parallax: true,
        loop: true,
        grabCursor: true,
        watchSlidesProgress: true,
        autoplay: {
            delay: autoplayDelay,
            disableOnInteraction: false,
            pauseOnMouseEnter: true,
        },
        pagination: {
            el: root.querySelector('.hero-swiper__pagination'),
            clickable: true,
            renderBullet(index, className) {
                const labels = ['New Collection', 'Best Sellers', 'Flash Sale', 'Seasonal'];
                const label = labels[index] ?? `Slide ${index + 1}`;

                return `<button type="button" class="${className}" aria-label="${label}"><span class="hero-swiper__bullet-label">${label}</span></button>`;
            },
        },
        navigation: {
            nextEl: root.querySelector('.hero-swiper__next'),
            prevEl: root.querySelector('.hero-swiper__prev'),
        },
        on: {
            init(instance) {
                updateProgress(instance, progressBar, autoplayDelay);
            },
            autoplayTimeLeft(instance, _time, progress) {
                if (progressBar) {
                    progressBar.style.transform = `scaleX(${1 - progress})`;
                }
            },
            slideChangeTransitionStart(instance) {
                animateSlideContent(instance);
                updateProgress(instance, progressBar, autoplayDelay);
            },
        },
    });

    animateSlideContent(swiper);

    root.addEventListener('mouseenter', () => swiper.autoplay?.stop());
    root.addEventListener('mouseleave', () => swiper.autoplay?.start());
}

function animateSlideContent(swiper) {
    swiper.slides.forEach((slide) => {
        slide.querySelectorAll('[data-hero-animate]').forEach((el) => {
            el.classList.remove('is-visible');
        });
    });

    const active = swiper.slides[swiper.activeIndex];

    if (!active) {
        return;
    }

    window.requestAnimationFrame(() => {
        active.querySelectorAll('[data-hero-animate]').forEach((el, index) => {
            el.style.setProperty('--hero-delay', `${120 + index * 90}ms`);
            el.classList.add('is-visible');
        });
    });
}

function updateProgress(swiper, progressBar, delay) {
    if (!progressBar) {
        return;
    }

    progressBar.style.transitionDuration = `${delay}ms`;
    progressBar.style.transform = 'scaleX(1)';

    window.requestAnimationFrame(() => {
        progressBar.style.transform = 'scaleX(0)';
    });
}
