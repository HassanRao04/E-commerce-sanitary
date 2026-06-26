import Swiper from 'swiper';
import { A11y, Navigation } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';

/**
 * Initialise all homepage product carousels.
 */
export default function initProductCarousels() {
    document.querySelectorAll('[data-product-carousel]').forEach((root) => {
        const slider = root.querySelector('.product-carousel__slider');

        if (!slider || slider.dataset.swiperInitialized === 'true') {
            return;
        }

        const prevEl = root.querySelector('.product-carousel__prev');
        const nextEl = root.querySelector('.product-carousel__next');

        new Swiper(slider, {
            modules: [Navigation, A11y],
            slidesPerView: 1.05,
            spaceBetween: 12,
            speed: 550,
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
            breakpoints: {
                480: {
                    slidesPerView: 1.6,
                    spaceBetween: 16,
                },
                640: {
                    slidesPerView: 2.15,
                    spaceBetween: 20,
                },
                768: {
                    slidesPerView: 2.5,
                    spaceBetween: 20,
                },
                1024: {
                    slidesPerView: 3.2,
                    spaceBetween: 24,
                },
                1280: {
                    slidesPerView: 4,
                    spaceBetween: 24,
                },
            },
        });

        slider.dataset.swiperInitialized = 'true';
    });
}
