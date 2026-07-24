import Swiper from 'swiper';
import { A11y, Autoplay, Navigation, Pagination } from 'swiper/modules';

/**
 * Premium testimonials carousel — autoplay, pagination, slide animations.
 */
export default function initTestimonials() {
    document.querySelectorAll('[data-testimonials]').forEach((root) => {
        const slider = root.querySelector('.testimonials-swiper');

        if (!slider || slider.dataset.swiperInitialized === 'true') {
            return;
        }

        const slideCount = slider.querySelectorAll('.swiper-slide').length;
        const progressBar = root.querySelector('[data-testimonials-progress]');
        const autoplayDelay = Number(root.dataset.autoplayDelay ?? 5500);
        const canLoop = slideCount >= 3;

        const swiper = new Swiper(slider, {
            modules: [Autoplay, Pagination, Navigation, A11y],
            slidesPerView: 1.08,
            spaceBetween: 16,
            centeredSlides: true,
            speed: 650,
            grabCursor: true,
            watchOverflow: true,
            loop: canLoop,
            autoplay: {
                delay: autoplayDelay,
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
            },
            pagination: {
                el: root.querySelector('.testimonials-carousel__pagination'),
                clickable: true,
                dynamicBullets: slideCount > 5,
            },
            navigation: {
                prevEl: root.querySelector('.testimonials-carousel__prev'),
                nextEl: root.querySelector('.testimonials-carousel__next'),
            },
            a11y: {
                prevSlideMessage: 'Previous testimonial',
                nextSlideMessage: 'Next testimonial',
            },
            breakpoints: {
                640: {
                    slidesPerView: 1.35,
                    spaceBetween: 20,
                },
                768: {
                    slidesPerView: 1.6,
                    spaceBetween: 24,
                    centeredSlides: false,
                },
                1024: {
                    slidesPerView: 2.15,
                    spaceBetween: 28,
                    centeredSlides: false,
                },
                1280: {
                    slidesPerView: 2.5,
                    spaceBetween: 32,
                    centeredSlides: false,
                },
            },
            on: {
                init(instance) {
                    animateActiveCard(instance);
                    resetProgress(progressBar, autoplayDelay);
                },
                slideChangeTransitionStart(instance) {
                    animateActiveCard(instance);
                    resetProgress(progressBar, autoplayDelay);
                },
                autoplayTimeLeft(_instance, _time, progress) {
                    if (progressBar) {
                        progressBar.style.transform = `scaleX(${1 - progress})`;
                    }
                },
            },
        });

        slider.dataset.swiperInitialized = 'true';

        root.addEventListener('mouseenter', () => swiper.autoplay?.stop());
        root.addEventListener('mouseleave', () => swiper.autoplay?.start());
    });
}

function animateActiveCard(swiper) {
    swiper.slides.forEach((slide) => {
        slide.querySelectorAll('[data-testimonial-animate]').forEach((card) => {
            card.classList.remove('is-active');
        });
    });

    const activeSlide = swiper.slides[swiper.activeIndex];

    if (!activeSlide) {
        return;
    }

    window.requestAnimationFrame(() => {
        activeSlide.querySelector('[data-testimonial-animate]')?.classList.add('is-active');
    });
}

function resetProgress(progressBar, delay) {
    if (!progressBar) {
        return;
    }

    progressBar.style.transitionDuration = `${delay}ms`;
    progressBar.style.transform = 'scaleX(1)';

    window.requestAnimationFrame(() => {
        progressBar.style.transform = 'scaleX(0)';
    });
}
