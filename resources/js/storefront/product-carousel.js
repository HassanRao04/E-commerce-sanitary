import Swiper from 'swiper';
import { A11y, Autoplay, Navigation } from 'swiper/modules';

const defaultBreakpoints = {
    480: {
        slidesPerView: 1.45,
        spaceBetween: 16,
    },
    640: {
        slidesPerView: 2.1,
        spaceBetween: 20,
    },
    768: {
        slidesPerView: 2.4,
        spaceBetween: 22,
    },
    1024: {
        slidesPerView: 3.15,
        spaceBetween: 24,
    },
    1280: {
        slidesPerView: 4,
        spaceBetween: 28,
    },
};

const bestsellersBreakpoints = {
    480: {
        slidesPerView: 1.3,
        spaceBetween: 18,
    },
    640: {
        slidesPerView: 1.85,
        spaceBetween: 22,
    },
    768: {
        slidesPerView: 2.15,
        spaceBetween: 24,
    },
    1024: {
        slidesPerView: 2.85,
        spaceBetween: 28,
    },
    1280: {
        slidesPerView: 3.35,
        spaceBetween: 32,
    },
};

const updateCarouselProgress = (swiper, root) => {
    const bar = root.querySelector('.product-carousel__progress-bar');

    if (!bar || !swiper.slides.length) {
        return;
    }

    const progress = Math.max(0, Math.min(1, swiper.progress || 0));
    bar.style.width = `${progress * 100}%`;
};

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Initialise all homepage product carousels.
 */
export default function initProductCarousels() {
    document.querySelectorAll('[data-product-carousel]').forEach((root) => {
        const slider = root.querySelector('.product-carousel__slider');

        if (!slider || slider.dataset.swiperInitialized === 'true') {
            return;
        }

        const isBestsellers = root.dataset.carouselVariant === 'bestsellers';
        const prevEl = root.querySelector('.product-carousel__prev');
        const nextEl = root.querySelector('.product-carousel__next');
        const modules = isBestsellers ? [Navigation, A11y, Autoplay] : [Navigation, A11y];

        const swiper = new Swiper(slider, {
            modules,
            slidesPerView: isBestsellers ? 1.15 : 1.05,
            spaceBetween: isBestsellers ? 16 : 12,
            speed: isBestsellers ? 650 : 550,
            grabCursor: true,
            watchOverflow: true,
            navigation: {
                prevEl,
                nextEl,
            },
            a11y: {
                prevSlideMessage: 'Previous products',
                nextSlideMessage: 'Next products',
            },
            ...(isBestsellers && !prefersReducedMotion()
                ? {
                    autoplay: {
                        delay: 5200,
                        disableOnInteraction: false,
                        pauseOnMouseEnter: true,
                    },
                }
                : {}),
            breakpoints: isBestsellers ? bestsellersBreakpoints : defaultBreakpoints,
            on: {
                init(instance) {
                    if (isBestsellers) {
                        updateCarouselProgress(instance, root);
                    }
                },
                progress(instance) {
                    if (isBestsellers) {
                        updateCarouselProgress(instance, root);
                    }
                },
                resize(instance) {
                    if (isBestsellers) {
                        updateCarouselProgress(instance, root);
                    }
                },
            },
        });

        slider.dataset.swiperInitialized = 'true';
        root._productCarousel = swiper;
    });
}
